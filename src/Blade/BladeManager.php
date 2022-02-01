<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Blade;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
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
     *
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

        $response = Torchlight::highlight(static::$blocks);
        $response = collect($response)->keyBy->id();

        $ids = Torchlight::findTorchlightIds($content);

        // The first time through we have to expand all
        // the blocks to include the clones.
        foreach ($ids as $id) {
            // For each block, stash the unadulterated content so
            // we can duplicate it for clones if we need to.
            $begin = "<!-- __torchlight-block-[$id]_begin__ -->";
            $end = "<!-- __torchlight-block-[$id]_end__ -->";
            $clean = Str::between($content, $begin, $end);

            $clones = '';

            if ($block = Arr::get($response, $id)) {
                foreach ($block->clones() as $clone) {
                    // Swap the original ID with the cloned ID.
                    $clones .= str_replace(
                        "__torchlight-block-[$id]", "__torchlight-block-[{$clone->id()}]", $clean
                    );

                    // Since we've added a new ID to the template, we
                    // need to make sure we add it to the array of
                    // IDs that drives the str_replace below.
                    $ids[] = $clone->id();
                }
            }

            // Get rid of the first comment no matter what.
            $content = str_replace($begin, '', $content);

            // Replace the second comment with the clones.
            $content = str_replace($end, $clones, $content);
        }

        $swap = [];

        // Second time through we'll populate the replacement array.
        foreach ($ids as $id) {
            /** @var Block $block */
            if (!$block = Arr::get($response, $id)) {
                continue;
            }

            // Swap out all the placeholders that we left.
            $swap[$block->placeholder()] = $block->highlighted;
            $swap[$block->placeholder('classes')] = $block->classes;
            $swap[$block->placeholder('styles')] = $block->styles;
            $swap[$block->placeholder('attrs')] = $block->attrsAsString();
        }

        // If this version of Laravel is affected by the spacing bug, then
        // we will swap out our placeholders with a preceding space, and
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
