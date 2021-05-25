<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Blade;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Torchlight\Block;
use Torchlight\Torchlight;

class BladeManager
{
    /**
     * Laravel before 8.23.0 has a bug that adds extra spaces around components.
     * Obviously this is a problem if your component is wrapped in <pre></pre>
     * tags, which ours usually is.
     *
     * @see https://github.com/laravel/framework/blob/8.x/CHANGELOG-8.x.md#v8230-2021-01-19.
     * @var bool
     */
    public static $affectedBySpacingBug = false;

    /**
     * @var array
     */
    protected static $blocks = [];

    public static function registerBlock(Block $block)
    {
        static::$blocks[$block->id()] = $block;
    }

    public static function getBlocks()
    {
        return static::$blocks;
    }

    public static function clearBlocks()
    {
        static::$blocks = [];
    }

    public static function renderResponse(Response $response)
    {
        // Bail early if there are no blocks registered.
        if (!static::$blocks) {
            return $response;
        }

        return $response->setContent(
            static::renderContent($response->content())
        );
    }

    public static function renderContent($content)
    {
        // Bail early if there are no blocks registered.
        if (!static::$blocks) {
            return $content;
        }

        Torchlight::highlight(static::$blocks);

        $ids = Torchlight::findTorchlightIds($content);

        $swap = [];

        foreach ($ids as $id) {
            /** @var Block $block */
            if (!$block = Arr::get(static::$blocks, $id)) {
                continue;
            }

            // Swap out all the placeholders that we left.
            $swap[$block->placeholder()] = $block->highlighted;
            $swap[$block->placeholder('classes')] = $block->classes;
            $swap[$block->placeholder('styles')] = $block->styles;
        }

        // If this version of Laravel is affected by the spacing bug, then
        // we will swap our our placeholders with a preceding space, and
        // a following space. This effectively fixes the bug.
        if (static::$affectedBySpacingBug) {
            $swap[' ##PRE_TL_COMPONENT##'] = '';
            $swap['##POST_TL_COMPONENT## '] = '';
        }

        // No matter what, always get rid of the placeholders.
        $swap['##PRE_TL_COMPONENT##'] = '';
        $swap['##POST_TL_COMPONENT##'] = '';

        return str_replace(array_keys($swap), array_values($swap), $content);
    }
}
