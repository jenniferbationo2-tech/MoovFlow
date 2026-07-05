<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    // Routes à NE PAS logger (trop de bruit)
    private array $routesIgnorees = [
        'login', 'logout', 'password.*',
        '*.store-draft', '*.autosave',
        'livewire.*',
    ];

    // Messages lisibles par route
    private array $messagesParRoute = [
        'evenements.store'              => 'Création d\'un événement',
        'evenements.update'             => 'Modification d\'un événement',
        'evenements.destroy'            => 'Archivage d\'un événement',
        'evenements.valider'            => 'Validation d\'un événement',
        'evenements.rejeter'            => 'Rejet d\'un événement',
        'evenements.demander-validation'=> 'Demande de validation',
        'evenements.demander-modifications' => 'Demande de modifications',
        'inscriptions.preselectionner'  => 'Présélection d\'un candidat',
        'inscriptions.valider'          => 'Validation d\'une inscription',
        'inscriptions.refuser'          => 'Refus d\'une inscription',
        'inscriptions.annuler'          => 'Annulation d\'une inscription',
        'inscriptions.recommander'      => 'Recommandation d\'un candidat',
        'certificats.generer'           => 'Génération de certificats',
        'certificats.envoyer-email'     => 'Envoi d\'un certificat par email',
        'admin.users.debloquer'         => 'Déblocage d\'un compte utilisateur',
        'admin.users.destroy'           => 'Suppression d\'un utilisateur',
        'communication.campaigns.store' => 'Création d\'une campagne',
        'communication.campaigns.envoyer' => 'Envoi d\'une campagne',
        'logistique.store'              => 'Ajout d\'une ressource logistique',
        'rapports.generer'              => 'Génération d\'un rapport RSE',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ignorer si pas connecté
        if (! $request->user()) {
            return $response;
        }

        // Ignorer les méthodes GET
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        // Ignorer les erreurs (400+) sauf 422 (validation)
        $status = $response->getStatusCode();
        if ($status >= 400 && $status !== 422) {
            return $response;
        }

        $routeName = $request->route()?->getName() ?? '';

        // Ignorer les routes blacklistées
        foreach ($this->routesIgnorees as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return $response;
            }
        }

        // Message lisible selon la route
        $message = $this->messagesParRoute[$routeName] ?? null;

        // Si route inconnue → ne pas logger (évite le bruit)
        if (! $message) {
            return $response;
        }

        $subject = collect($request->route()?->parameters() ?? [])
            ->first(fn (mixed $p): bool => $p instanceof Model);

        $logger = activity('audit')
            ->causedBy($request->user())
            ->event('action')
            ->withProperties([
                'route'  => $routeName,
                'method' => $request->method(),
                'status' => $status,
                'ip'     => $request->ip(),
            ]);

        if ($subject instanceof Model) {
            $logger->performedOn($subject);
        }

        $logger->log($message);

        return $response;
    }
}