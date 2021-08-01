<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Contracts;

use Torchlight\Block;

interface PostProcessor
{
    public function process(Block $block);
}
