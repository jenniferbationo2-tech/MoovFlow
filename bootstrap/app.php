<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'log.activity' => \App\Http\Middleware\LogActivity::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\LogActivity::class,
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    /*->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\Illuminate\Http\Middleware\SetCacheHeaders::class);
    })*/
    ->withExceptions(function (Exceptions $exceptions) {
        // Rendu Inertia pour les erreurs HTTP courantes
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if (
                $request->header('X-Inertia') &&
                in_array($response->getStatusCode(), [403, 404, 422, 429, 500, 503])
            ) {
                $statusMessages = [
                    403 => 'Accès non autorisé.',
                    404 => 'La ressource demandée est introuvable.',
                    422 => 'Les données soumises sont invalides.',
                    429 => 'Trop de requêtes. Veuillez patienter.',
                    500 => 'Une erreur interne est survenue.',
                    503 => 'Le service est temporairement indisponible.',
                ];
                $status = $response->getStatusCode();
                return back()->with('error', $statusMessages[$status] ?? "Erreur {$status}");
            }
            return $response;
        });

        // Logger toutes les exceptions non-HTTP (erreurs inattendues)
        $exceptions->report(function (\Throwable $e) {
            if (!($e instanceof \Illuminate\Http\Exceptions\HttpResponseException)
                && !($e instanceof \Illuminate\Validation\ValidationException)
                && !($e instanceof \Illuminate\Auth\AuthenticationException)
                && !($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException)
                && !($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException)
            ) {
                \Log::error('Exception non gérée : ' . $e->getMessage(), [
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });
    })->create();
