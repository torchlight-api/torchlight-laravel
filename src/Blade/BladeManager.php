<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Torchlight\Blade;

use Hammerstone\Torchlight\Block;
use Hammerstone\Torchlight\Client;
use Illuminate\Http\Response;

class BladeManager
{
    protected static $blocks = [];

    public static function registerDirectives($app)
    {
        $class = static::class;

        $app['blade.compiler']->directive('torchlight', function ($args) use ($class) {
            return "<?php $class::openBlock($args); ?>";
        });

        $app['blade.compiler']->directive('endtorchlight', function () use ($class) {
            return "<?php $class::closeBlock(); ?>";
        });
    }

    public static function openBlock($language = null, $code = null, $theme = null)
    {
        if (is_null($code)) {
            // If the developer didn't pass any code in, then we assume
            // they are going to render it in the blade view, so we
            // need to capture it.
            ob_start();
        } else if (is_file($code)) {
            $code = file_get_contents($code);
        }

        $block = Block::make()->setLanguage($language)->setTheme($theme);

        if ($code) {
            $block->setCode($code);
        }

        static::$blocks[] = $block;

        if ($code) {
            // If they gave us the code already, then there will be no
            // closing directive so we close ourselves.
            static::closeBlock();
        }
    }

    public static function closeBlock()
    {
        /** @var \Hammerstone\Torchlight\Block $block */
        $block = last(static::$blocks);

        if (!$block->code) {
            // Close the block, cleaning the buffer out and storing it
            // as the developer's code they'd like us to highlight.
            $code = ob_get_clean();

            $block = $block->setCode($code);
        }

        // Echo out a unique placeholder into the buffer, which is captured
        // by us, so we can replace it with highlighted code later.
        echo $block->placeholder();
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
            // Substitute all the placeholders that we left with the highlighted html.
            $content = str_replace($block->placeholder(), $block->html, $content);
        }

        return $response->setContent($content);
    }
}
