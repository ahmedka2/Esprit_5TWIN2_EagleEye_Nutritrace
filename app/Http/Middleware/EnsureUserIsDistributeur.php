<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDistributeur
{
    /**
     * Autorise uniquement les comptes ayant le rôle distributeur.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isDistributeur(), 403);

        return $next($request);
    }
}
