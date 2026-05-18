<?php

namespace App\Services;

use App\Models\CandidatureBenevole;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\PosteBenevole;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyseService
{
    
    public function kpisGlobaux(?Carbon $debut = null, ?Carbon $fin = null): array
    {
        $queryEv = Evenement::query();
        if ($debut) $queryEv->where('date_debut', '>=', $debut);
        if ($fin)   $queryEv->where('date_debut', '<=', $fin);

        $totalEvenements = (clone $queryEv)->count();
        $evenementsPublies = (clone $queryEv)->where('statut', 'publie')->count();
        $evenementsTermines = (clone $queryEv)->where('statut', 'termine')->count();

        // Inscriptions sur la période
        $queryInsc = Inscription::query()
            ->when($debut || $fin, function ($q) use ($debut, $fin) {
                $q->whereHas('evenement', function ($qe) use ($debut, $fin) {
                    if ($debut) $qe->where('date_debut', '>=', $debut);
                    if ($fin)   $qe->where('date_debut', '<=', $fin);
                });
            });

        $totalInscriptions = (clone $queryInsc)->count();
        $totalAcceptes = (clone $queryInsc)->whereIn('statut', ['acceptee', 'confirmee', 'present'])->count();
        $totalPresent = (clone $queryInsc)->where('statut', 'present')->count();

        // Taux
        $tauxAcceptation = $totalInscriptions > 0
            ? round(($totalAcceptes / $totalInscriptions) * 100, 1)
            : 0;
        $tauxPresence = $totalAcceptes > 0
            ? round(($totalPresent / $totalAcceptes) * 100, 1)
            : 0;

        // Budget total
        $budgetTotal = (clone $queryEv)->sum('budget_prev') ?? 0;

        // Bénéficiaires RSE (cible totale)
        $cibleTotal = (clone $queryEv)->sum('cible_beneficiaires') ?? 0;

        // Postes bénévoles
        $totalPostes = PosteBenevole::query()
            ->when($debut || $fin, function ($q) use ($debut, $fin) {
                $q->whereHas('evenement', function ($qe) use ($debut, $fin) {
                    if ($debut) $qe->where('date_debut', '>=', $debut);
                    if ($fin)   $qe->where('date_debut', '<=', $fin);
                });
            })
            ->count();

        $benevolesAcceptes = CandidatureBenevole::query()
            ->where('statut', 'accepte')
            ->when($debut || $fin, function ($q) use ($debut, $fin) {
                $q->whereHas('poste.evenement', function ($qe) use ($debut, $fin) {
                    if ($debut) $qe->where('date_debut', '>=', $debut);
                    if ($fin)   $qe->where('date_debut', '<=', $fin);
                });
            })
            ->count();

        return [
            'total_evenements'      => $totalEvenements,
            'evenements_publies'    => $evenementsPublies,
            'evenements_termines'   => $evenementsTermines,
            'total_inscriptions'    => $totalInscriptions,
            'total_acceptes'        => $totalAcceptes,
            'total_present'         => $totalPresent,
            'taux_acceptation'      => $tauxAcceptation,
            'taux_presence'         => $tauxPresence,
            'budget_total'          => $budgetTotal,
            'cible_beneficiaires'   => $cibleTotal,
            'total_postes'          => $totalPostes,
            'benevoles_mobilises'   => $benevolesAcceptes,
        ];
    }

    public function repartitionParType(?Carbon $debut = null, ?Carbon $fin = null): Collection
    {
        $query = Evenement::query()
            ->select('type_evenement_id', DB::raw('count(*) as nombre'))
            ->with('typeEvenement:id,nom,code')
            ->groupBy('type_evenement_id');

        if ($debut) $query->where('date_debut', '>=', $debut);
        if ($fin)   $query->where('date_debut', '<=', $fin);

        return $query->get()->map(function ($item) {
            return [
                'type_id'    => $item->type_evenement_id,
                'type_nom'   => $item->typeEvenement?->nom ?? 'Sans type',
                'type_code'  => $item->typeEvenement?->code ?? 'X',
                'nombre'     => $item->nombre,
            ];
        });
    }

    
    public function evolutionMensuelle(int $mois = 12): array
    {
        $fin = Carbon::now()->endOfMonth();
        $debut = $fin->copy()->subMonths($mois - 1)->startOfMonth();

        $resultats = [];

        for ($d = $debut->copy(); $d <= $fin; $d->addMonth()) {
            $debutMois = $d->copy()->startOfMonth();
            $finMois = $d->copy()->endOfMonth();

            $nbEvenements = Evenement::whereBetween('date_debut', [$debutMois, $finMois])->count();

            $nbInscriptions = Inscription::whereHas('evenement', function ($q) use ($debutMois, $finMois) {
                $q->whereBetween('date_debut', [$debutMois, $finMois]);
            })->count();

            $resultats[] = [
                'mois'         => $d->format('Y-m'),
                'mois_label'   => $d->locale('fr')->isoFormat('MMM YY'),
                'evenements'   => $nbEvenements,
                'inscriptions' => $nbInscriptions,
            ];
        }

        return $resultats;
    }

    
    public function topEvenements(int $limit = 5): Collection
    {
        return Evenement::query()
            ->withCount('inscriptions')
            ->with(['typeEvenement:id,nom,code', 'lieu:id,nom'])
            ->orderByDesc('inscriptions_count')
            ->take($limit)
            ->get()
            ->map(fn ($e) => [
                'id'              => $e->id,
                'titre'           => $e->titre,
                'type_nom'        => $e->typeEvenement?->nom ?? '—',
                'lieu_nom'        => $e->lieu?->nom ?? '—',
                'inscriptions'    => $e->inscriptions_count,
                'date_debut'      => $e->date_debut,
                'statut'          => $e->statut,
            ]);
    }

    
    public function kpisEvenement(Evenement $evenement): array
    {
        $inscriptions = $evenement->inscriptions ?? collect();

        $totalInscriptions = $inscriptions->count();
        $totalAcceptes = $inscriptions->whereIn('statut', ['acceptee', 'confirmee', 'present'])->count();
        $totalPresent = $inscriptions->where('statut', 'present')->count();
        $totalRefuses = $inscriptions->where('statut', 'refusee')->count();
        $totalAnnules = $inscriptions->where('statut', 'annulee')->count();

        $tauxAcceptation = $totalInscriptions > 0
            ? round(($totalAcceptes / $totalInscriptions) * 100, 1) : 0;
        $tauxPresence = $totalAcceptes > 0
            ? round(($totalPresent / $totalAcceptes) * 100, 1) : 0;

        // Budget
        $depensesPrestataires = $evenement->prestataires?->sum(function ($p) {
            return $p->pivot->montant_final ?? $p->pivot->montant_prevu ?? 0;
        }) ?? 0;

        $budgetEngageVsPrevu = $evenement->budget_prev > 0
            ? round(($depensesPrestataires / $evenement->budget_prev) * 100, 1)
            : 0;

        // Logistique
        $nbMateriels = $evenement->materiels?->count() ?? 0;
        $nbPrestataires = $evenement->prestataires?->count() ?? 0;

        // Bénévoles
        $nbPostes = $evenement->postesBenevoles?->count() ?? 0;
        $nbBenevolesAcceptes = $evenement->postesBenevoles?->sum(function ($p) {
            return $p->candidatures->where('statut', 'accepte')->count();
        }) ?? 0;

        return [
            // Engagement
            'total_inscriptions' => $totalInscriptions,
            'total_acceptes'     => $totalAcceptes,
            'total_present'      => $totalPresent,
            'total_refuses'      => $totalRefuses,
            'total_annules'      => $totalAnnules,
            'taux_acceptation'   => $tauxAcceptation,
            'taux_presence'      => $tauxPresence,

            // Budget
            'budget_previsionnel'    => $evenement->budget_prev ?? 0,
            'depenses_prestataires'  => $depensesPrestataires,
            'budget_engage_pct'      => $budgetEngageVsPrevu,

            // RSE
            'cible_beneficiaires'    => $evenement->cible_beneficiaires ?? 0,
            'beneficiaires_reels'    => $totalPresent,
            'public_cible'           => $evenement->public_cible ?? '—',

            // Logistique
            'nb_materiels'           => $nbMateriels,
            'nb_prestataires'        => $nbPrestataires,
            'nb_postes_benevoles'    => $nbPostes,
            'nb_benevoles_acceptes'  => $nbBenevolesAcceptes,
        ];
    }

    
    public function statutsInscriptions(Evenement $evenement): array
    {
        $inscriptions = $evenement->inscriptions ?? collect();

        return [
            ['statut' => 'Pré-inscrits',     'nombre' => $inscriptions->where('statut', 'preinscrit')->count()],
            ['statut' => 'Présélectionnés',  'nombre' => $inscriptions->where('statut', 'preselectionne')->count()],
            ['statut' => 'Dossier soumis',   'nombre' => $inscriptions->where('statut', 'dossier_soumis')->count()],
            ['statut' => 'Acceptées',        'nombre' => $inscriptions->whereIn('statut', ['acceptee', 'confirmee'])->count()],
            ['statut' => 'Présents',         'nombre' => $inscriptions->where('statut', 'present')->count()],
            ['statut' => 'Refusées',         'nombre' => $inscriptions->where('statut', 'refusee')->count()],
            ['statut' => 'Annulées',         'nombre' => $inscriptions->where('statut', 'annulee')->count()],
        ];
    }
}