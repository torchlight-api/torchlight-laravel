<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Composer\InstalledVersions;
use Livewire\Livewire;
use Torchlight\Middleware\RenderTorchlight;

class LivewireTest extends BaseTest
{
    /** @test */
    public function livewire_2_registers_a_middleware()
    {
        // Check for the Livewire Facade.
        if (!class_exists('\\Livewire\\Livewire')) {
            return $this->markTestSkipped('Livewire not installed.');
        }

        $version = InstalledVersions::getVersion('livewire/livewire');

        if (version_compare($version, '2.0.0', '>=')) {
            $this->assertTrue(in_array(
                RenderTorchlight::class,
                Livewire::getPersistentMiddleware()
            ));
        } else {
            $this->markTestSkipped('Livewire 1 cannot register middleware.');
        }
    }
}
