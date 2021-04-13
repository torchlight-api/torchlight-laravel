<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Torchlight\Blade;

use Hammerstone\Torchlight\Block;
use Hammerstone\Torchlight\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BladeManager
{
    protected static $blocks = [];

    public static function registerBlock(Block $block)
    {
        static::$blocks[] = $block;
    }

    public static function render(Response $response)
    {
        // Bail early if there are no blocks on this page.
        if (!static::$blocks) {
            return $response;
        }

        $blocks = (new Client)->highlight(static::$blocks);

        static::$blocks = [];

        $content = $response->content();

        foreach ($blocks as $block) {
            $swap = [
                $block->placeholder() => $block->highlighted,
                $block->placeholder('classes') => $block->classes,
                $block->placeholder('styles') => $block->styles,
            ];

            foreach ($swap as $search => $replace) {
                // Substitute all the placeholders that we left with the highlighted html.
                $content = str_replace($search, $replace, $content);
            }
        }

        return $response->setContent($content);
    }
}
