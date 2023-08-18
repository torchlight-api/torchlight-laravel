<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev>
 */

namespace Torchlight;

use Illuminate\Support\ServiceProvider;
use Torchlight\Blade\BladeManager;
use Torchlight\Blade\CodeComponent;
use Torchlight\Blade\EngineDecorator;
use Torchlight\Commands\Install;
use Torchlight\Middleware\RenderTorchlight;

class TorchlightServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->bindManagerSingleton();
        $this->registerCommands();
        $this->publishConfig();
        $this->registerBladeComponent();
        $this->registerLivewire();
        $this->decorateGrahamCampbellEngines();
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

    public function registerLivewire()
    {
        // Check for the Livewire Facade.
        if (!class_exists('\\Livewire\\Livewire')) {
            return;
        }

        // Livewire 1.x does not have the `addPersistentMiddleware` method.
        if (method_exists(\Livewire\LivewireManager::class, 'addPersistentMiddleware')) {
            \Livewire\Livewire::addPersistentMiddleware([
                RenderTorchlight::class,
            ]);
        }
    }

    /**
     * Graham Campbell's Markdown package is a common (and excellent) package that many
     * Laravel developers use for markdown. It registers a few view engines so you can
     * just return e.g. `view("file.md")` and the markdown will get rendered to HTML.
     *
     * The markdown file will get parsed *once* and saved to the disk, which could lead
     * to data leaks if you're using a post processor that injects some sort of user
     * details. The first user that hits the page will have their information saved
     * into the compiled views.
     *
     * We decorate the engines that Graham uses so we can alert our post processors
     * not to run when the views are being compiled.
     */
    public function decorateGrahamCampbellEngines()
    {
        if (!class_exists('\\GrahamCampbell\\Markdown\\MarkdownServiceProvider')) {
            return;
        }

        // The engines won't be registered if this is false.
        if (!$this->app->config->get('markdown.views')) {
            return;
        }

        // Decorate all the engines that Graham's package registers.
        $this->decorateEngine('md');
        $this->decorateEngine('phpmd');
        $this->decorateEngine('blademd');
    }

    /**
     * Decorate a single view engine.
     *
     * @param  $name
     */
    protected function decorateEngine($name)
    {
        // No engine registered.
        if (!$resolved = $this->app->view->getEngineResolver()->resolve($name)) {
            return;
        }

        // Wrap the existing engine in our decorator.
        $this->app->view->getEngineResolver()->register($name, function () use ($resolved) {
            return new EngineDecorator($resolved);
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/torchlight.php', 'torchlight');
    }
}
