<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\ObjectifRse;
use App\Services\AnalyseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnalyseController extends Controller
{
    public function __construct(
        private readonly AnalyseService $service,
    ) {}

    /**
     * Dashboard global - vision macro de tous les événements.
     */
    public function dashboardGlobal(Request $request): Response
    {
        $this->authorizeAccess();

        // Filtre par période (par défaut : toute l'année en cours)
        $debut = $request->filled('debut')
            ? Carbon::parse($request->debut)
            : Carbon::now()->startOfYear();

        $fin = $request->filled('fin')
            ? Carbon::parse($request->fin)
            : Carbon::now()->endOfYear();

        return Inertia::render('Analyse/DashboardGlobal', [
            'kpis'              => $this->service->kpisGlobaux($debut, $fin),
            'repartitionType'   => $this->service->repartitionParType($debut, $fin),
            'evolutionMensuelle'=> $this->service->evolutionMensuelle(12),
            'topEvenements'     => $this->service->topEvenements(5),
            'filters'           => [
                'debut' => $debut->format('Y-m-d'),
                'fin'   => $fin->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Dashboard d'un événement spécifique.
     */
    public function dashboardEvenement(Evenement $evenement): Response
    {
        $this->authorizeAccessEvenement($evenement);

        $evenement->load([
            'typeEvenement',
            'lieu',
            'inscriptions',
            'materiels',
            'prestataires',
            'postesBenevoles.candidatures',
        ]);

        $user = Auth::user();
        $peutModifierRse = $user->hasRole('responsable_dcirp')
            || ($user->hasRole('organisateur') && $evenement->created_by === $user->id);

        return Inertia::render('Evenements/Dashboard', [
            'evenement'            => $evenement,
            'kpis'                 => $this->service->kpisEvenement($evenement),
            'statutsInscriptions'  => $this->service->statutsInscriptions($evenement),
            'objectifRse'          => ObjectifRse::where('evenement_id', $evenement->id)->first(),
            'peutModifierRse'      => $peutModifierRse,
        ]);
    }

    /**
     * Enregistre (crée ou met à jour) le bilan d'impact RSE d'un événement.
     */
    public function updateObjectifsRse(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeAccessEvenement($evenement);

        $validated = $request->validate([
            'type_impact'                => ['required', 'string', 'max:100'],
            'nb_beneficiaires_directs'   => ['required', 'integer', 'min:0'],
            'nb_beneficiaires_indirects' => ['nullable', 'integer', 'min:0'],
            'nb_femmes_beneficiaires'    => ['nullable', 'integer', 'min:0'],
            'nb_associations_soutenues'  => ['nullable', 'integer', 'min:0'],
            'nb_projets_accompagnes'     => ['nullable', 'integer', 'min:0'],
            'nb_emplois_crees'           => ['nullable', 'integer', 'min:0'],
            'montants_collectes'         => ['nullable', 'numeric', 'min:0'],
            'retombees_partenaires'      => ['nullable', 'numeric', 'min:0'],
            'score_environnemental'      => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Le nombre de femmes bénéficiaires ne peut pas dépasser le total de bénéficiaires directs
        if (($validated['nb_femmes_beneficiaires'] ?? 0) > $validated['nb_beneficiaires_directs']) {
            return back()->withErrors([
                'nb_femmes_beneficiaires' => 'Ne peut pas dépasser le nombre de bénéficiaires directs.',
            ])->withInput();
        }

        ObjectifRse::updateOrCreate(
            ['evenement_id' => $evenement->id],
            $validated
        );

        return back()->with('success', 'Bilan d\'impact RSE enregistré avec succès.');
    }

    /**
     * Rapport RSE sur période.
     */
    public function rapportRse(Request $request): Response
    {
        $this->authorizeAccess();

        // Filtre période (par défaut : année en cours)
        $debut = $request->filled('debut')
            ? Carbon::parse($request->debut)
            : Carbon::now()->startOfYear();

        $fin = $request->filled('fin')
            ? Carbon::parse($request->fin)
            : Carbon::now()->endOfYear();

        // Liste des événements RSE de la période
        $evenements = Evenement::query()
            ->whereBetween('date_debut', [$debut, $fin])
            ->with(['typeEvenement:id,nom,code', 'lieu:id,nom'])
            ->withCount(['inscriptions', 'inscriptions as inscriptions_acceptees_count' => function ($q) {
                $q->whereIn('statut', ['acceptee', 'confirmee', 'present']);
            }])
            ->orderBy('date_debut', 'desc')
            ->get();

        return Inertia::render('Analyse/RapportRse', [
            'kpis'              => $this->service->kpisGlobaux($debut, $fin),
            'repartitionType'   => $this->service->repartitionParType($debut, $fin),
            'evenements'        => $evenements,
            'filters'           => [
                'debut' => $debut->format('Y-m-d'),
                'fin'   => $fin->format('Y-m-d'),
            ],
            'periode' => [
                'debut_label' => $debut->locale('fr')->isoFormat('D MMMM Y'),
                'fin_label'   => $fin->locale('fr')->isoFormat('D MMMM Y'),
            ],
        ]);
    }

    // ════════════════════════════════════════════
    //   PERMISSIONS
    // ════════════════════════════════════════════

    private function authorizeAccess(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['responsable_dcirp', 'organisateur']),
            403,
            'Accès réservé au staff.'
        );
    }

    private function authorizeAccessEvenement(Evenement $evenement): void
    {
        $user = Auth::user();

        if ($user->hasRole('responsable_dcirp')) {
            return;
        }

        if ($user->hasRole('organisateur') && $evenement->created_by === $user->id) {
            return;
        }

        abort(403, 'Vous ne pouvez voir le dashboard que de vos propres événements.');
    }
}