<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Blade;

use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Torchlight\Block;
use Torchlight\Client;
use Torchlight\Torchlight;

class BladeManager
{
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

        return str_replace(array_keys($swap), array_values($swap), $content);
    }
}
