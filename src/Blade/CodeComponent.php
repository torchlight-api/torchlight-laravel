<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Blade;

use Torchlight\Block;
use Illuminate\View\Component;
use Torchlight\Blade\BladeManager;

class CodeComponent extends Component
{
    public $language;

    public $theme;

    public $contents;

    public $block;

    /**
     * Create a new component instance.
     *
     * @param $language
     * @param null $theme
     * @param null $contents
     * @param null $torchlightId
     */
    public function __construct($language, $theme = null, $contents = null, $torchlightId = null)
    {
        $this->language = $language;
        $this->theme = $theme;
        $this->contents = $contents;

        $this->block = Block::make($torchlightId)->language($this->language)->theme($this->theme);

    }

    public function capture($contents)
    {
        $contents = $contents ?: $this->contents;

        if (is_file($contents)) {
            $contents = file_get_contents($contents);
        } else if (is_file(resource_path($contents))) {
            $contents = file_get_contents(resource_path($contents));
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
        return <<<'EOT'
<code {{
        $attributes->except('style')->merge([
            'class' => $block->placeholder('classes'),
            'style' => $attributes->get('style') . $block->placeholder('styles')
        ])
    }}><?php ob_start(); ?>{{ $slot }}<?php $capture(ob_get_clean()) ?>{{ $block->placeholder() }}</code>
EOT;

    }
}
