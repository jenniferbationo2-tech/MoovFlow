<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\ObjectifRse;
use App\Models\User;
use App\Services\DashboardAggregatorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAggregatorService $dashboardAggregatorService
    ) {
    }

    public function index(): Response
    {
        $user  = Auth::user();
        $roles = $user->roles->pluck('name')->all();

        // Routage par rôle
        if (in_array('admin', $roles)) {
            return $this->adminDashboard($user);
        }
        if (in_array('responsable_dcirp', $roles)) {
            return $this->responsableDashboard($user);
        }
        if (in_array('organisateur', $roles)) {
            return $this->organisateurDashboard($user);
        }

        return $this->participantDashboard($user);
    }

    /**
     * Dashboard ADMIN - Vue système et opérationnelle.
     */
    private function adminDashboard(User $user): Response
    {
        // ── KPIs OPÉRATIONNELS ──────────────────────
        $kpis = [
            'utilisateurs_actifs' => User::where('is_active', true)->count(),
            'evenements_publies'  => Evenement::where('statut', 'publie')->count(),
            'dossiers_a_analyser' => Inscription::whereIn('statut', ['en_attente', 'en_analyse'])->count(),
            'comptes_bloques'     => User::whereNotNull('bloque_jusqu_a')
                ->where('bloque_jusqu_a', '>', now())
                ->count(),
        ];

        // ── ACTIVITÉ RÉCENTE ─────────────────────────
        $activiteRecente = Inscription::query()
            ->with(['user:id,nom,prenom', 'evenement:id,titre'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Inscription $i) => [
                'id'         => $i->id,
                'user'       => $i->user ? "{$i->user->prenom} {$i->user->nom}" : 'Visiteur',
                'evenement'  => $i->evenement?->titre ?? '—',
                'statut'     => $i->statut,
                'created_at' => optional($i->created_at)?->toIso8601String(),
            ])
            ->all();

        // ── PROCHAINS ÉVÉNEMENTS ────────────────────
        $prochainsEvenements = Evenement::query()
            ->with(['typeEvenement:id,nom,code', 'lieu:id,nom'])
            ->where('statut', 'publie')
            ->where('date_debut', '>=', now())
            ->orderBy('date_debut')
            ->take(5)
            ->get()
            ->map(fn (Evenement $e) => [
                'id'         => $e->id,
                'titre'      => $e->titre,
                'date_debut' => optional($e->date_debut)?->toIso8601String(),
                'lieu'       => $e->lieu?->nom,
                'type'       => $e->typeEvenement ? [
                    'nom'  => $e->typeEvenement->nom,
                    'code' => $e->typeEvenement->code,
                ] : null,
            ])
            ->all();

        // ── RÉPARTITION UTILISATEURS PAR RÔLE ───────
        $repartitionRoles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('roles.name', DB::raw('COUNT(*) as total'))
            ->groupBy('roles.name')
            ->get()
            ->map(fn ($r) => ['role' => $r->name, 'total' => (int) $r->total])
            ->all();

        // ── STATUT ÉVÉNEMENTS ───────────────────────
        $completionEvenements = [
            'brouillon' => Evenement::where('statut', 'brouillon')->count(),
            'publie'    => Evenement::where('statut', 'publie')->count(),
            'en_cours'  => Evenement::where('statut', 'en_cours')->count(),
            'termine'   => Evenement::where('statut', 'termine')->count(),
            'annule'    => Evenement::where('statut', 'annule')->count(),
        ];

        return Inertia::render('Dashboard', [
            'role'                  => 'admin',
            'kpis'                  => $kpis,
            'activiteRecente'       => $activiteRecente,
            'prochainsEvenements'   => $prochainsEvenements,
            'repartitionRoles'      => $repartitionRoles,
            'completionEvenements'  => $completionEvenements,
        ]);
    }

    /**
     * Dashboard RESPONSABLE dCIRP - Vue stratégique RSE.
     */
    private function responsableDashboard(User $user): Response
    {
        $globalStats = $this->dashboardAggregatorService->getGlobalStats();

        $totalBeneficiaires = (int) ObjectifRse::sum('nb_beneficiaires_directs');
        $totalFemmes        = (int) ObjectifRse::sum('nb_femmes_beneficiaires');
        $totalAssociations  = (int) ObjectifRse::sum('nb_associations_soutenues');
        $tauxFemmes         = $totalBeneficiaires > 0
            ? round(($totalFemmes / $totalBeneficiaires) * 100, 1)
            : 0;
        $budgetEngage = (float) ($globalStats['cards']['budget_total']['depense'] ?? 0);
        $budgetTotal  = (float) ($globalStats['cards']['budget_total']['previsionnel'] ?? 0);
        $tauxBudget   = $budgetTotal > 0 ? round(($budgetEngage / $budgetTotal) * 100) : 0;

        $kpis = [
            'evenements_en_cours' => Evenement::where('statut', 'en_cours')->count(),
            'dossiers_a_valider'  => Inscription::whereIn('statut', ['en_attente', 'en_analyse'])->count(),
            'taux_presence'       => $this->calculerTauxPresence(),
            'beneficiaires'       => $totalBeneficiaires,
        ];

        $statsRSE = [
            'beneficiaires_directs'   => $totalBeneficiaires,
            'taux_femmes'             => $tauxFemmes,
            'cible_femmes'            => 60,
            'associations_soutenues'  => $totalAssociations,
            'budget_engage'           => $budgetEngage,
            'taux_budget'             => $tauxBudget,
        ];

        // Performance par type d'événement
        $performanceTypes = Evenement::query()
            ->selectRaw('type_evenement_id, COUNT(*) as total')
            ->with('typeEvenement:id,nom,code')
            ->groupBy('type_evenement_id')
            ->get()
            ->map(fn ($e) => [
                'type'  => $e->typeEvenement?->nom ?? 'Inconnu',
                'code'  => $e->typeEvenement?->code,
                'total' => (int) $e->total,
            ])
            ->all();

        $derniersEvenements = Evenement::query()
            ->with(['typeEvenement:id,nom,code', 'lieu:id,nom'])
            ->whereIn('statut', ['publie', 'en_cours'])
            ->latest('date_debut')
            ->take(5)
            ->get()
            ->map(fn (Evenement $e) => [
                'id'         => $e->id,
                'titre'      => $e->titre,
                'date_debut' => optional($e->date_debut)?->toIso8601String(),
                'statut'     => $e->statut,
                'lieu'       => $e->lieu?->nom,
                'type'       => $e->typeEvenement ? [
                    'nom'  => $e->typeEvenement->nom,
                    'code' => $e->typeEvenement->code,
                ] : null,
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'role'                => 'responsable_dcirp',
            'kpis'                => $kpis,
            'statsRSE'            => $statsRSE,
            'performanceTypes'    => $performanceTypes,
            'derniersEvenements'  => $derniersEvenements,
        ]);
    }

    /**
     * Dashboard ORGANISATEUR - Vue opérationnelle.
     */
    private function organisateurDashboard(User $user): Response
    {
        $kpis = [
            'mes_evenements_actifs' => Evenement::where('created_by', $user->id)
                ->whereIn('statut', ['publie', 'en_cours'])
                ->count(),
            'dossiers_a_analyser' => Inscription::whereHas('evenement',
                fn ($q) => $q->where('created_by', $user->id))
                ->whereIn('statut', ['en_attente', 'en_analyse'])
                ->count(),
            'inscriptions_totales' => Inscription::whereHas('evenement',
                fn ($q) => $q->where('created_by', $user->id))
                ->count(),
            'evenements_brouillon' => Evenement::where('created_by', $user->id)
                ->where('statut', 'brouillon')
                ->count(),
        ];

        $mesEvenements = Evenement::query()
            ->with(['typeEvenement:id,nom,code', 'lieu:id,nom'])
            ->withCount('inscriptions')
            ->where('created_by', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Evenement $e) => [
                'id'                 => $e->id,
                'titre'              => $e->titre,
                'date_debut'         => optional($e->date_debut)?->toIso8601String(),
                'statut'             => $e->statut,
                'lieu'               => $e->lieu?->nom,
                'inscriptions_count' => $e->inscriptions_count,
                'type'               => $e->typeEvenement ? [
                    'nom'  => $e->typeEvenement->nom,
                    'code' => $e->typeEvenement->code,
                ] : null,
            ])->all();

        $dossiersRecents = Inscription::query()
            ->whereHas('evenement', fn ($q) => $q->where('created_by', $user->id))
            ->whereIn('statut', ['en_attente', 'en_analyse'])
            ->with(['user:id,nom,prenom,email', 'evenement:id,titre'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Inscription $i) => [
                'id'         => $i->id,
                'qr_code'    => $i->qr_code,
                'user'       => $i->user ? [
                    'prenom' => $i->user->prenom,
                    'nom'    => $i->user->nom,
                    'email'  => $i->user->email,
                ] : null,
                'evenement'  => $i->evenement?->titre,
                'statut'     => $i->statut,
                'created_at' => optional($i->created_at)?->toIso8601String(),
            ])->all();

        return Inertia::render('Dashboard', [
            'role'             => 'organisateur',
            'kpis'             => $kpis,
            'mesEvenements'    => $mesEvenements,
            'dossiersRecents'  => $dossiersRecents,
        ]);
    }

    /**
     * Dashboard PARTICIPANT - Vue personnelle.
     */
    private function participantDashboard(User $user): Response
    {
        $kpis = [
            'mes_inscriptions' => Inscription::where('user_id', $user->id)->count(),
            'a_venir'          => Inscription::where('user_id', $user->id)
                ->whereIn('statut', ['confirmee', 'acceptee'])
                ->whereHas('evenement', fn ($q) => $q->where('date_debut', '>=', now()))
                ->count(),
            'en_attente'       => Inscription::where('user_id', $user->id)
                ->whereIn('statut', ['en_attente', 'en_analyse'])
                ->count(),
        ];

        $mesProchainsEvenements = Inscription::query()
            ->with(['evenement.typeEvenement', 'evenement.lieu'])
            ->where('user_id', $user->id)
            ->whereIn('statut', ['confirmee', 'acceptee'])
            ->whereHas('evenement', fn ($q) => $q->where('date_debut', '>=', now()))
            ->get()
            ->map(fn (Inscription $i) => [
                'id'         => $i->id,
                'qr_code'    => $i->qr_code,
                'statut'     => $i->statut,
                'evenement'  => $i->evenement ? [
                    'id'         => $i->evenement->id,
                    'titre'      => $i->evenement->titre,
                    'date_debut' => optional($i->evenement->date_debut)?->toIso8601String(),
                    'lieu'       => $i->evenement->lieu?->nom,
                    'type'       => $i->evenement->typeEvenement ? [
                        'nom'  => $i->evenement->typeEvenement->nom,
                        'code' => $i->evenement->typeEvenement->code,
                    ] : null,
                ] : null,
            ])->all();

        $dejaInscrit = Inscription::where('user_id', $user->id)
            ->pluck('evenement_id')->all();

        $recommandes = Evenement::query()
            ->with(['typeEvenement:id,nom,code', 'lieu:id,nom'])
            ->where('statut', 'publie')
            ->where('date_debut', '>=', now())
            ->whereNotIn('id', $dejaInscrit)
            ->take(3)
            ->get()
            ->map(fn (Evenement $e) => [
                'id'         => $e->id,
                'titre'      => $e->titre,
                'date_debut' => optional($e->date_debut)?->toIso8601String(),
                'lieu'       => $e->lieu?->nom,
                'visuel_url' => $e->visuel ? asset('storage/' . $e->visuel) : null,
                'type'       => $e->typeEvenement ? [
                    'nom'  => $e->typeEvenement->nom,
                    'code' => $e->typeEvenement->code,
                ] : null,
            ])->all();

        return Inertia::render('Dashboard', [
            'role'                    => 'participant',
            'kpis'                    => $kpis,
            'mesProchainsEvenements'  => $mesProchainsEvenements,
            'recommandes'             => $recommandes,
        ]);
    }

    private function calculerTauxPresence(): float
    {
        $total    = Inscription::whereIn('statut', ['confirmee', 'present'])->count();
        $presents = Inscription::where('statut', 'present')->count();

        return $total > 0 ? round(($presents / $total) * 100, 1) : 0;
    }
}