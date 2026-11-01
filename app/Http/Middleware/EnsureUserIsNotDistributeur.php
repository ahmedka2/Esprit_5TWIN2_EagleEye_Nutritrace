<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotDistributeur
{
    /**
     * Évite qu'un distributeur accède aux écrans de gestion des autres rôles.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isDistributeur()) {
            return redirect()
                ->route('distributeur.dashboard')
                ->with('error', 'Votre espace est limité à la gestion des véhicules et des livraisons.');
        }

        return $next($request);
    }
}
