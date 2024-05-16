<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Torchlight\Blade\BladeManager;
use Torchlight\Middleware\RenderTorchlight;

class MiddlewareAndComponentTest extends BaseTestCase
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('torchlight.blade_components', true);
        config()->set('torchlight.token', 'token');
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
    public function it_sends_a_simple_request_with_no_response()
    {
        $this->fakeNullResponse('component');

        $response = $this->getView('simple-php-hello-world.blade.php');

        $this->assertEquals(
            '<pre><code data-theme="material-theme-palenight" data-lang="php" class="torchlight" style=""><div class=\'line\'>echo &quot;hello world&quot;;</div></code></pre>',
            $response->content()
        );

        Http::assertSent(function ($request) {
            return $request['blocks'][0] === [
                'id' => 'component',
                'hash' => '66192c35bf8a710bee532ac328c76977',
                'language' => 'php',
                'theme' => 'material-theme-palenight',
                'code' => 'echo "hello world";',
            ];
        });
    }

    /** @test */
    public function it_sends_a_simple_request_with_highlighted_response()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('simple-php-hello-world.blade.php');

        $this->assertEquals(
            '<pre><code data-theme="material-theme-palenight" data-lang="php" class="torchlight" style="background-color: #292D3E;">this is the highlighted response from the server</code></pre>',
            $response->content()
        );
    }

    /** @test */
    public function it_sends_a_simple_request_with_style()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('simple-php-hello-world-with-style.blade.php');

        $this->assertEquals(
            '<pre><code data-theme="material-theme-palenight" data-lang="php" class="torchlight" style="display: none;background-color: #292D3E;">this is the highlighted response from the server</code></pre>',
            $response->content()
        );
    }

    /** @test */
    public function no_attrs_no_trailing_space()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
            'attrs' => []
        ]);

        $response = $this->getView('simple-php-hello-world-with-style.blade.php');

        $this->assertEquals(
            '<pre><code class="torchlight" style="display: none;background-color: #292D3E;">this is the highlighted response from the server</code></pre>',
            $response->content()
        );
    }

    /** @test */
    public function classes_get_merged()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('simple-php-hello-world-with-classes.blade.php');

        $this->assertEquals(
            '<code data-theme="material-theme-palenight" data-lang="php" class="torchlight mt-4" style="background-color: #292D3E;">this is the highlighted response from the server</code>',
            $response->content()
        );
    }

    /** @test */
    public function attributes_are_preserved()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('simple-php-hello-world-with-attributes.blade.php');

        $this->assertEquals(
            '<code data-theme="material-theme-palenight" data-lang="php" class="torchlight" style="background-color: #292D3E;" x-data="{}">this is the highlighted response from the server</code>',
            $response->content()
        );
    }

    /** @test */
    public function inline_keeps_its_spaces()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'this is the highlighted response from the server',
        ]);

        $response = $this->getView('an-inline-component.blade.php');

        $this->assertEquals(
            'this is <code data-theme="material-theme-palenight" data-lang="php" class="torchlight" style="background-color: #292D3E;">this is the highlighted response from the server</code> inline',
            $response->content()
        );
    }

    /** @test */
    public function inline_swaps_run()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'echo "hello world"',
        ]);

        $response = $this->getView('an-inline-component-with-swaps.blade.php');

        $this->assertEquals(
            'this is <code data-theme="material-theme-palenight" data-lang="php" class="torchlight" style="background-color: #292D3E;">echo "goodbye world"</code> inline',
            $response->content()
        );
    }

    /** @test */
    public function inline_processors_run()
    {
        $this->fakeSuccessfulResponse('component', [
            'classes' => 'torchlight',
            'styles' => 'background-color: #292D3E;',
            'highlighted' => 'echo "hello world"',
        ]);

        $response = $this->getView('an-inline-component-with-post-processors.blade.php');

        $this->assertEquals(
            'this is <code data-theme="material-theme-palenight" data-lang="php" class="torchlight" style="background-color: #292D3E;">echo "goodbye world"</code> inline',
            $response->content()
        );
    }

    /** @test */
    public function language_can_be_set_via_component()
    {
        $this->fakeNullResponse('component');

        $this->getView('simple-js-hello-world.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['language'] === 'javascript';
        });
    }

    /** @test */
    public function theme_can_be_set_via_component()
    {
        $this->fakeNullResponse('component');

        $this->getView('simple-php-hello-world-new-theme.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['theme'] === 'a new theme';
        });
    }

    /** @test */
    public function code_contents_can_be_a_file()
    {
        $this->fakeNullResponse('component');

        $this->getView('contents-via-file.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === rtrim(file_get_contents(config_path('app.php'), '\n'));
        });
    }

    /** @test */
    public function code_contents_can_be_a_file_2()
    {
        $this->fakeNullResponse('component');

        $this->getView('contents-via-file-2.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === rtrim(file_get_contents(config_path('app.php'), '\n'));
        });
    }

    /** @test */
    public function file_must_be_passed_via_contents()
    {
        $this->fakeNullResponse('component');

        $this->getView('file-must-be-passed-through-contents.blade.php');

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['code'] === config_path('app.php');
        });
    }

    /** @test */
    public function dedent_works_properly()
    {
        $this->withoutExceptionHandling();
        $this->fakeNullResponse('component');

        $response = $this->getView('dedent_works_properly.blade.php');

        $result = "<code data-theme=\"material-theme-palenight\" data-lang=\"php\" class=\"torchlight\" style=\"\"><div class='line'>public function {</div><div class='line'>    // test</div><div class='line'>}</div></code>";

        if (BladeManager::$affectedBySpacingBug) {
            $this->assertEquals(
                "<pre>\n    $result\n</pre>\n<pre>$result</pre>\n<pre>$result</pre>",
                $response->content()
            );
        } else {
            $this->assertEquals(
                "<pre>\n    $result</pre>\n<pre>$result</pre>\n<pre>$result</pre>",
                $response->content()
            );
        }
    }

    /** @test */
    public function two_code_in_one_pre()
    {
        $this->withoutExceptionHandling();
        $this->fakeNullResponse('component');

        $response = $this->getView('two-codes-in-one-tag.blade.php');

        $result = "<code data-theme=\"material-theme-palenight\" data-lang=\"php\" class=\"torchlight\" style=\"\"><div class='line'>public function {</div><div class='line'>    // test</div><div class='line'>}</div></code>";

        if (BladeManager::$affectedBySpacingBug) {
            $this->assertEquals(
                "<pre>\n    {$result}\n    {$result}\n</pre>",
                $response->content()
            );
        } else {
            $this->assertEquals(
                "<pre>\n    $result    $result</pre>",
                $response->content()
            );
        }
    }

    /** @test */
    public function two_components_work()
    {
        $this->fakeSuccessfulResponse('component1', [
            'id' => 'component1',
            'classes' => 'torchlight1',
            'styles' => 'background-color: #111111;',
            'highlighted' => 'response 1',
        ]);

        $this->fakeSuccessfulResponse('component2', [
            'id' => 'component2',
            'classes' => 'torchlight2',
            'styles' => 'background-color: #222222;',
            'highlighted' => 'response 2',
        ]);

        $response = $this->getView('two-simple-php-hello-world.blade.php');

        $expected = <<<EOT
<pre><code data-theme="material-theme-palenight" data-lang="php" class="torchlight1" style="background-color: #111111;">response 1</code></pre>

<pre><code data-theme="material-theme-palenight" data-lang="php" class="torchlight2" style="background-color: #222222;">response 2</code></pre>
EOT;

        $this->assertEquals($expected, $response->content());
    }
}
