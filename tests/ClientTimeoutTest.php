<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Torchlight\Block;
use Torchlight\Exceptions\RequestException;
use Torchlight\Torchlight;

class ClientTimeoutTest extends BaseTestCase
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('torchlight', [
            'theme' => 'material',
            'token' => 'token',
        ]);
    }

    /** @test */
    public function it_catches_the_connect_exception()
    {
        $this->fakeTimeout('timeout');

        // Our exception, not the default Laravel one.
        $this->expectException(RequestException::class);

        Torchlight::highlight(
            Block::make('timeout')->language('php')->code('echo "hello world";')
        );
    }

    /** @test */
    public function it_catches_the_connect_exception_in_prod()
    {
        $this->fakeTimeout('timeout');

        Torchlight::overrideEnvironment('production');

        Torchlight::highlight(
            Block::make('timeout')->language('php')->code('echo "hello world";')
        );

        // Just want to make sure we got past the highlight with no exception.
        $this->assertTrue(true);
    }

    /** @test */
    public function it_catches_a_real_connection_exception()
    {
        config()->set('torchlight.host', 'https://nonexistent.torchlight.dev');

        // Our exception, not the default Laravel one.
        $this->expectException(RequestException::class);

        Torchlight::highlight(
            Block::make('timeout')->language('php')->code('echo "hello world";')
        );
    }
}
