<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Torchlight\TorchlightServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class BaseTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            TorchlightServiceProvider::class
        ];
    }
}