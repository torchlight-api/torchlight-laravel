<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Blade;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Torchlight\Block;
use Torchlight\PostProcessors\SimpleSwapProcessor;
use Torchlight\Torchlight;

class CodeComponent extends Component
{
    public $language;

    public $theme;

    public $contents;

    public $block;

    protected $trimFixDelimiter = '##LARAVEL_TRIM_FIXER##';

    /**
     * Create a new component instance.
     *
     * @param  $language
     * @param  null  $theme
     * @param  null  $contents
     * @param  null  $torchlightId
     */
    public function __construct($language, $theme = null, $contents = null, $swap = null, $postProcessors = [], $torchlightId = null)
    {
        $this->language = $language;
        $this->theme = $theme;
        $this->contents = $contents;

        $this->block = Block::make($torchlightId)->language($this->language)->theme($this->theme);

        $postProcessors = Arr::wrap($postProcessors);

        if ($swap) {
            $postProcessors[] = SimpleSwapProcessor::make($swap);
        }

        foreach ($postProcessors as $processor) {
            $this->block->addPostProcessor($processor);
        }
    }

    public function withAttributes(array $attributes)
    {
        // By default Laravel trims slot content in the ManagesComponents
        // trait. The line that does the trimming looks like this:
        // `$defaultSlot = new HtmlString(trim(ob_get_clean()));`

        // The problem with this is that when you have a Blade Component
        // that is indented in this way:

        // <pre>
        //    <x-torchlight-code>
        //        public function {
        //            // test
        //        }
        //    </x-torchlight-code>
        // </pre>

        // Then Laravel will strip the leading whitespace off of the first
        // line, of content making it impossible for us to know how
        // much to dedent the rest of the code.

        // We're hijacking this `withAttributes` method because it is called
        // _after_ the buffer is opened but before the content. So we echo
        // out some nonsense which will prevent Laravel from trimming
        // the whitespace. We'll replace it later. We only do this
        // if it's not a file-based-contents component.
        if (is_null($this->contents)) {
            echo $this->trimFixDelimiter;
        }

        return parent::withAttributes($attributes);
    }

    public function capture($contents)
    {
        $contents = $contents ?: $this->contents;
        $contents = Torchlight::processFileContents($contents) ?: $contents;

        if (Str::startsWith($contents, $this->trimFixDelimiter)) {
            $contents = Str::replaceFirst($this->trimFixDelimiter, '', $contents);
        }

        BladeManager::registerBlock($this->block->code($contents));
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return string
     */
    public function render()
    {
        // Put all of the attributes on the code element, merging in our placeholder
        // classes and style string. Echo out the slot, but capture it using output
        // buffering. We then pass it through as the contents to highlight, leaving
        // the placeholder so we can replace it later with fully highlighted code.
        // We have to add the ##PRE## and ##POST## tags to cover a framework bug.
        // @see BladeManager::renderContent.
        return <<<'EOT'
##PRE_TL_COMPONENT##<!-- {{ $block->placeholder('begin') }} --><code {{ $block->placeholder('attrs') }}{{
        $attributes->except('style')->merge([
            'class' => $block->placeholder('classes'),
            'style' => $attributes->get('style') . $block->placeholder('styles')
        ])
    }}><?php ob_start(); ?>{{ $slot }}<?php $capture(ob_get_clean()) ?>{{ $block->placeholder() }}</code><!-- {{ $block->placeholder('end') }} -->##POST_TL_COMPONENT##
EOT;
    }
}
