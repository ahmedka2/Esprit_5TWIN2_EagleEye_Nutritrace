<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsPro
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isPro()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès réservé aux professionnels et administrateurs.'], 403);
            }

            return redirect()->route('home')
                ->with('error', "L'Espace pro est réservé aux professionnels de la filière et aux administrateurs.");
        }

        return $next($request);
    }
}
