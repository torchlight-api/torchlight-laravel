<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Torchlight\Block;
use Torchlight\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Torchlight\Torchlight;

class ClientTest extends BaseTest
{
    public function getEnvironmentSetUp($app)
    {
        $this->setUpCache();
        $this->setUpHttpFake();
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
            'theme' => 'material',
            'token' => 'token',
            'bust' => 0
        ]);
    }

    protected function setUpHttpFake()
    {
        $response = [
            "duration" => 118,
            "engine" => 1,
            "blocks" => [[
                "id" => "real_response_id",
                "hash" => "3cb41f9d2e180f47dba1a2e123692f74",
                "language" => "php",
                "theme" => "material",
                "classes" => "torchlight",
                "styles" => "background-color: #292D3E; --theme-selection-background: #00000080;",
                "wrapped" => "<pre><code class='torchlight' style='background-color: #292D3E; --theme-selection-background: #00000080;'><div class='line'><span style=\"color:#3A3F58; text-align: right; user-select: none;\" class=\"line-number\">1</span><span style=\"color: #82AAFF\">echo</span><span style=\"color: #A6ACCD\"> </span><span style=\"color: #89DDFF\">&quot;</span><span style=\"color: #C3E88D\">hello world</span><span style=\"color: #89DDFF\">&quot;</span><span style=\"color: #89DDFF\">;</span></div></code></pre>",

                "highlighted" => "<div class='line'><span style=\"color:#3A3F58; text-align: right; user-select: none;\" class=\"line-number\">1</span><span style=\"color: #82AAFF\">echo</span><span style=\"color: #A6ACCD\"> </span><span style=\"color: #89DDFF\">&quot;</span><span style=\"color: #C3E88D\">hello world</span><span style=\"color: #89DDFF\">&quot;</span><span style=\"color: #89DDFF\">;</span></div>",
            ]]
        ];

        Http::fake([
            'api.torchlight.dev/*' => Http::response($response, 200),
        ]);
    }

    /** @test */
    public function it_sends_a_simple_request()
    {
        Torchlight::highlight(
            Block::make('id')->language('php')->code('echo "hello world";')
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer token')
                && $request['blocks'] === [[
                    "id" => "id",
                    "hash" => "49c75d827bf95472ac155c6b6cc42aaf",
                    "language" => "php",
                    "theme" => "material",
                    "code" => 'echo "hello world";',
                ]];
        });
    }

    /** @test */
    public function block_theme_overrides_config()
    {
        Torchlight::highlight(
            Block::make('id')->language('php')->theme('nord')->code('echo "hello world";')
        );

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['theme'] === "nord";
        });
    }

    /** @test */
    public function a_block_with_html_wont_be_requested()
    {
        $block = Block::make('id')->language('php')->code('echo "hello world";');

        // Fake HTML, as if it had already been rendered.
        $block->wrapped('<code>echo hello</code>');

        Torchlight::highlight($block);

        Http::assertNothingSent();
    }

    /** @test */
    public function only_blocks_without_html_get_sent()
    {
        $shouldNotSend = Block::make('1')->language('php')->code('echo "hello world";');
        // Fake HTML, as if it had already been rendered.
        $shouldNotSend->wrapped('<code>echo hello</code>');

        $shouldSend = Block::make('2')->language('php')->code('echo "hello world";');

        Torchlight::highlight([$shouldNotSend, $shouldSend]);

        Http::assertSent(function ($request) {
            // Only 1 block
            return count($request['blocks']) === 1
                // And only the second block
                && $request['blocks'][0]['id'] === '2';
        });
    }

    /** @test */
    public function a_block_gets_its_html_set()
    {
        $block = Block::make('real_response_id')->language('php')->code('echo "hello world";');

        $this->assertNull($block->wrapped);

        Torchlight::highlight($block);

        $this->assertNotNull($block->wrapped);
    }

    /** @test */
    public function cache_gets_set()
    {
        $block = Block::make('real_response_id')->language('php')->code('echo "hello world";');

        $client = new Client;

        $cacheKey = $client->cacheKey($block);

        $this->assertNull(Cache::get($cacheKey));

        $client->highlight($block);

        $this->assertNotNull(Cache::get($cacheKey));
    }

    /** @test */
    public function already_cached_doesnt_get_sent_again()
    {
        $block = Block::make('fake_id')->language('php')->code('echo "hello world";');

        Torchlight::highlight($block);
        Torchlight::highlight($block);
        Torchlight::highlight($block);
        Torchlight::highlight($block);
        Torchlight::highlight($block);

        // One request to set the cache, none after that.
        Http::assertSentCount(1);
    }

    /** @test */
    public function if_theres_no_response_then_it_sets_a_default()
    {
        $block = Block::make('unknown_id')->language('php')->code('echo "hello world";');

        Torchlight::highlight($block);

        $this->assertEquals('echo &quot;hello world&quot;;', $block->highlighted);
        $this->assertEquals('<pre><code class=\'torchlight\'>echo &quot;hello world&quot;;</code></pre>', $block->wrapped);
    }

}