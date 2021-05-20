<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Torchlight\Middleware\RenderTorchlight;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class MiddlewareTest extends BaseTest
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('torchlight.blade_components', true);
        config()->set('torchlight.token', 'token');
    }

    protected function getView($view)
    {
        Route::get('/torchlight', function () use ($view) {
            return View::file(__DIR__ . '/Support/' . $view);
        })->middleware(RenderTorchlight::class);

        return $this->call('GET', 'torchlight');
    }

    protected function nullApiResponse()
    {
        $this->withoutExceptionHandling();
        Http::fake([
            'api.torchlight.dev/*' => Http::response(null, 200),
        ]);
    }

    protected function legitApiResponse()
    {
        $this->withoutExceptionHandling();
        $response = [
            "blocks" => [[
                "id" => "real_response_id",
                "classes" => "torchlight",
                "styles" => "background-color: #292D3E;",
                "highlighted" => "this is the highlighted response from the server",
            ]]
        ];

        Http::fake([
            'api.torchlight.dev/*' => Http::response($response, 200),
        ]);
    }


    /** @test */
    public function it_sends_a_simple_request_with_no_response()
    {
        $this->nullApiResponse();

        $response = $this->getView('simple-php-hello-world.blade.php');

        $this->assertEquals(
            '<code class="" style="">echo &quot;hello world&quot;;</code>',
            rtrim($response->content())
        );

        Http::assertSent(function ($request) {
            return $request['blocks'][0] === [
                    "id" => "real_response_id",
                    "hash" => "e99681f5450cbaf3774adc5eb74d637f",
                    "language" => "php",
                    "theme" => "material-theme-palenight",
                    "code" => "echo \"hello world\";",
                ];
        });
    }

    /** @test */
    public function it_sends_a_simple_request_with_highlighted_response()
    {
        $this->legitApiResponse();

        $response = $this->getView('simple-php-hello-world.blade.php');

        $this->assertEquals(
            '<code class="torchlight" style="background-color: #292D3E;">this is the highlighted response from the server</code>',
            // See https://github.com/laravel/framework/pull/35874/files for the rtrim reasoning.
            rtrim($response->content())
        );
    }

    /** @test */
    public function classes_get_merged()
    {
        $this->legitApiResponse();

        $response = $this->getView('simple-php-hello-world-with-classes.blade.php');

        $this->assertEquals(
            '<code class="torchlight mt-4" style="background-color: #292D3E;">this is the highlighted response from the server</code>',
            rtrim($response->content())
        );
    }

    /** @test */
    public function attributes_are_preserved()
    {
        $this->legitApiResponse();

        $response = $this->getView('simple-php-hello-world-with-attributes.blade.php');

        $this->assertEquals(
            '<code class="torchlight" style="background-color: #292D3E;" x-data="{}">this is the highlighted response from the server</code>',
            rtrim($response->content())
        );
    }

    /** @test */
    public function language_can_be_set_via_component()
    {
        $this->nullApiResponse();

        $this->getView('simple-js-hello-world.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['language'] === 'javascript';
        });
    }

    /** @test */
    public function theme_can_be_set_via_component()
    {
        $this->nullApiResponse();

        $this->getView('simple-php-hello-world-new-theme.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['theme'] === 'a new theme';
        });
    }

    /** @test */
    public function code_contents_can_be_a_file()
    {
        $this->withoutExceptionHandling();
        $this->nullApiResponse();

        $this->getView('contents-via-file.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === rtrim(file_get_contents(config_path('app.php'), '\n'));
        });
    }
}