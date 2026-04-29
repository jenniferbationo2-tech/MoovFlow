<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Journalise les actions ecriture importantes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user() || ! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $subject = collect($request->route()?->parameters() ?? [])
            ->first(fn (mixed $parameter): bool => $parameter instanceof Model);

        $logger = activity('request')
            ->causedBy($request->user())
            ->event(strtolower($request->method()))
            ->withProperties([
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'payload' => collect($request->except([
                    'password',
                    'password_confirmation',
                    'current_password',
                ]))->toArray(),
                'status' => $response->getStatusCode(),
            ]);

        if ($subject instanceof Model) {
            $logger->performedOn($subject);
        }

        $logger->log('Action HTTP importante');

        return $response;
    }
}