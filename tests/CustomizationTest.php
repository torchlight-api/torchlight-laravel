<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Torchlight\Torchlight;

class CustomizationTest extends BaseTestCase
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('torchlight.token', 'token from config');
    }

    /** @test */
    public function you_can_use_your_own_config_callback()
    {
        $this->assertEquals('token from config', Torchlight::config('token'));

        Torchlight::getConfigUsing(function ($key, $default) {
            return Arr::get([
                'token' => 'token from callback'
            ], $key);
        });

        $this->assertEquals('token from callback', Torchlight::config('token'));
    }

    /** @test */
    public function prefixing_default_config_with_torchlight_is_ok()
    {
        $this->assertEquals('token from config', Torchlight::config('torchlight.token'));
        $this->assertEquals('token from config', Torchlight::config('token'));
    }

    /** @test */
    public function cache_implementation_can_be_set()
    {
        // The default store will be the file store.
        config()->set('torchlight.cache', 'file');
        // Grab an instance of it so we can use it in the test.
        $originalStore = Cache::store('file');

        // This is the one we'll swap in.
        $newStore = Cache::store('array');

        Torchlight::cache()->set('original_key', 1, 60);

        // Swap in the new cache instance
        Torchlight::setCacheInstance($newStore);
        Torchlight::cache()->put('new_key', 1, 60);

        $this->assertTrue($originalStore->has('original_key'));
        $this->assertFalse($originalStore->has('new_key'));

        $this->assertFalse($newStore->has('original_key'));
        $this->assertTrue($newStore->has('new_key'));
    }

    /** @test */
    public function environment_can_be_set()
    {
        $this->assertEquals('testing', Torchlight::environment());

        Torchlight::overrideEnvironment('production');

        $this->assertEquals('production', Torchlight::environment());

        Torchlight::overrideEnvironment(null);

        $this->assertEquals('testing', Torchlight::environment());
    }

    /** @test */
    public function config_can_be_array()
    {
        $this->assertEquals('token from config', Torchlight::config('token'));

        Torchlight::getConfigUsing([
            'token' => 'plain ol array'
        ]);

        $this->assertEquals('plain ol array', Torchlight::config('token'));
    }
}
