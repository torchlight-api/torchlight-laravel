<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Torchlight\Blade;

use Hammerstone\Torchlight\Block;
use Hammerstone\Torchlight\Client;

class BladeManager
{
    public static $blockCount = 0;

    protected static $blocks = [];

    public static function registerDirectives($app)
    {
        $class = static::class;

        $app['blade.compiler']->directive('torchlight', function ($language = null, $theme = null) use ($class) {
            BladeManager::incrementBlockCount();

            // If the developer didn't pass anything in to the directive, use
            // the string 'null', which will pass `null` to the function.
            $language = $language ?: 'null';
            $theme = $theme ?: 'null';

            return "<?php $class::openBlock($language, $theme); ?>";
        });

        $app['blade.compiler']->directive('endtorchlight', function () use ($class) {
            return "<?php $class::closeBlock(); ?>";
        });
    }

    public static function incrementBlockCount()
    {
        // Start the outermost output buffer that captures all blocks.
        if (static::$blockCount === 0) {
            ob_start();
        }

        static::$blockCount++;
    }

    public static function decrementBlockCount()
    {
        static::$blockCount--;

        // If all the blocks are closed then close the outermost
        // buffer and add the highlighting to the page.
        if (static::$blockCount === 0) {
            static::finalize(ob_get_clean());
        }
    }

    public static function openBlock($language, $theme)
    {
        // Start the output buffer for this specific block.
        ob_start();

        static::$blocks[] = Block::make()->setLanguage($language)->setTheme($theme);
    }

    public static function closeBlock()
    {
        // Close the block, cleaning the buffer out and storing it
        // as the developer's code they'd like us to highlight.
        $code = ob_get_clean();

        /** @var \Hammerstone\Torchlight\Block $block */
        $block = last(static::$blocks)->setCode($code);

        // Echo out a unique placeholder into the buffer, which is captured
        // by us, so we can replace it with highlighted code later.
        echo $block->placeholder();

        static::decrementBlockCount();
    }

    public static function finalize($buffer)
    {
        $blocks = (new Client)->highlight(static::$blocks);

        static::$blocks = [];

        foreach ($blocks as $block) {
            // Substitute all the placeholders that we left with the highlighted html.
            $buffer = str_replace($block->placeholder(), $block->html, $buffer);
        }

        // Echo out the finalized buffer, which now includes our highlighted code.
        echo $buffer;
    }
}
