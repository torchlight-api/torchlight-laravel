<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Orchestra\Testbench\TestCase;
use Torchlight\TorchlightServiceProvider;

abstract class BaseTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TorchlightServiceProvider::class
        ];
    }
}
