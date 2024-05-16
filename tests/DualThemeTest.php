<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Torchlight\Middleware\RenderTorchlight;

class DualThemeTest extends BaseTestCase
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('torchlight.blade_components', true);
        config()->set('torchlight.token', 'token');
        config()->set('torchlight.theme', [
            'github-dark',
            'github-light'
        ]);
    }

    protected function getView($view)
    {
        // This helps when testing multiple Laravel versions locally.
        $this->artisan('view:clear');

        Route::get('/torchlight', function () use ($view) {
            return View::file(__DIR__ . '/Support/' . $view);
        })->middleware(RenderTorchlight::class);

        return $this->call('GET', 'torchlight');
    }

    /** @test */
    public function multiple_themes_with_comma()
    {
        config()->set('torchlight.theme', [
            'github-dark,github-light'
        ]);

        $this->assertDarkLight('github-dark', 'github-light');
    }

    /** @test */
    public function multiple_themes_no_labels()
    {
        config()->set('torchlight.theme', [
            'github-dark',
            'github-light'
        ]);

        $this->assertDarkLight('github-dark', 'github-light');
    }

    /** @test */
    public function multiple_themes_with_labels()
    {
        config()->set('torchlight.theme', [
            'dark' => 'github-dark',
            'light' => 'github-light'
        ]);

        $this->assertDarkLight('dark:github-dark', 'light:github-light');
    }

    protected function assertDarkLight($theme1, $theme2)
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight1',
            'styles' => 'background-color: #111111;',
            'highlighted' => 'response 1',
        ]);

        $this->fakeSuccessfulResponse('component_clone_0', [
            'classes' => 'torchlight2',
            'styles' => 'background-color: #222222;',
            'highlighted' => 'response 2',
        ]);

        $response = $this->getView('simple-php-hello-world.blade.php');

        $this->assertEquals(
            "<pre><code data-theme=\"{$theme1}\" data-lang=\"php\" class=\"torchlight1\" style=\"background-color: #111111;\">response 1</code><code data-theme=\"{$theme2}\" data-lang=\"php\" class=\"torchlight2\" style=\"background-color: #222222;\">response 2</code></pre>",
            $response->content()
        );
    }
}
