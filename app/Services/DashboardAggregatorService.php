<?php

namespace App\Services;

use App\Models\Enquete;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Presence;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardAggregatorService
{
    public function __construct(
        private readonly RseMetricsService $rseMetricsService
    ) {
    }

    /**
     * Retourne les statistiques globales du tableau de bord.
     *
     * @return array<string, mixed>
     */
    public function getGlobalStats(): array
    {
        $events = Evenement::query()
            ->with(['typeEvenement', 'budgets.lignesBudget', 'objectifsRse'])
            ->withCount('inscriptions')
            ->latest('date_debut')
            ->get();

        $totalInscrits = Inscription::query()->count();
        $participantsPresents = Presence::query()->count();
        $tauxPresence = $totalInscrits > 0 ? round(($participantsPresents / $totalInscrits) * 100, 1) : 0;
        $budgetPrevisionnel = round((float) $events->sum(fn (Evenement $evenement) => (float) ($evenement->budgets->first()?->montant_previsionnel ?? $evenement->budget_prev ?? 0)), 2);
        $budgetDepense = round((float) $events->sum(function (Evenement $evenement): float {
            $budget = $evenement->budgets->first();

            if (! $budget) {
                return 0;
            }

            return (float) $budget->lignesBudget->where('type', 'depense')->sum('montant');
        }), 2);

        $rseScores = $events
            ->filter(fn (Evenement $evenement) => $evenement->objectifsRse->isNotEmpty())
            ->map(fn (Evenement $evenement): float => (float) $this->rseMetricsService->calculate($evenement)['score_global']);

        return [
            'cards' => [
                'evenements_actifs' => $events->whereIn('statut', ['publie', 'en_cours'])->count(),
                'total_inscrits' => $totalInscrits,
                'taux_presence' => $tauxPresence,
                'budget_total' => [
                    'previsionnel' => $budgetPrevisionnel,
                    'depense' => $budgetDepense,
                ],
                'score_rse_moyen' => $rseScores->isNotEmpty() ? round((float) $rseScores->avg(), 1) : 0,
                'enquetes_en_cours' => Enquete::query()->whereIn('statut', ['brouillon', 'publie'])->count(),
            ],
            'stats' => [
                'events_by_status' => $events
                    ->groupBy('statut')
                    ->map(fn (Collection $items, string $status): array => [
                        'label' => $this->formatStatus($status),
                        'value' => $items->count(),
                    ])
                    ->values()
                    ->all(),
                'events_by_type' => $events
                    ->groupBy(fn (Evenement $evenement) => $evenement->typeEvenement?->nom ?? 'Non defini')
                    ->map(fn (Collection $items, string $type): array => [
                        'label' => $type,
                        'value' => $items->count(),
                    ])
                    ->values()
                    ->all(),
                'total_registered' => $totalInscrits,
                'participants_present' => $participantsPresents,
                'taux_presence' => $tauxPresence,
                'budget_total' => [
                    'previsionnel' => $budgetPrevisionnel,
                    'depense' => $budgetDepense,
                ],
                'monthly_trends' => $this->getMonthlyTrends(),
            ],
            'latest_events' => $events
                ->take(5)
                ->map(fn (Evenement $evenement): array => [
                    'id' => $evenement->id,
                    'titre' => $evenement->titre,
                    'statut' => $evenement->statut,
                    'type' => $evenement->typeEvenement?->nom ?? 'Non defini',
                    'date_debut' => optional($evenement->date_debut)?->toIso8601String(),
                    'date_fin' => optional($evenement->date_fin)?->toIso8601String(),
                    'inscriptions_count' => $evenement->inscriptions_count,
                ])
                ->values()
                ->all(),
            'recent_activity' => $this->recentActivity(),
        ];
    }

    /**
     * Retourne les statistiques détaillées d'un événement.
     *
     * @return array<string, mixed>
     */
    public function getEventStats(Evenement $evenement): array
    {
        $evenement->loadMissing([
            'typeEvenement',
            'inscriptions.user',
            'inscriptions.presence',
            'inscriptions.paiements',
            'budgets.lignesBudget',
            'objectifsRse',
            'enquetes.reponses',
            'projets',
        ]);

        $inscriptions = $evenement->inscriptions;
        $presents = $inscriptions->filter(fn ($inscription) => $inscription->presence !== null)->count();
        $inscrits = $inscriptions->count();
        $absents = max($inscrits - $presents, 0);
        $budget = $evenement->budgets->first();
        $recettesPaiements = round((float) $inscriptions
            ->flatMap(fn ($inscription) => $inscription->paiements)
            ->where('statut', 'paye')
            ->sum('montant'), 2);
        $budgetRecettes = round((float) ($budget?->lignesBudget->where('type', 'recette')->sum('montant') ?? 0), 2);
        $depenses = round((float) ($budget?->lignesBudget->where('type', 'depense')->sum('montant') ?? 0), 2);
        $previsionnel = round((float) ($budget?->montant_previsionnel ?? $evenement->budget_prev ?? 0), 2);
        $rse = $this->rseMetricsService->calculate($evenement);

        return [
            'participation' => [
                'inscrits' => $inscrits,
                'presents' => $presents,
                'absents' => $absents,
                'taux_presence' => $inscrits > 0 ? round(($presents / $inscrits) * 100, 1) : 0,
                'participants' => $inscriptions->map(function ($inscription): array {
                    return [
                        'id' => $inscription->id,
                        'nom' => $inscription->user?->name ?? 'Participant',
                        'email' => $inscription->user?->email,
                        'telephone' => $inscription->user?->telephone,
                        'statut_inscription' => $inscription->statut,
                        'presence' => $inscription->presence ? 'present' : 'absent',
                        'scan_time' => optional($inscription->presence?->scan_time)?->toIso8601String(),
                    ];
                })->values()->all(),
            ],
            'financier' => [
                'budget_previsionnel' => $previsionnel,
                'recettes_paiements' => $recettesPaiements,
                'recettes_lignes_budget' => $budgetRecettes,
                'recettes_total' => round($recettesPaiements + $budgetRecettes, 2),
                'depenses' => $depenses,
                'solde' => round(($recettesPaiements + $budgetRecettes) - $depenses, 2),
                'depassement' => $depenses > $previsionnel,
                'budget' => $budget ? [
                    'id' => $budget->id,
                    'devise' => $budget->devise,
                    'lignes' => $budget->lignesBudget->map(fn ($ligne): array => [
                        'id' => $ligne->id,
                        'libelle' => $ligne->libelle,
                        'montant' => (float) $ligne->montant,
                        'type' => $ligne->type,
                    ])->values()->all(),
                ] : [
                    'id' => null,
                    'devise' => 'XOF',
                    'lignes' => [],
                ],
            ],
            'rse' => $rse,
            'satisfaction' => [
                'enquetes_count' => $evenement->enquetes->count(),
                'publiees_count' => $evenement->enquetes->where('statut', 'publie')->count(),
            ],
        ];
    }

    /**
     * Retourne l'évolution mensuelle des inscriptions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMonthlyTrends(int $months = 12): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $rows = Inscription::query()
            ->selectRaw('YEAR(created_at) as annee, MONTH(created_at) as mois, COUNT(*) as total')
            ->where('created_at', '>=', $start)
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at), MONTH(created_at)')
            ->get()
            ->keyBy(fn ($row) => sprintf('%04d-%02d', $row->annee, $row->mois));

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($start, $rows): array {
                $date = $start->copy()->addMonths($offset);
                $key = $date->format('Y-m');
                $value = $rows->get($key);

                return [
                    'month' => $date->translatedFormat('M Y'),
                    'value' => (int) ($value->total ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(): array
    {
        $events = Evenement::query()
            ->latest()
            ->take(3)
            ->get()
            ->map(fn (Evenement $evenement): array => [
                'type' => 'evenement',
                'label' => 'Événement créé',
                'description' => $evenement->titre,
                'date' => optional($evenement->created_at)?->toIso8601String(),
            ]);

        $inscriptions = Inscription::query()
            ->with(['user', 'evenement'])
            ->latest()
            ->take(3)
            ->get()
            ->map(fn (Inscription $inscription): array => [
                'type' => 'inscription',
                'label' => 'Nouvelle inscription',
                'description' => ($inscription->user?->name ?? 'Participant').' · '.($inscription->evenement?->titre ?? 'Événement'),
                'date' => optional($inscription->created_at)?->toIso8601String(),
            ]);

        $paiements = Paiement::query()
            ->with('inscription.evenement')
            ->latest()
            ->take(3)
            ->get()
            ->map(fn (Paiement $paiement): array => [
                'type' => 'paiement',
                'label' => 'Paiement mis à jour',
                'description' => ($paiement->inscription?->evenement?->titre ?? 'Événement').' · '.strtoupper($paiement->statut),
                'date' => optional($paiement->updated_at)?->toIso8601String(),
            ]);

        return $events
            ->concat($inscriptions)
            ->concat($paiements)
            ->sortByDesc('date')
            ->take(8)
            ->values()
            ->all();
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'brouillon' => 'Brouillon',
            'publie' => 'Publié',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'annule' => 'Annulé',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}