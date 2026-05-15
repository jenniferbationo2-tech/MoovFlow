<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\ObjectifRse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RapportController extends Controller
{
    private function StaffAccès(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp']),
            403,
            'Accès réservé au staff dCIRP.'
        );
    }

    /**
     * Page principale Impact RSE & Rapports.
     */
    public function index(Request $request): Response
    {
        $this->StaffAccès();

        // Filtres
        $evenementId = $request->input('evenement_id');
        $periode     = $request->input('periode', '12mois');

        // Construire la requête de base avec filtres
        $rseQuery = ObjectifRse::query();
        $eventsQuery = Evenement::query();
        $inscQuery = Inscription::query();

        if ($evenementId) {
            $rseQuery->where('evenement_id', $evenementId);
            $eventsQuery->where('id', $evenementId);
            $inscQuery->where('evenement_id', $evenementId);
        }

        if ($periode === '12mois') {
            $eventsQuery->where('date_debut', '>=', now()->subYear());
            $inscQuery->whereHas('evenement', fn ($q) => $q->where('date_debut', '>=', now()->subYear()));
        } elseif ($periode === '6mois') {
            $eventsQuery->where('date_debut', '>=', now()->subMonths(6));
            $inscQuery->whereHas('evenement', fn ($q) => $q->where('date_debut', '>=', now()->subMonths(6)));
        } elseif ($periode === '3mois') {
            $eventsQuery->where('date_debut', '>=', now()->subMonths(3));
            $inscQuery->whereHas('evenement', fn ($q) => $q->where('date_debut', '>=', now()->subMonths(3)));
        }

        // KPIs RSE globaux
        $totalBeneficiairesDirects   = (int) (clone $rseQuery)->sum('nb_beneficiaires_directs');
        $totalBeneficiairesIndirects = (int) (clone $rseQuery)->sum('nb_beneficiaires_indirects');
        $totalFemmes                 = (int) (clone $rseQuery)->sum('nb_femmes_beneficiaires');
        $totalAssociations           = (int) (clone $rseQuery)->sum('nb_associations_soutenues');
        $totalProjets                = (int) (clone $rseQuery)->sum('nb_projets_accompagnes');
        $totalEmplois                = (int) (clone $rseQuery)->sum('nb_emplois_crees');
        $totalCollectes              = (float) (clone $rseQuery)->sum('montants_collectes');
        $totalRetombees              = (float) (clone $rseQuery)->sum('retombees_partenaires');

        $tauxFemmes = $totalBeneficiairesDirects > 0
            ? round(($totalFemmes / $totalBeneficiairesDirects) * 100, 1)
            : 0;

        // Évolution mensuelle (12 derniers mois)
        $evolutionMensuelle = $this->calculerEvolutionMensuelle($evenementId);

        // Performance par type d'événement
        $performanceTypes = DB::table('evenements')
            ->join('types_evenement', 'types_evenement.id', '=', 'evenements.type_evenement_id')
            ->leftJoin('inscriptions', 'inscriptions.evenement_id', '=', 'evenements.id')
            ->selectRaw('
                types_evenement.nom as type_nom,
                types_evenement.code as type_code,
                COUNT(DISTINCT evenements.id) as nb_evenements,
                COUNT(DISTINCT inscriptions.id) as nb_inscriptions
            ')
            ->groupBy('types_evenement.id', 'types_evenement.nom', 'types_evenement.code')
            ->orderByDesc('nb_inscriptions')
            ->get()
            ->map(fn ($r) => [
                'type'           => $r->type_nom,
                'code'           => $r->type_code,
                'nb_evenements'  => (int) $r->nb_evenements,
                'nb_inscriptions'=> (int) $r->nb_inscriptions,
            ])
            ->all();

        // Top événements par impact
        $topEvenements = Evenement::query()
            ->with(['typeEvenement:id,nom,code', 'objectifsRse'])
            ->whereHas('objectifsRse')
            ->withCount('inscriptions')
            ->get()
            ->map(function (Evenement $e) {
                $rse = $e->objectifsRse->first();
                return [
                    'id'                => $e->id,
                    'titre'             => $e->titre,
                    'date_debut'        => optional($e->date_debut)?->toIso8601String(),
                    'type'              => $e->typeEvenement?->nom,
                    'type_code'         => $e->typeEvenement?->code,
                    'inscriptions_count'=> $e->inscriptions_count,
                    'beneficiaires'     => (int) ($rse?->nb_beneficiaires_directs ?? 0),
                ];
            })
            ->sortByDesc('beneficiaires')
            ->take(10)
            ->values()
            ->all();

        // Liste des événements pour filtre
        $evenements = Evenement::query()
            ->select('id', 'titre')
            ->orderBy('titre')
            ->get();

        return Inertia::render('Rapports/Index', [
            'kpis' => [
                'beneficiaires_directs'   => $totalBeneficiairesDirects,
                'beneficiaires_indirects' => $totalBeneficiairesIndirects,
                'femmes'                  => $totalFemmes,
                'taux_femmes'             => $tauxFemmes,
                'associations'            => $totalAssociations,
                'projets'                 => $totalProjets,
                'emplois'                 => $totalEmplois,
                'collectes'               => $totalCollectes,
                'retombees'               => $totalRetombees,
            ],
            'evolutionMensuelle' => $evolutionMensuelle,
            'performanceTypes'   => $performanceTypes,
            'topEvenements'      => $topEvenements,
            'evenements'         => $evenements,
            'filters'            => [
                'evenement_id' => $evenementId,
                'periode'      => $periode,
            ],
        ]);
    }

    /**
     * Évolution mensuelle des bénéficiaires sur 12 mois.
     */
    private function calculerEvolutionMensuelle(?int $evenementId = null): array
    {
        $months = collect(range(11, 0))->map(function ($i) {
            return now()->subMonths($i)->format('Y-m');
        });

        $query = ObjectifRse::query()
            ->join('evenements', 'evenements.id', '=', 'objectifs_rse.evenement_id')
            ->selectRaw('DATE_FORMAT(evenements.date_debut, "%Y-%m") as mois, SUM(nb_beneficiaires_directs) as total')
            ->whereNotNull('evenements.date_debut')
            ->where('evenements.date_debut', '>=', now()->subYear())
            ->groupBy('mois');

        if ($evenementId) {
            $query->where('evenements.id', $evenementId);
        }

        $data = $query->pluck('total', 'mois');

        return $months->map(function ($mois) use ($data) {
            $date = \Carbon\Carbon::createFromFormat('Y-m', $mois);
            return [
                'mois'    => $date->locale('fr')->isoFormat('MMM'),
                'mois_full'=> $date->format('Y-m'),
                'total'   => (int) ($data[$mois] ?? 0),
            ];
        })->all();
    }

    /**
     * Export PDF du rapport global.
     */
    public function exportGlobal(Request $request): HttpResponse
    {
        $this->StaffAccès();

        $totalBeneficiairesDirects   = (int) ObjectifRse::sum('nb_beneficiaires_directs');
        $totalFemmes                 = (int) ObjectifRse::sum('nb_femmes_beneficiaires');
        $totalAssociations           = (int) ObjectifRse::sum('nb_associations_soutenues');
        $totalProjets                = (int) ObjectifRse::sum('nb_projets_accompagnes');
        $totalEmplois                = (int) ObjectifRse::sum('nb_emplois_crees');
        $totalCollectes              = (float) ObjectifRse::sum('montants_collectes');

        $tauxFemmes = $totalBeneficiairesDirects > 0
            ? round(($totalFemmes / $totalBeneficiairesDirects) * 100, 1)
            : 0;

        $evenements = Evenement::query()
            ->with(['typeEvenement', 'lieu', 'objectifsRse'])
            ->whereHas('objectifsRse')
            ->orderByDesc('date_debut')
            ->get();

        $performanceTypes = DB::table('evenements')
            ->join('types_evenement', 'types_evenement.id', '=', 'evenements.type_evenement_id')
            ->leftJoin('inscriptions', 'inscriptions.evenement_id', '=', 'evenements.id')
            ->selectRaw('
                types_evenement.nom as type_nom,
                COUNT(DISTINCT evenements.id) as nb_evenements,
                COUNT(DISTINCT inscriptions.id) as nb_inscriptions
            ')
            ->groupBy('types_evenement.id', 'types_evenement.nom')
            ->orderByDesc('nb_inscriptions')
            ->get();

        $pdf = Pdf::loadView('rapports.global-pdf', [
            'date_generation' => now()->locale('fr')->isoFormat('DD MMMM YYYY'),
            'kpis' => [
                'beneficiaires_directs' => $totalBeneficiairesDirects,
                'femmes'                => $totalFemmes,
                'taux_femmes'           => $tauxFemmes,
                'associations'          => $totalAssociations,
                'projets'               => $totalProjets,
                'emplois'               => $totalEmplois,
                'collectes'             => $totalCollectes,
            ],
            'evenements'       => $evenements,
            'performanceTypes' => $performanceTypes,
            'genere_par'       => Auth::user(),
        ]);

        return $pdf->download('rapport-impact-rse-' . now()->format('Y-m-d') . '.pdf');
    }
}