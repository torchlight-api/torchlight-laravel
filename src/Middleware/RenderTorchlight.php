<?php

namespace Hammerstone\Torchlight\Middleware;

use Closure;
use Hammerstone\Torchlight\Blade\BladeManager;
use Illuminate\Http\Request;

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

        return BladeManager::render($response);
    }
}
