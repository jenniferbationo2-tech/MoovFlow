<?php

namespace App\Http\Controllers;

use App\Models\Enquete;
use App\Models\Evenement;
use App\Models\ReponseEnquete;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SatisfactionController extends Controller
{
    /**
     * Affiche les résultats agrégés de satisfaction pour un événement.
     */
    public function index(Evenement $evenement): Response
    {
        $evenement->load(['enquetes.reponses', 'enquetes.evenement']);
        $enquete = $evenement->enquetes->sortByDesc('created_at')->first();

        return Inertia::render('Rapports/Satisfaction', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'overview' => [
                'enquetes' => $evenement->enquetes->map(fn (Enquete $item): array => [
                    'id' => $item->id,
                    'titre' => $item->titre,
                    'type' => $item->type,
                    'statut' => $item->statut,
                    'reponses_count' => $item->reponses->count(),
                ])->values()->all(),
            ],
            'analyse' => $enquete ? $this->buildSurveyAnalysis($enquete) : $this->emptyAnalysis(),
        ]);
    }

    /**
     * Affiche l'analyse détaillée d'une enquête avec graphiques.
     */
    public function analyse(Enquete $enquete): Response
    {
        $enquete->load(['evenement', 'reponses.user']);

        return Inertia::render('Rapports/Satisfaction', [
            'evenement' => [
                'id' => $enquete->evenement?->id,
                'titre' => $enquete->evenement?->titre,
            ],
            'overview' => [
                'enquetes' => $enquete->evenement?->enquetes()
                    ->withCount('reponses')
                    ->latest()
                    ->get()
                    ->map(fn (Enquete $item): array => [
                        'id' => $item->id,
                        'titre' => $item->titre,
                        'type' => $item->type,
                        'statut' => $item->statut,
                        'reponses_count' => $item->reponses_count,
                    ])
                    ->values()
                    ->all() ?? [],
            ],
            'analyse' => $this->buildSurveyAnalysis($enquete),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSurveyAnalysis(Enquete $enquete): array
    {
        $reponses = $enquete->relationLoaded('reponses')
            ? $enquete->reponses
            : $enquete->reponses()->get();

        $questionCharts = collect($enquete->questions ?? [])
            ->map(fn (array $question): array => $this->buildQuestionChart($question, $reponses));
        $notes = $questionCharts
            ->where('kind', 'rating')
            ->pluck('average')
            ->filter(fn ($value) => $value !== null);
        $satisfactionGlobale = $notes->isNotEmpty()
            ? round(($notes->avg() / 5) * 100, 1)
            : 0;

        $verbatims = $reponses
            ->flatMap(function (ReponseEnquete $reponse) use ($enquete) {
                return collect($enquete->questions ?? [])
                    ->filter(fn (array $question) => ($question['type'] ?? null) === 'texte')
                    ->map(fn (array $question) => (string) data_get($reponse->reponses, $question['id'], ''))
                    ->filter();
            })
            ->values();

        return [
            'enquete' => [
                'id' => $enquete->id,
                'titre' => $enquete->titre,
                'type' => $enquete->type,
                'statut' => $enquete->statut,
                'questions' => $enquete->questions ?? [],
                'reponses_count' => $reponses->count(),
            ],
            'satisfaction_globale' => $satisfactionGlobale,
            'question_charts' => $questionCharts->values()->all(),
            'points_forts' => $this->detectStrengths($questionCharts, $verbatims),
            'points_amelioration' => $this->detectWeaknesses($questionCharts, $verbatims),
        ];
    }

    /**
     * @param array<string, mixed> $question
     * @param Collection<int, ReponseEnquete> $reponses
     * @return array<string, mixed>
     */
    private function buildQuestionChart(array $question, Collection $reponses): array
    {
        $values = $reponses
            ->map(fn (ReponseEnquete $reponse) => data_get($reponse->reponses, $question['id']))
            ->filter(fn ($value) => $value !== null && $value !== '');

        if (($question['type'] ?? null) === 'note') {
            $counts = $values
                ->map(fn ($value) => (string) $value)
                ->countBy()
                ->sortKeys();

            return [
                'question_id' => $question['id'],
                'label' => $question['label'],
                'kind' => 'rating',
                'type' => 'bar',
                'labels' => $counts->keys()->values()->all(),
                'datasets' => [[
                    'label' => $question['label'],
                    'backgroundColor' => '#0066B3',
                    'data' => $counts->values()->all(),
                ]],
                'average' => $values->isNotEmpty() ? round((float) $values->avg(), 1) : null,
            ];
        }

        if (($question['type'] ?? null) === 'choix_multiple') {
            $counts = $values->countBy();

            return [
                'question_id' => $question['id'],
                'label' => $question['label'],
                'kind' => 'choice',
                'type' => 'pie',
                'labels' => $counts->keys()->values()->all(),
                'datasets' => [[
                    'label' => $question['label'],
                    'backgroundColor' => ['#0066B3', '#00A651', '#F59E0B', '#8B5CF6', '#EF4444'],
                    'data' => $counts->values()->all(),
                ]],
                'average' => null,
            ];
        }

        return [
            'question_id' => $question['id'],
            'label' => $question['label'],
            'kind' => 'text',
            'type' => 'list',
            'entries' => $values->values()->all(),
            'average' => null,
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $questionCharts
     * @param Collection<int, string> $verbatims
     * @return array<int, string>
     */
    private function detectStrengths(Collection $questionCharts, Collection $verbatims): array
    {
        $strengths = $questionCharts
            ->where('kind', 'rating')
            ->filter(fn (array $chart) => ($chart['average'] ?? 0) >= 4)
            ->map(fn (array $chart) => $chart['label'].' · moyenne '.number_format((float) $chart['average'], 1, ',', ' ').'/5')
            ->values();

        if ($verbatims->contains(fn (string $entry) => str_contains(mb_strtolower($entry), 'excellent') || str_contains(mb_strtolower($entry), 'bonne'))) {
            $strengths->push('Les verbatims font ressortir une perception globalement positive de l’organisation.');
        }

        return $strengths->take(4)->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $questionCharts
     * @param Collection<int, string> $verbatims
     * @return array<int, string>
     */
    private function detectWeaknesses(Collection $questionCharts, Collection $verbatims): array
    {
        $weaknesses = $questionCharts
            ->where('kind', 'rating')
            ->filter(fn (array $chart) => ($chart['average'] ?? 5) < 3.5)
            ->map(fn (array $chart) => $chart['label'].' · moyenne '.number_format((float) $chart['average'], 1, ',', ' ').'/5')
            ->values();

        if ($verbatims->contains(fn (string $entry) => str_contains(mb_strtolower($entry), 'retard') || str_contains(mb_strtolower($entry), 'amelior'))) {
            $weaknesses->push('Plusieurs réponses textuelles suggèrent des axes d’amélioration opérationnels.');
        }

        return $weaknesses->take(4)->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAnalysis(): array
    {
        return [
            'enquete' => null,
            'satisfaction_globale' => 0,
            'question_charts' => [],
            'points_forts' => [],
            'points_amelioration' => [],
        ];
    }
}