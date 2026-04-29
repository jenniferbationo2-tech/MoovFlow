<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Verifie la presence d un des roles attendus.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $flattenedRoles = collect($roles)
            ->flatMap(fn (string $role): array => array_filter(explode('|', $role)))
            ->values()
            ->all();

        if (! $user->hasAnyRole($flattenedRoles)) {
            abort(Response::HTTP_FORBIDDEN, 'Vous n avez pas le role requis pour acceder a cette ressource.');
        }

        return $next($request);
    }
}