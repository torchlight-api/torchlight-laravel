<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight;

use GuzzleHttp\Promise\Promise;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;
use Torchlight\Exceptions\ConfigurationException;
use Torchlight\Exceptions\RequestException;
use Torchlight\Exceptions\TorchlightException;

class Client
{
    public function highlight($blocks)
    {
        $blocks = Arr::wrap($blocks);

        $blocks = $this->collectionOfBlocks($blocks)->keyBy->id();

        // First set the html from the cache if it is already stored.
        $this->setBlocksFromCache($blocks);

        // Then reject all the blocks that already have the html, which
        // will leave us with only the blocks we need to request.
        $needed = $blocks->reject->wrapped;

        // If there are any blocks that don't have html yet,
        // we fire a request.
        if ($needed->count()) {
            // This method will set the html on the block objects,
            // so we don't do anything with the return value.
            $this->request($needed);
        }

        return $blocks->values()->toArray();
    }

    protected function request(Collection $blocks)
    {
        $error = false;
        $response = [];

        try {
            $response = $this->collectionOfBlocks($blocks)
                ->chunk(Torchlight::config('request_chunk_size', 15))
                ->pipe(function ($chunks) {
                    return collect($this->requestChunks($chunks));
                })
                ->map(function ($response) use (&$error) {
                    if ($response instanceof Throwable) {
                        $error = $response;

                        return [];
                    }

                    if ($response->failed()) {
                        $error = $response->toException();

                        return [];
                    }

                    return Arr::get($response->json(), 'blocks', []);
                })
                ->flatten(1);
        } catch (Throwable $e) {
            $this->throwUnlessProduction($e);
        }

        $response = collect($response)->keyBy('id');

        $blocks->each(function (Block $block) use ($response) {
            $blockFromResponse = Arr::get($response, "{$block->id()}", []);

            foreach ($this->applyDirectlyFromResponse() as $key) {
                if (Arr::has($blockFromResponse, $key)) {
                    $block->{$key} = $blockFromResponse[$key];
                }
            }

            if (!$block->wrapped) {
                $block->wrapped = $this->defaultWrapped($block);
            }

            if (!$block->highlighted) {
                $block->highlighted = $this->defaultHighlighted($block);
            }
        });

        // Only store the ones we got back from the API.
        $this->setCacheFromBlocks($blocks, $response->keys());

        $this->potentiallyThrowRequestException($error);

        return $blocks;
    }

    protected function requestChunks($chunks)
    {
        $host = Torchlight::config('host', 'https://api.torchlight.dev');
        $timeout = Torchlight::config('request_timeout', 5);

        // Can't use Http::pool here because it's not
        // available in Laravel 7 and early 8.
        return $chunks
            // This first map fires all the requests.
            ->map(function ($blocks) use ($host, $timeout) {
                return Http::async()
                    ->baseUrl($host)
                    ->timeout($timeout)
                    ->withToken($this->getToken())
                    ->post('highlight', [
                        'blocks' => $this->blocksAsRequestParam($blocks)->values()->toArray(),
                    ]);
            })
            // The second one waits for them all to finish.
            ->map(function (Promise $request) {
                return $request->wait();
            });
    }

    protected function collectionOfBlocks($blocks)
    {
        return collect($blocks)->each(function ($block) {
            if (!$block instanceof Block) {
                throw new TorchlightException('Block not instance of ' . Block::class);
            }
        });
    }

    protected function getToken()
    {
        $token = Torchlight::config('token');

        if (!$token) {
            $this->throwUnlessProduction(
                new ConfigurationException('No Torchlight token configured.')
            );
        }

        return $token;
    }

    protected function potentiallyThrowRequestException($exception)
    {
        if ($exception) {
            $this->throwUnlessProduction(new RequestException($exception->getMessage()));
        }
    }

    protected function throwUnlessProduction($exception)
    {
        throw_unless(Torchlight::environment() === 'production', $exception);
    }

    public function cachePrefix()
    {
        return 'torchlight::';
    }

    public function cacheKey(Block $block)
    {
        return $this->cachePrefix() . 'block-' . $block->hash();
    }

    protected function blocksAsRequestParam(Collection $blocks)
    {
        return $blocks->map(function (Block $block) {
            return $block->toRequestParams();
        });
    }

    protected function applyDirectlyFromResponse()
    {
        return ['wrapped', 'highlighted', 'styles', 'classes'];
    }

    protected function setCacheFromBlocks(Collection $blocks, Collection $ids)
    {
        $keys = $this->applyDirectlyFromResponse();

        $blocks->only($ids)->each(function (Block $block) use ($keys) {
            $value = [];

            foreach ($keys as $key) {
                if ($block->{$key}) {
                    $value[$key] = $block->{$key};
                }
            }

            if (count($value)) {
                Torchlight::cache()->put($this->cacheKey($block), $value, $seconds = 7 * 24 * 60 * 60);
            }
        });
    }

    protected function setBlocksFromCache(Collection $blocks)
    {
        $keys = $this->applyDirectlyFromResponse();

        $blocks->each(function (Block $block) use ($keys) {
            if (!$cached = Torchlight::cache()->get($this->cacheKey($block))) {
                return;
            }

            if (is_string($cached)) {
                return;
            }

            foreach ($keys as $key) {
                if (Arr::has($cached, $key)) {
                    $block->{$key} = $cached[$key];
                }
            }
        });
    }

    /**
     * In the case where nothing returns from the API, we have to show _something_.
     *
     * @param Block $block
     * @return string
     */
    protected function defaultHighlighted(Block $block)
    {
        return htmlentities($block->code);
    }

    /**
     * In the case where nothing returns from the API, we have to show _something_.
     *
     * @param Block $block
     * @return string
     */
    protected function defaultWrapped(Block $block)
    {
        return "<pre><code class='torchlight'>" . $this->defaultHighlighted($block) . '</code></pre>';
    }
}
