<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Torchlight;

use Illuminate\Support\ServiceProvider;
use Torchlight\Blade\BladeManager;
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
            return new Manager;
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
        if (!Torchlight::config('torchlight.blade_components')) {
            return;
        }

        // Laravel before 8.23.0 has a bug that adds extra spaces around components.
        // Obviously this is a problem if your component is wrapped in <pre></pre>
        // tags, which ours usually is.
        // See https://github.com/laravel/framework/blob/8.x/CHANGELOG-8.x.md#v8230-2021-01-19.
        BladeManager::$affectedBySpacingBug = version_compare(app()->version(), '8.23.0', '<');

        $this->loadViewComponentsAs('torchlight', [
            'code' => CodeComponent::class
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/torchlight.php', 'torchlight');
    }
}
