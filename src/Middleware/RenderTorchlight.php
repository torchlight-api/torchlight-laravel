<?php

namespace Torchlight\Middleware;

use Closure;
use Torchlight\Blade\BladeManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class RenderTorchlight
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Must be a regular, HTML response.
        if (!$response instanceof Response || !Str::contains($response->headers->get('content-type'), 'html')) {
            return $response;
        }

        return BladeManager::renderResponse($response);
    }
}
