<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class SecurityController extends Controller
{
    /**
     * Page principale du centre de sécurité.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('view-audit'); // Réutilise la gate existante

        // ════════════════════════════════════════════
        //   ONGLET 1 : COMPTES BLOQUÉS
        // ════════════════════════════════════════════
        $comptesBloques = User::query()
            ->whereNotNull('bloque_jusqu_a')
            ->where('bloque_jusqu_a', '>', now())
            ->with('roles')
            ->orderByDesc('bloque_jusqu_a')
            ->get()
            ->map(fn (User $u) => [
                'id'                   => $u->id,
                'nom'                  => $u->nom,
                'prenom'               => $u->prenom,
                'email'                => $u->email,
                'telephone'            => $u->telephone,
                'bloque_jusqu_a'       => $u->bloque_jusqu_a?->toIso8601String(),
                'bloque_jusqu_a_human' => $u->bloque_jusqu_a?->locale('fr')->diffForHumans(),
                'tentatives_connexion' => $u->tentatives_connexion ?? 0,
                'roles'                => $u->roles->pluck('name')->all(),
                'derniere_tentative'   => $this->getDerniereTentative($u->id),
            ]);

        // ════════════════════════════════════════════
        //   ONGLET 2 : TENTATIVES DE CONNEXION ÉCHOUÉES (7 derniers jours)
        // ════════════════════════════════════════════
        $tentativesEchouees = Activity::query()
            ->where('log_name', 'request')
            ->where(function ($q) {
                $q->where('properties->url', 'like', '%/login%')
                  ->orWhere('properties->route', 'like', '%login%');
            })
            ->whereNotIn('properties->status', [200, 302])
            ->where('created_at', '>=', now()->subDays(7))
            ->with('causer:id,nom,prenom,email')
            ->latest()
            ->take(100)
            ->get()
            ->map(fn ($a) => [
                'id'               => $a->id,
                'created_at'       => $a->created_at->toIso8601String(),
                'created_at_human' => $a->created_at->locale('fr')->diffForHumans(),
                'ip'               => $a->properties['ip'] ?? '—',
                'email_tente'      => $a->properties['payload']['email'] ?? null,
                'status'           => $a->properties['status'] ?? null,
                'causer'           => $a->causer ? [
                    'id'     => $a->causer->id,
                    'prenom' => $a->causer->prenom,
                    'nom'    => $a->causer->nom,
                    'email'  => $a->causer->email,
                ] : null,
            ]);

        // ════════════════════════════════════════════
        //   ONGLET 3 : ACTIVITÉ PAR UTILISATEUR (TOP 20)
        // ════════════════════════════════════════════
        $activiteParUser = User::query()
            ->select(['id', 'nom', 'prenom', 'email', 'is_active', 'bloque_jusqu_a', 'tentatives_connexion'])
            ->with('roles')
            ->get()
            ->map(function (User $u) {
                $totalConnexions = Activity::where('causer_id', $u->id)
                    ->where('log_name', 'request')
                    ->where(function ($q) {
                        $q->where('properties->url', 'like', '%/login%')
                          ->orWhere('properties->route', 'like', '%login%');
                    })
                    ->count();

                $echecsRecents = Activity::where('causer_id', $u->id)
                    ->where('log_name', 'request')
                    ->where(function ($q) {
                        $q->where('properties->url', 'like', '%/login%')
                          ->orWhere('properties->route', 'like', '%login%');
                    })
                    ->whereNotIn('properties->status', [200, 302])
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count();

                $derniereConnexion = Activity::where('causer_id', $u->id)
                    ->where('log_name', 'request')
                    ->where(function ($q) {
                        $q->where('properties->url', 'like', '%/login%')
                          ->orWhere('properties->route', 'like', '%login%');
                    })
                    ->whereIn('properties->status', [200, 302])
                    ->latest()
                    ->first();

                return [
                    'id'                   => $u->id,
                    'nom'                  => $u->nom,
                    'prenom'               => $u->prenom,
                    'email'                => $u->email,
                    'roles'                => $u->roles->pluck('name')->all(),
                    'is_active'            => (bool) $u->is_active,
                    'est_bloque'           => $u->bloque_jusqu_a && $u->bloque_jusqu_a > now(),
                    'total_connexions'     => $totalConnexions,
                    'echecs_recents'       => $echecsRecents,
                    'tentatives_connexion' => $u->tentatives_connexion ?? 0,
                    'derniere_connexion'   => $derniereConnexion?->created_at?->toIso8601String(),
                    'derniere_connexion_human' => $derniereConnexion?->created_at?->locale('fr')->diffForHumans(),
                    'risque'               => $this->calculerRisque($echecsRecents, $u->tentatives_connexion ?? 0),
                ];
            })
            ->sortByDesc('echecs_recents')
            ->values();

        // ════════════════════════════════════════════
        //   DÉTECTIONS AUTOMATIQUES (ALERTES)
        // ════════════════════════════════════════════

        // Alerte 1 : IP avec plus de 3 échecs en 1h
        $ipsSuspectes = Activity::query()
            ->where('log_name', 'request')
            ->where(function ($q) {
                $q->where('properties->url', 'like', '%/login%')
                  ->orWhere('properties->route', 'like', '%login%');
            })
            ->whereNotIn('properties->status', [200, 302])
            ->where('created_at', '>=', now()->subHour())
            ->get()
            ->groupBy(fn ($a) => $a->properties['ip'] ?? 'inconnue')
            ->filter(fn ($group) => $group->count() >= 3)
            ->map(fn ($group, $ip) => [
                'ip'              => $ip,
                'tentatives'      => $group->count(),
                'premiere'        => $group->min('created_at')?->toIso8601String(),
                'derniere'        => $group->max('created_at')?->toIso8601String(),
                'emails_tentes'   => $group->pluck('properties.payload.email')->filter()->unique()->values()->all(),
            ])
            ->values();

        // Alerte 2 : Compte avec plus de 5 échecs en 1h
        $comptesCibles = Activity::query()
            ->where('log_name', 'request')
            ->where(function ($q) {
                $q->where('properties->url', 'like', '%/login%')
                  ->orWhere('properties->route', 'like', '%login%');
            })
            ->whereNotIn('properties->status', [200, 302])
            ->where('created_at', '>=', now()->subHour())
            ->whereNotNull('properties->payload->email')
            ->get()
            ->groupBy(fn ($a) => $a->properties['payload']['email'] ?? 'inconnu')
            ->filter(fn ($group) => $group->count() >= 5)
            ->map(fn ($group, $email) => [
                'email'      => $email,
                'tentatives' => $group->count(),
                'premiere'   => $group->min('created_at')?->toIso8601String(),
                'derniere'   => $group->max('created_at')?->toIso8601String(),
                'ips'        => $group->pluck('properties.ip')->filter()->unique()->values()->all(),
            ])
            ->values();

        // ════════════════════════════════════════════
        //   KPIs
        // ════════════════════════════════════════════
        $kpis = [
            'comptes_bloques' => $comptesBloques->count(),

            'echecs_aujourdhui' => Activity::where('log_name', 'request')
                ->where(function ($q) {
                    $q->where('properties->url', 'like', '%/login%')
                      ->orWhere('properties->route', 'like', '%login%');
                })
                ->whereNotIn('properties->status', [200, 302])
                ->whereDate('created_at', today())
                ->count(),

            'ips_suspectes' => $ipsSuspectes->count(),

            'comptes_desactives' => User::where('is_active', false)->count(),
        ];

        return Inertia::render('Admin/Security/Index', [
            'kpis'             => $kpis,
            'comptesBloques'   => $comptesBloques,
            'tentativesEchouees' => $tentativesEchouees,
            'activiteParUser'  => $activiteParUser,
            'alertes' => [
                'ips_suspectes'  => $ipsSuspectes,
                'comptes_cibles' => $comptesCibles,
            ],
        ]);
    }

    /**
     * Action : Débloquer un compte depuis cette page.
     */
    public function debloquer(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $user->update([
            'bloque_jusqu_a'       => null,
            'tentatives_connexion' => 0,
        ]);

        return back()->with('success', "Compte de {$user->prenom} {$user->nom} débloqué.");
    }

    // ════════════════════════════════════════════
    //   HELPERS
    // ════════════════════════════════════════════

    /**
     * Récupère la dernière tentative échouée d'un user.
     */
    private function getDerniereTentative(int $userId): ?array
    {
        $derniere = Activity::where('causer_id', $userId)
            ->where('log_name', 'request')
            ->where(function ($q) {
                $q->where('properties->url', 'like', '%/login%')
                  ->orWhere('properties->route', 'like', '%login%');
            })
            ->whereNotIn('properties->status', [200, 302])
            ->latest()
            ->first();

        if (!$derniere) return null;

        return [
            'date'  => $derniere->created_at->locale('fr')->diffForHumans(),
            'ip'    => $derniere->properties['ip'] ?? '—',
        ];
    }

    /**
     * Calcule un niveau de risque selon les échecs.
     */
    private function calculerRisque(int $echecsRecents, int $tentatives): string
    {
        if ($tentatives >= 3) return 'critique';
        if ($echecsRecents >= 5) return 'eleve';
        if ($echecsRecents >= 2) return 'modere';
        return 'faible';
    }
}