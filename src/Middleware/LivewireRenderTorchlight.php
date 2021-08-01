<?php

namespace Torchlight\Middleware;

use Torchlight\Blade\BladeManager;

class LivewireRenderTorchlight
{
    public static function hydrate($unHydratedInstance, $request)
    {
        //
    }

    public static function dehydrate($instance, $response)
    {
        $html = BladeManager::renderContent(data_get($response, 'effects.html'));

        data_set($response, 'effects.html', $html);
    }
}
