<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Torchlight\Middleware\RenderTorchlight;

class RealClientTest extends BaseTestCase
{
    public function getEnvironmentSetUp($app)
    {
        $this->setUpTorchlight();
    }

    protected function setUpCache()
    {
        config()->set('cache', [
            'default' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                    'serialize' => false,
                ],
            ],
        ]);
    }

    protected function setUpTorchlight()
    {
        config()->set('torchlight', [
            'theme' => 'material-theme-lighter',
            'token' => '',
            'bust' => 0,
            'blade_components' => true,
            'options' => [
                'lineNumbers' => false
            ]
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
    public function it_sends_a_simple_request_with_highlighted_response_real()
    {
        return $this->markTestSkipped();

        $response = $this->getView('simple-php-hello-world.blade.php');

        $this->assertEquals(
            '<pre><code data-lang="php" class="torchlight" style="background-color: #FAFAFA; --theme-selection-background: #CCD7DA80;"><!-- Syntax highlighted by torchlight.dev --><div class=\'line\'><span style="color: #6182B8;">echo</span><span style="color: #90A4AE;"> </span><span style="color: #39ADB5;">&quot;</span><span style="color: #91B859;">hello world</span><span style="color: #39ADB5;">&quot;</span><span style="color: #39ADB5;">;</span></div></code></pre>',
            $response->content()
        );
    }
}
