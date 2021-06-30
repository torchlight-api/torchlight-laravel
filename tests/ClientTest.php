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
use Torchlight\Exceptions\RequestException;
use Torchlight\Torchlight;

class ClientTest extends BaseTest
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
            'bust' => 0
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
                && $request['blocks'] === [[
                    'id' => 'id',
                    'hash' => '49c75d827bf95472ac155c6b6cc42aaf',
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

        $this->assertEquals('echo &quot;hello world&quot;;', $block->highlighted);
        $this->assertEquals('<pre><code class=\'torchlight\'>echo &quot;hello world&quot;;</code></pre>', $block->wrapped);
    }

    /** @test */
    public function a_500_error_returns_a_default_in_production()
    {
        Torchlight::overrideEnvironment('production');

        $this->addFake('unknown_id', Http::response(null, 500));

        $block = Block::make('unknown_id')->language('php')->code('echo "hello world";');

        Torchlight::highlight($block);

        $this->assertEquals('echo &quot;hello world&quot;;', $block->highlighted);
        $this->assertEquals('<pre><code class=\'torchlight\'>echo &quot;hello world&quot;;</code></pre>', $block->wrapped);
    }

    /** @test */
    public function multiple_requests_get_chunked()
    {
        config()->set('torchlight.request_chunk_size', 2);

        $this->fakeSuccessfulResponse('1');
        $this->fakeSuccessfulResponse('2');
        $this->fakeSuccessfulResponse('3');
        $this->fakeSuccessfulResponse('4');

        Torchlight::highlight([
            Block::make('1')->language('php')->code('echo "hello world 1";'),
            Block::make('2')->language('php')->code('echo "hello world 2";'),
            Block::make('3')->language('php')->code('echo "hello world 3";'),
            Block::make('4')->language('php')->code('echo "hello world 4";'),
        ]);

        // All these should be cached.
        Torchlight::highlight([
            Block::make('1')->language('php')->code('echo "hello world 1";'),
            Block::make('2')->language('php')->code('echo "hello world 2";'),
            Block::make('3')->language('php')->code('echo "hello world 3";'),
            Block::make('4')->language('php')->code('echo "hello world 4";'),
        ]);

        // 2 chunks sent, second set was cached.
        Http::assertSentCount(2);
    }

    /** @test */
    public function blocks_still_get_cached_if_one_request_fails()
    {
        config()->set('torchlight.request_chunk_size', 1);

        $this->fakeSuccessfulResponse('1');
        $this->fakeSuccessfulResponse('2');
        $this->fakeSuccessfulResponse('3');

        $this->fakeTimeout('4');

        try {
            Torchlight::highlight([
                $block1 = Block::make('1')->language('php')->code('echo "hello world 1";'),
                $block2 = Block::make('2')->language('php')->code('echo "hello world 2";'),
                $block3 = Block::make('3')->language('php')->code('echo "hello world 3";'),
                $block4 = Block::make('4')->language('php')->code('echo "hello world 4";'),
            ]);
        } catch (RequestException $exception) {
        }

        // Exception should have been thrown.
        $this->assertInstanceOf(RequestException::class, $exception);

        $client = new Client;

        $this->assertNotNull(Cache::get($client->cacheKey($block1)));
        $this->assertNotNull(Cache::get($client->cacheKey($block2)));
        $this->assertNotNull(Cache::get($client->cacheKey($block3)));
    }
}
