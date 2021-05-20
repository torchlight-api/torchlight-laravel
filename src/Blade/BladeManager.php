<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Blade;

use Torchlight\Block;
use Torchlight\Client;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class BladeManager
{
    protected static $blocks = [];

    public static function registerBlock(Block $block)
    {
        static::$blocks[] = $block;
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

        $blocks = (new Client)->highlight(static::$blocks);

        static::$blocks = [];

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

        return $content;
    }
}
