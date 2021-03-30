<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Torchlight;

use Hammerstone\Torchlight\Exceptions\ConfigurationException;
use Hammerstone\Torchlight\Exceptions\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Client
{
    public function highlight($blocks)
    {
        $blocks = collect($blocks)->keyBy->id();

        // First set the html from the cache if it is already stored.
        $this->setHtmlFromCache($blocks);

        // Then reject all the blocks that already have the html, which
        // will leave us with only the blocks we need to request.
        $needed = $blocks->reject->html;

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
        $host = config('torchlight.host', 'https://api.torchlight.dev');

        $response = Http::timeout(5)
            ->withToken($this->getToken())
            ->post($host . '/highlight', [
                'blocks' => $this->blocksAsRequestParam($blocks)->values(),
            ])
            ->json();

        $this->potentiallyThrowRequestException($response);

        $response = collect(Arr::get($response, 'blocks', []))->keyBy('id');

        $blocks->each(function (Block $block) use ($response) {
            $block->setHtml(
                $block->html ?? $this->getHtmlFromResponse($response, $block)
            );
        });

        // Only store the ones we got back from the API.
        $this->setCacheFromBlocks($blocks, $response->keys());

        return $blocks;
    }

    protected function getToken()
    {
        $token = config('torchlight.token');

        if (!$token) {
            $this->throwUnlessProduction(
                new ConfigurationException('No Torchlight token configured.')
            );
        }

        return $token;
    }

    protected function potentiallyThrowRequestException($response)
    {
        if ($error = Arr::get($response, 'error')) {
            $this->throwUnlessProduction(new RequestException($error));
        }
    }

    protected function throwUnlessProduction($exception)
    {
        throw_unless(app()->environment('production'), $exception);
    }

    public function cache()
    {
        $store = config('torchlight.cache');

        if ($store === null) {
            $store = config('cache.default');
        }

        return Cache::store($store);
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

    protected function setCacheFromBlocks(Collection $blocks, Collection $ids)
    {
        $blocks->only($ids)->each(function (Block $block) use ($ids) {
            if ($block->html) {
                $this->cache()->put(
                    $this->cacheKey($block),
                    $block->html,
                    now()->addDays(7)
                );
            }
        });
    }

    protected function setHtmlFromCache(Collection $blocks)
    {
        $blocks->each(function (Block $block) {
            if ($html = $this->cache()->get($this->cacheKey($block))) {
                $block->setHtml($html);
            }
        });
    }

    /**
     * Get the HTML for a particular block out of the response.
     *
     * @param $response
     * @param Block $block
     * @return string
     */
    protected function getHtmlFromResponse(Collection $response, Block $block)
    {
        $html = Arr::get($response, "{$block->id()}.html", false);

        return $html === false ? $this->defaultHtml($block) : $html;
    }

    /**
     * In the case where nothing returns from the API, we have to show _something_.
     *
     * @param Block $block
     * @return string
     */
    protected function defaultHtml(Block $block)
    {
        return "<pre class='torchlight'><code>" . htmlentities($block->code) . "</code></pre>";
    }
}
