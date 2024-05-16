<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Composer\InstalledVersions;
use Livewire\Livewire;
use Torchlight\Middleware\RenderTorchlight;

class LivewireTest extends BaseTestCase
{
    /** @test */
    public function livewire_registers_a_middleware()
    {
        // Check for the Livewire Facade.
        if (!class_exists('\\Livewire\\Livewire')) {
            return $this->markTestSkipped('Livewire not installed.');
        }

        $this->assertTrue(in_array(
            RenderTorchlight::class, Livewire::getPersistentMiddleware()
        ));
    }
}
