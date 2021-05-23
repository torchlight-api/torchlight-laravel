<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;

class Manager
{
    use Macroable;

    /**
     * @var null|callable
     */
    protected $getConfigUsing;

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var Container
     */
    protected $app;

    /**
     * @var null|string
     */
    protected $environment;

    /**
     * @param Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * @param $blocks
     * @return mixed
     */
    public function highlight($blocks)
    {
        return $this->client()->highlight($blocks);
    }

    /**
     * @return Client
     */
    public function client()
    {
        return $this->app->make(Client::class);
    }

    /**
     * @return string
     */
    public function environment()
    {
        return $this->environment ?? app()->environment();
    }

    /**
     * @param string $environment
     */
    public function overrideEnvironment($environment = null)
    {
        $this->environment = $environment;
    }

    /**
     * Get an item out of the config using dot notation.
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function config($key, $default = null)
    {
        // Default to Laravel's config method.
        $method = $this->getConfigUsing ?? 'config';

        // If we are using Laravel's config method, then we'll prepend
        // the key with `torchlight` if it isn't already there.
        if ($method === 'config') {
            $key = Str::start($key, 'torchlight.');
        }

        return call_user_func($method, $key, $default);
    }

    /**
     * A callback function used to access configuration. By default this
     * is null, which will fall through to Laravel's `config` function.
     *
     * @param $callback
     */
    public function getConfigUsing($callback)
    {
        $this->getConfigUsing = $callback;
    }

    /**
     * Set the cache implementation directly instead of using a driver.
     *
     * @param Repository $cache
     */
    public function setCacheInstance(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * The cache store to use.
     *
     * @return Repository
     */
    public function cache()
    {
        if ($this->cache) {
            return $this->cache;
        }

        // If the developer has requested a particular store, we'll use it.
        // If the config value is null, the default cache will be used.
        return Cache::store($this->config('cache'));
    }

    /**
     * Return all the Torchlight IDs in a given string.
     *
     * @param string $content
     * @return array
     */
    public function findTorchlightIds($content)
    {
        preg_match_all('/__torchlight-block-\[(.+?)\]/', $content, $matches);

        return array_values(array_unique(Arr::get($matches, 1, [])));
    }
}
