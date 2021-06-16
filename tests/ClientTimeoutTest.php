<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use Torchlight\Block;
use Torchlight\Exceptions\RequestException;
use Torchlight\Torchlight;

class ClientTimeoutTest extends BaseTest
{
    public function getEnvironmentSetUp($app)
    {
        config()->set('torchlight', [
            'theme' => 'material',
            'token' => 'token',
            'bust' => 0,
            'host' => 'https://nonexistent.torchlight.dev'
        ]);
    }

    /** @test */
    public function it_catches_the_connect_exception()
    {
        // Our exception, not the default Laravel one.
        $this->expectException(RequestException::class);

        Torchlight::highlight(
            Block::make('id')->language('php')->code('echo "hello world";')
        );
    }

    /** @test */
    public function it_catches_the_connect_exception_in_prod()
    {
        Torchlight::overrideEnvironment('production');

        Torchlight::highlight(
            Block::make('id')->language('php')->code('echo "hello world";')
        );

        // Just want to make sure we got past the highlight with no exception.
        $this->assertTrue(true);
    }
}
