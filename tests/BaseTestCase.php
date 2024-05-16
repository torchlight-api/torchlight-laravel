<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Tests;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise\FulfilledPromise;
use http\Client\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase;
use Torchlight\TorchlightServiceProvider;

abstract class BaseTestCase extends TestCase
{
    protected $apiFaked = false;

    protected $fakeResponseBlocks = [];

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        $providers = [
            TorchlightServiceProvider::class,
        ];

        if (class_exists('\\Livewire\\LivewireServiceProvider')) {
            $providers[] = LivewireServiceProvider::class;
        }

        return $providers;
    }

    protected function fakeApi()
    {
        $this->apiFaked = true;

        $this->fakeResponseBlocks = [];

        Http::fake([
            'api.torchlight.dev/*' => function (Request $request) {
                $response = [];

                foreach ($request->data()['blocks'] as $block) {
                    if (!Arr::has($this->fakeResponseBlocks, $block['id'])) {
                        throw new TransferException('Torchlight block response not set for ' . $block['id']);
                    }

                    $fake = $this->fakeResponseBlocks[$block['id']];

                    if (is_array($fake)) {
                        $highlighted = "<div class='highlighted'>" . $block['code'] . '</div>';

                        $response[] = array_merge($block, [
                            'classes' => 'torchlight',
                            'styles' => 'background-color: #000000;',
                            'attrs' => [
                                'data-theme' => $block['theme'],
                                'data-lang' => $block['language']
                            ],
                            'wrapped' => "<pre><code>$highlighted</code></pre>",
                            'highlighted' => $highlighted,
                        ], $fake);
                    }

                    if ($fake === ConnectException::class) {
                        throw new ConnectException('Connection timed out', $request->toPsrRequest());
                    }

                    if ($fake instanceof Response || $fake instanceof FulfilledPromise) {
                        return $fake;
                    }
                }

                return Http::response([
                    'duration' => 100,
                    'engine' => 1,
                    'blocks' => $response
                ], 200);
            },
        ]);
    }

    protected function fakeSuccessfulResponse($id, $response = [])
    {
        $this->addFake($id, $response);
    }

    protected function fakeTimeout($id)
    {
        $this->addFake($id, ConnectException::class);
    }

    protected function fakeNullResponse($id)
    {
        $this->addFake($id, Http::response(null, 200));
    }

    protected function addFake($id, $response)
    {
        if (!$this->apiFaked) {
            $this->fakeApi();
        }

        $this->fakeResponseBlocks[$id] = $response;
    }
}
