<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Torchlight;

use Torchlight\Blade\BladeManager;
use Torchlight\Blade\CodeComponent;
use Torchlight\Commands\Install;
use Illuminate\Support\ServiceProvider;

class TorchlightServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Install::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/torchlight.php' => config_path('torchlight.php')
        ], 'config');

        if (config('torchlight.blade_components')) {
            $this->loadViewComponentsAs('torchlight', [
                'code' => CodeComponent::class
            ]);
        }

    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/torchlight.php', 'torchlight');
    }

}
