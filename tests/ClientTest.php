<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Torchlight\Block;
use Torchlight\Client;
use Torchlight\Torchlight;

class ClientTest extends BaseTestCase
{
    public function getEnvironmentSetUp($app)
    {
        $this->setUpCache();
        $this->fakeApi();
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
            'bust' => 0,
            'options' => [
                'lineNumbers' => true
            ]
        ]);
    }

    /** @test */
    public function it_sends_a_simple_request()
    {
        $this->fakeSuccessfulResponse('id');

        Torchlight::highlight(
            Block::make('id')->language('php')->code('echo "hello world";')
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer token')
                && $request['options'] === [
                    'lineNumbers' => true
                ]
                && $request['blocks'] === [[
                    'id' => 'id',
                    'hash' => 'e937def4cb365a758d1bf55ecc7fea5b',
                    'language' => 'php',
                    'theme' => 'material',
                    'code' => 'echo "hello world";',
                ]];
        });
    }

    /** @test */
    public function block_theme_overrides_config()
    {
        $this->fakeSuccessfulResponse('id');

        Torchlight::highlight(
            Block::make('id')->language('php')->theme('nord')->code('echo "hello world";')
        );

        Http::assertSent(function ($request) {
            return $request['blocks'][0]['theme'] === 'nord';
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
        $this->fakeSuccessfulResponse('1');
        $this->fakeSuccessfulResponse('2');

        $shouldNotSend = Block::make('1')->language('php')->code('echo "hello world";');
        // Fake HTML, as if it had already been rendered.
        $shouldNotSend->wrapped('<code>echo hello</code>');

        $shouldSend = Block::make('2')->language('php')->code('echo "hello world";');

        Torchlight::highlight([
            $shouldNotSend,
            $shouldSend
        ]);

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
        $this->fakeSuccessfulResponse('success');

        $block = Block::make('success')->language('php')->code('echo "hello world";');

        $this->assertNull($block->wrapped);

        Torchlight::highlight($block);

        $this->assertNotNull($block->wrapped);
    }

    /** @test */
    public function cache_gets_set()
    {
        $this->fakeSuccessfulResponse('success');

        $block = Block::make('success')->language('php')->code('echo "hello world";');

        $client = new Client;

        $cacheKey = $client->cacheKey($block);

        $this->assertNull(Cache::get($cacheKey));

        $client->highlight($block);

        $this->assertNotNull(Cache::get($cacheKey));
    }

    /** @test */
    public function already_cached_doesnt_get_sent_again()
    {
        $this->fakeSuccessfulResponse('success');

        $block = Block::make('success')->language('php')->code('echo "hello world";');

        Torchlight::highlight(clone $block);
        Torchlight::highlight(clone $block);
        Torchlight::highlight(clone $block);
        Torchlight::highlight(clone $block);
        Torchlight::highlight(clone $block);

        // One request to set the cache, none after that.
        Http::assertSentCount(1);
    }

    /** @test */
    public function if_theres_no_response_then_it_sets_a_default()
    {
        $this->fakeNullResponse('unknown_id');

        $block = Block::make('unknown_id')->language('php')->code('echo "hello world";');

        Torchlight::highlight($block);

        $this->assertEquals('<div class=\'line\'>echo &quot;hello world&quot;;</div>', $block->highlighted);
        $this->assertEquals('<pre><code data-lang=\'php\' class=\'torchlight\'><div class=\'line\'>echo &quot;hello world&quot;;</div></code></pre>', $block->wrapped);
    }

    /** @test */
    public function a_500_error_returns_a_default_in_production()
    {
        Torchlight::overrideEnvironment('production');

        $this->addFake('unknown_id', Http::response(null, 500));

        $block = Block::make('unknown_id')->language('php')->code('echo "hello world";');

        Torchlight::highlight($block);

        $this->assertEquals('<div class=\'line\'>echo &quot;hello world&quot;;</div>', $block->highlighted);
        $this->assertEquals('<pre><code data-lang=\'php\' class=\'torchlight\'><div class=\'line\'>echo &quot;hello world&quot;;</div></code></pre>', $block->wrapped);
    }
}
