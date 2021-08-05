<?php
/**
 * @author Aaron Francis <aaron@hammerstone.dev|https://twitter.com/aarondfrancis>
 */

namespace Torchlight\Blade;

use Illuminate\Contracts\View\Engine;
use Torchlight\Torchlight;

class EngineDecorator implements Engine
{
    public $decorated;

    public function __construct($resolved)
    {
        $this->decorated = $resolved;
    }

    public function __get($name)
    {
        return $this->decorated->{$name};
    }

    public function __set($name, $value)
    {
        $this->decorated->{$name} = $value;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->decorated, $name], $arguments);
    }

    public function get($path, array $data = [])
    {
        Torchlight::currentlyCompilingViews(true);

        $result = $this->decorated->get($path, $data);

        Torchlight::currentlyCompilingViews(false);

        return $result;
    }
}
