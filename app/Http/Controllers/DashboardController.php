<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\ObjectifRse;
use App\Services\DashboardAggregatorService;
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
        // Données globales du service existant
        $globalStats = $this->dashboardAggregatorService->getGlobalStats();

        // 🆕 KPIs RSE pour le dashboard pro
        $totalBeneficiaires    = (int) ObjectifRse::sum('nb_beneficiaires_directs');
        $totalFemmes           = (int) ObjectifRse::sum('nb_femmes_beneficiaires');
        $totalAssociations     = (int) ObjectifRse::sum('nb_associations_soutenues');
        $tauxFemmes            = $totalBeneficiaires > 0
            ? round(($totalFemmes / $totalBeneficiaires) * 100, 1)
            : 0;
        $budgetEngage          = (float) ($globalStats['cards']['budget_total']['depense'] ?? 0);
        $budgetTotal           = (float) ($globalStats['cards']['budget_total']['previsionnel'] ?? 0);
        $tauxBudget            = $budgetTotal > 0 ? round(($budgetEngage / $budgetTotal) * 100) : 0;

        // 🆕 5 derniers événements
        $derniersEvenements = Evenement::query()
            ->with(['typeEvenement', 'lieu'])
            ->whereIn('statut', ['publie', 'en_cours', 'brouillon'])
            ->latest('created_at')
            ->take(5)
            ->get()
            ->map(fn (Evenement $ev): array => [
                'id'              => $ev->id,
                'titre'           => $ev->titre,
                'date_debut'      => optional($ev->date_debut)?->toIso8601String(),
                'statut'          => $ev->statut,
                'lieu'            => $ev->lieu ? ['nom' => $ev->lieu->nom] : null,
                'type_evenement'  => $ev->typeEvenement ? [
                    'nom'  => $ev->typeEvenement->nom,
                    'code' => $ev->typeEvenement->code,
                ] : null,
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'stats' => [
                'beneficiaires_directs'    => $totalBeneficiaires,
                'croissance_beneficiaires' => 12, // Placeholder, à calculer si besoin
                'taux_femmes'              => $tauxFemmes,
                'cible_femmes'             => 60,
                'associations_soutenues'   => $totalAssociations,
                'budget_engage'            => $budgetEngage,
                'taux_budget'              => $tauxBudget,
            ],
            'derniers_evenements' => $derniersEvenements,
            // Garde les autres données du service
            'cards' => $globalStats['cards'] ?? [],
        ]);
    }
}