<?php

namespace AxoloteSource\Logics\Middleware;

use AxoloteSource\Logics\Enums\Http;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class IsAllow
{
    public function handle(Request $request, Closure $next, string $action)
    {
        if (! $request->user()->belongsToAction($action)) {
            return Response::error(
                message: __('No tienes permiso para acceder a este recurso'),
                data: ['action' => $action],
                status: Http::Forbidden
            );
        }

        return $next($request);
    }
}
