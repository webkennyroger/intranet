<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $request->user()->hasAnyRole(['Super Administrador', 'Administrador', 'Gestor', 'Editor'])) {
            abort(403, 'Acesso ao painel administrativo negado.');
        }

        return $next($request);
    }
}
