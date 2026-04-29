<?php

namespace App\Http\Controllers;

use App\Models\Enquete;
use App\Models\Evenement;
use App\Models\ReponseEnquete;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EnqueteController extends Controller
{
    /**
     * Affiche la liste des enquêtes d'un événement.
     */
    public function index(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('communication.view'), 403);

        $totalParticipants = $evenement->inscriptions()->count();

        $enquetes = $evenement->enquetes()
            ->withCount('reponses')
            ->latest()
            ->get()
            ->map(fn (Enquete $enquete): array => [
                'id' => $enquete->id,
                'titre' => $enquete->titre,
                'type' => $enquete->type,
                'statut' => $enquete->statut,
                'questions_count' => count($enquete->questions ?? []),
                'reponses_count' => $enquete->reponses_count,
                'taux_reponse' => $totalParticipants > 0 ? round(($enquete->reponses_count / $totalParticipants) * 100, 1) : 0,
                'created_at' => optional($enquete->created_at)?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('Communication/Enquetes/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
                'participants_count' => $totalParticipants,
            ],
            'enquetes' => $enquetes,
            'types' => $this->types(),
        ]);
    }

    /**
     * Affiche le formulaire de création d'enquête.
     */
    public function create(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('communication.manage'), 403);

        return Inertia::render('Communication/Enquetes/Create', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'types' => $this->types(),
            'questionTypes' => $this->questionTypes(),
        ]);
    }

    /**
     * Enregistre une nouvelle enquête.
     */
    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        abort_unless($request->user()?->can('communication.manage'), 403);

        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_column($this->types(), 'value'))],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['required', 'string', 'max:100'],
            'questions.*.label' => ['required', 'string', 'max:255'],
            'questions.*.type' => ['required', Rule::in(array_column($this->questionTypes(), 'value'))],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable', 'string', 'max:255'],
        ]);

        $enquete = $evenement->enquetes()->create([
            'titre' => $validated['titre'],
            'type' => $validated['type'],
            'questions' => collect($validated['questions'])->map(function (array $question): array {
                return [
                    'id' => $question['id'],
                    'label' => $question['label'],
                    'type' => $question['type'],
                    'options' => array_values(array_filter($question['options'] ?? [])),
                ];
            })->values()->all(),
            'statut' => 'brouillon',
        ]);

        return redirect()
            ->route('communication.enquetes.show', ['evenement' => $evenement->id, 'enquete' => $enquete->id])
            ->with('success', 'Enquête créée avec succès.');
    }

    /**
     * Affiche le détail d'une enquête avec statistiques.
     */
    public function show(Evenement $evenement, Enquete $enquete): Response
    {
        abort_unless(request()->user()?->can('communication.view'), 403);

        $enquete->load(['evenement', 'reponses.user:id,name,email']);
        $participantsCount = $enquete->evenement?->inscriptions()->count() ?? 0;
        $reponses = $enquete->reponses;

        return Inertia::render('Communication/Enquetes/Show', [
            'enquete' => [
                'id' => $enquete->id,
                'titre' => $enquete->titre,
                'type' => $enquete->type,
                'statut' => $enquete->statut,
                'questions' => $enquete->questions ?? [],
                'evenement' => [
                    'id' => $enquete->evenement?->id,
                    'titre' => $enquete->evenement?->titre,
                ],
            ],
            'stats' => [
                'nb_reponses' => $reponses->count(),
                'participants_count' => $participantsCount,
                'taux_reponse' => $participantsCount > 0 ? round(($reponses->count() / $participantsCount) * 100, 1) : 0,
            ],
            'chartData' => $this->buildChartData($enquete, $reponses->all()),
            'responses' => $reponses->map(fn (ReponseEnquete $reponse): array => [
                'id' => $reponse->id,
                'user' => [
                    'name' => $reponse->user?->name,
                    'email' => $reponse->user?->email,
                ],
                'reponses' => $reponse->reponses,
                'created_at' => optional($reponse->created_at)?->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * Soumet ou met à jour la réponse d'un utilisateur à une enquête.
     */
    public function respond(Request $request, Evenement $evenement, Enquete $enquete): RedirectResponse
    {
        abort_unless(
            $request->user()?->can('communication.view') || $request->user()?->hasRole('participant'),
            403
        );

        $validated = $request->validate([
            'reponses' => ['required', 'array'],
        ]);

        ReponseEnquete::query()->updateOrCreate(
            [
                'enquete_id' => $enquete->id,
                'user_id' => $request->user()->id,
            ],
            [
                'reponses' => $validated['reponses'],
            ]
        );

        return back()->with('success', 'Votre réponse a été enregistrée.');
    }

    /**
     * Publie une enquête.
     */
    public function publish(Evenement $evenement, Enquete $enquete): RedirectResponse
    {
        abort_unless(request()->user()?->can('communication.manage'), 403);

        $enquete->update([
            'statut' => 'publie',
        ]);

        return back()->with('success', 'Enquête publiée avec succès.');
    }

    /**
     * Clôture une enquête.
     */
    public function close(Evenement $evenement, Enquete $enquete): RedirectResponse
    {
        abort_unless(request()->user()?->can('communication.manage'), 403);

        $enquete->update([
            'statut' => 'cloture',
        ]);

        return back()->with('success', 'Enquête clôturée avec succès.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildChartData(Enquete $enquete, array $reponses): array
    {
        return collect($enquete->questions ?? [])->map(function (array $question) use ($reponses): array {
            $values = collect($reponses)->map(function (ReponseEnquete $reponse) use ($question) {
                return data_get($reponse->reponses, $question['id']);
            })->filter(fn ($value) => $value !== null && $value !== '');

            if (($question['type'] ?? null) === 'choix_multiple') {
                $counts = $values
                    ->countBy()
                    ->map(fn ($count, $label) => ['label' => $label, 'value' => $count])
                    ->values()
                    ->all();

                return [
                    'question_id' => $question['id'],
                    'label' => $question['label'],
                    'type' => 'pie',
                    'labels' => array_column($counts, 'label'),
                    'datasets' => [
                        [
                            'label' => $question['label'],
                            'backgroundColor' => ['#0066B3', '#00A651', '#F59E0B', '#8B5CF6', '#EF4444'],
                            'data' => array_column($counts, 'value'),
                        ],
                    ],
                ];
            }

            if (($question['type'] ?? null) === 'note') {
                $counts = $values
                    ->map(fn ($value) => (string) $value)
                    ->countBy()
                    ->sortKeys()
                    ->map(fn ($count, $label) => ['label' => $label, 'value' => $count])
                    ->values()
                    ->all();

                return [
                    'question_id' => $question['id'],
                    'label' => $question['label'],
                    'type' => 'bar',
                    'labels' => array_column($counts, 'label'),
                    'datasets' => [
                        [
                            'label' => $question['label'],
                            'backgroundColor' => '#0066B3',
                            'data' => array_column($counts, 'value'),
                        ],
                    ],
                ];
            }

            return [
                'question_id' => $question['id'],
                'label' => $question['label'],
                'type' => 'text',
                'entries' => $values->values()->all(),
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function types(): array
    {
        return [
            ['value' => 'sondage', 'label' => 'Sondage'],
            ['value' => 'feedback', 'label' => 'Feedback'],
            ['value' => 'evaluation', 'label' => 'Évaluation'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function questionTypes(): array
    {
        return [
            ['value' => 'texte', 'label' => 'Texte libre'],
            ['value' => 'choix_multiple', 'label' => 'Choix multiple'],
            ['value' => 'note', 'label' => 'Note'],
        ];
    }
}