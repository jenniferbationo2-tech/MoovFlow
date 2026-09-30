<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\ObjectifRse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\TypeEvenement;
use App\Models\CommunicationCampaign;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
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
     * Dashboard ADMIN - rôle technique : utilisateurs, sécurité, journal d'activité.
     * L'admin ne gère aucun domaine métier (événements, RSE, logistique...).
     */
    private function adminDashboard(User $user): Response
    {
        $kpis = [
            'utilisateurs_actifs' => User::where('is_active', true)->count(),
            'comptes_bloques'     => User::whereNotNull('bloque_jusqu_a')
                ->where('bloque_jusqu_a', '>', now())
                ->count(),
            'nouveaux_7j'         => User::where('created_at', '>=', now()->subDays(7))->count(),
            'actions_24h'         => \Spatie\Activitylog\Models\Activity::where('created_at', '>=', now()->subDay())->count(),
        ];

        // ── JOURNAL D'ACTIVITÉ RÉCENT ─────────────────
        $activiteRecente = \Spatie\Activitylog\Models\Activity::query()
            ->with('causer:id,nom,prenom')
            ->latest()
            ->take(8)
            ->get()
            ->map(fn ($a) => [
                'id'          => $a->id,
                'description' => $a->description,
                'log_name'    => $a->log_name,
                'event'       => $a->event,
                'causer'      => $a->causer ? "{$a->causer->prenom} {$a->causer->nom}" : 'Système',
                'created_at'  => optional($a->created_at)?->toIso8601String(),
            ])
            ->all();

        // ── RÉPARTITION DES UTILISATEURS PAR RÔLE ─────
        $repartitionRoles = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('roles.name', DB::raw('COUNT(*) as total'))
            ->groupBy('roles.name')
            ->get()
            ->map(fn ($r) => ['role' => $r->name, 'total' => (int) $r->total])
            ->all();

        return Inertia::render('Dashboard', [
            'role'             => 'admin',
            'kpis'             => $kpis,
            'activiteRecente'  => $activiteRecente,
            'repartitionRoles' => $repartitionRoles,
        ]);
    }

    
    private function responsableDashboard(User $user): Response
    {
        $totalBeneficiaires = (int) ObjectifRse::sum('nb_beneficiaires_directs');
        $totalFemmes        = (int) ObjectifRse::sum('nb_femmes_beneficiaires');
        $totalAssociations  = (int) ObjectifRse::sum('nb_associations_soutenues');
        $tauxFemmes         = $totalBeneficiaires > 0
            ? round(($totalFemmes / $totalBeneficiaires) * 100, 1)
            : 0;

        // Enveloppe budgétaire globale RSE : un plafond annuel fixé par le responsable,
        // dans lequel chaque événement (hors brouillons et annulés) puise son prévisionnel.
        $enveloppeRse = (float) \App\Models\Setting::get('budget_enveloppe_rse', 0);
        $budgetEngage = (float) Evenement::whereNotIn('statut', ['annule', 'brouillon'])->sum('budget_prev');
        $tauxBudget   = $enveloppeRse > 0 ? round(($budgetEngage / $enveloppeRse) * 100) : 0;

        $kpis = [
            'evenements_en_cours' => Evenement::where('statut', 'en_cours')->count(),
            'dossiers_a_valider'  => Inscription::whereIn('statut', [
                Inscription::STATUT_PREINSCRIT,
                Inscription::STATUT_DOSSIER_SOUMIS,
                Inscription::STATUT_EN_ANALYSE,
                Inscription::STATUT_RECOMMANDEE,
            ])->count(),
            'taux_presence'       => $this->calculerTauxPresence(),
            'beneficiaires'       => $totalBeneficiaires,
        ];

        $statsRSE = [
            'beneficiaires_directs'   => $totalBeneficiaires,
            'taux_femmes'             => $tauxFemmes,
            'cible_femmes'            => 60,
            'associations_soutenues'  => $totalAssociations,
            'budget_engage'           => $budgetEngage,
            'budget_enveloppe'        => $enveloppeRse,
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
            'repartitionTypes'    => $this->getRepartitionTypesEvenements(),
        ]);
    }

    /**
     * Définit le plafond de l'enveloppe budgétaire globale RSE.
     */
    public function updateEnveloppeRse(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless(Auth::user()->hasRole('responsable_dcirp'), 403);

        $validated = $request->validate([
            'budget_enveloppe_rse' => ['required', 'numeric', 'min:0'],
        ]);

        \App\Models\Setting::set('budget_enveloppe_rse', (string) $validated['budget_enveloppe_rse']);

        return back()->with('success', 'Enveloppe budgétaire mise à jour.');
    }


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

    
    private function getRepartitionTypesEvenements(): array
    {
        return TypeEvenement::query()
            ->withCount(['evenements as total' => function ($q) {
                $q->whereIn('statut', ['publie', 'en_cours', 'termine']);
            }])
            ->orderByDesc('total')
            ->get()
            ->map(fn ($t) => [
                'code'  => $t->code,
                'nom'   => $t->nom,
                'total' => (int) $t->total,
            ])
            ->all();
    }
}