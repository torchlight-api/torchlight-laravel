<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Torchlight;

use Illuminate\Support\ServiceProvider;
use Torchlight\Blade\CodeComponent;
use Torchlight\Commands\Install;

class TorchlightServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->bindManagerSingleton();
        $this->registerCommands();
        $this->publishConfig();
        $this->registerBladeComponent();
    }

    public function bindManagerSingleton()
    {
        $this->app->singleton(Manager::class, function () {
            return new Manager($this->app);
        });
    }

    public function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Install::class,
            ]);
        }
    }

    public function publishConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/torchlight.php' => config_path('torchlight.php')
        ], 'config');
    }

    public function registerBladeComponent()
    {
        if (Torchlight::config('torchlight.blade_components')) {
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
