<?php

namespace Torchlight\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Torchlight\Blade\BladeManager;

class RenderTorchlight
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse && class_exists('\\Livewire\\Livewire') && \Livewire\Livewire::isLivewireRequest()) {
            return $this->handleLivewireRequest($response);
        }

        // Must be a regular, HTML response.
        if (!$response instanceof Response || !Str::contains($response->headers->get('content-type'), 'html')) {
            return $response;
        }

        $response = BladeManager::renderResponse($response);

        // Clear blocks from memory to prevent memory leak when using Laravel Octane
        BladeManager::clearBlocks();

        return $response;
    }

    protected function handleLivewireRequest(JsonResponse $response)
    {
        if (!BladeManager::getBlocks()) {
            return $response;
        }

        $data = $response->getData();

        if (data_get($data, 'effects.html')) {
            // Livewire v2
            $html = BladeManager::renderContent(data_get($data, 'effects.html'));

            data_set($data, 'effects.html', $html);
        } else {
            // Livewire v3
            foreach (data_get($data, 'components.*.effects.html') as $componentIndex => $componentHtml) {
                $html = BladeManager::renderContent($componentHtml);
                data_set($data, "components.$componentIndex.effects.html", $html);
            }
        }

        return $response->setData($data);
    }
}
