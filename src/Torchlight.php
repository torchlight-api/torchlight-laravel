<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight;

use Illuminate\Support\Facades\Facade;

class Torchlight extends Facade
{
    /**
     * @return string
     *
     * @see Manager
     */
    protected static function getFacadeAccessor()
    {
        return Manager::class;
    }
}
