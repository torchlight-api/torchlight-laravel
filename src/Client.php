<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Torchlight;

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
        // @TODO handle timeout
        $response = Http::timeout(5)
            ->withToken(config('torchlight.token'))
            ->post('https://torchlight.dev/api/highlight', [
                'blocks' => $this->blocksAsRequestParam($blocks)->values(),
            ])
            ->json();

        $response = collect($response['blocks'])->keyBy('id');

        $blocks->each(function (Block $block) use ($response) {
            $block->setHtml(
                $block->html ?? $this->getHtmlFromResponse($response, $block)
            );
        });

        $this->setCacheFromBlocks($blocks);

        return $blocks;
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

    protected function setCacheFromBlocks(Collection $blocks)
    {
        return $blocks->each(function (Block $block) {
            if (!$block->html) {
                return;
            }

            $this->cache()->put(
                $this->cacheKey($block),
                $block->html,
                now()->addDays(30)
            );
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
        return "<pre><code>" . $block->code . "</code></pre>";
    }
}
