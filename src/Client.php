<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight;

use Illuminate\Http\Client\ConnectionException;
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

        $blocks = $this->collectionOfBlocks($blocks)->values();
        $blocks = $blocks->merge($blocks->map->spawnClones())->flatten();
        $blocks = $blocks->keyBy->id();

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
        try {
            $host = Torchlight::config('host', 'https://api.torchlight.dev');
            $timeout = Torchlight::config('request_timeout', 5);

            $response = Http::baseUrl($host)
                ->timeout($timeout)
                ->withToken($this->getToken())
                ->post('highlight', [
                    'blocks' => $this->blocksAsRequestParam($blocks)->values()->toArray(),
                    'options' => $this->getOptions(),
                ]);

            if ($response->failed()) {
                $this->potentiallyThrowRequestException($response->toException());
                $response = [];
            } else {
                $response = $response->json();
            }
        } catch (Throwable $e) {
            $e instanceof ConnectionException
                ? $this->potentiallyThrowRequestException($e)
                : $this->throwUnlessProduction($e);

            $response = [];
        }

        $response = Arr::get($response, 'blocks', []);
        $response = collect($response)->keyBy('id');

        $blocks->each(function (Block $block) use ($response) {
            $blockFromResponse = Arr::get($response, "{$block->id()}", $this->defaultResponse($block));

            foreach ($this->applyDirectlyFromResponse() as $key) {
                if (Arr::has($blockFromResponse, $key)) {
                    $block->{$key} = $blockFromResponse[$key];
                }
            }
        });

        // Only store the ones we got back from the API.
        $this->setCacheFromBlocks($blocks, $response->keys());

        return $blocks;
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

    protected function getOptions()
    {
        $options = Torchlight::config('options', []);

        if (!is_array($options)) {
            $options = [];
        }

        return $options;
    }

    protected function potentiallyThrowRequestException($exception)
    {
        if ($exception) {
            $wrapped = new RequestException('A Torchlight request exception has occurred.', 0, $exception);

            $this->throwUnlessProduction($wrapped);
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
        return ['wrapped', 'highlighted', 'styles', 'classes', 'attrs'];
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
                $seconds = Torchlight::config('cache_seconds', 7 * 24 * 60 * 60);

                if (is_null($seconds)) {
                    Torchlight::cache()->forever($this->cacheKey($block), $value);
                } else {
                    Torchlight::cache()->put($this->cacheKey($block), $value, (int)$seconds);
                }
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
     * @param  Block  $block
     * @return array
     */
    protected function defaultResponse(Block $block)
    {
        $lines = array_map(function ($line) {
            return "<div class='line'>" . htmlentities($line) . '</div>';
        }, explode("\n", $block->code));

        $highlighted = implode('', $lines);

        return [
            'highlighted' => $highlighted,
            'classes' => 'torchlight',
            'styles' => '',
            'attrs' => [
                'data-theme' => $block->theme,
                'data-lang' => $block->language,
            ],
            'wrapped' => "<pre><code data-lang='{$block->language}' class='torchlight'>{$highlighted}</code></pre>",
        ];
    }
}
