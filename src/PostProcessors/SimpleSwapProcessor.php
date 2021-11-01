<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\PostProcessors;

use Torchlight\Block;
use Torchlight\Contracts\PostProcessor;

class SimpleSwapProcessor implements PostProcessor
{
    public $swap = [];

    public static function make($swap)
    {
        return new static($swap);
    }

    public function __construct($swap)
    {
        $this->swap = $swap;
    }

    public function process(Block $block)
    {
        $block->highlighted = str_replace(array_keys($this->swap), array_values($this->swap), $block->highlighted);
    }
}
