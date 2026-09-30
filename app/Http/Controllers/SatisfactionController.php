<?php

namespace App\Http\Controllers;

use App\Models\Enquete;
use App\Models\Evenement;
use App\Models\Inscription;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SatisfactionController extends Controller
{
    /**
     * Vue d'ensemble de la satisfaction pour un événement.
     * Liste les enquêtes + résumé global.
     */
    public function index(Evenement $evenement): Response
    {
        $this->autoriserAcces($evenement);

        $enquetes = $evenement->enquetes()
            ->withCount('reponses')
            ->latest()
            ->get()
            ->map(fn (Enquete $e) => [
                'id'              => $e->id,
                'titre'           => $e->titre,
                'type'            => $e->type,
                'statut'          => $e->statut,
                'nb_questions'    => count($e->questions['items'] ?? []),
                'nb_reponses'     => $e->reponses_count,
                'created_at'      => $e->created_at->toIso8601String(),
            ]);

        return Inertia::render('Satisfaction/Index', [
            'evenement' => [
                'id'    => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'enquetes' => $enquetes,
        ]);
    }

    /**
     * Analyse détaillée d'une enquête (stats + graphiques).
     */
    public function analyse(Enquete $enquete): Response
    {
        $this->autoriserAcces($enquete->evenement);

        $enquete->load('evenement', 'reponses');

        // ─── Récupérer toutes les réponses ───
        $reponses = $enquete->reponses;
        $totalReponses = $reponses->count();

        // ─── Calculs participants attendus ───
        $nbInscriptionsValidees = Inscription::where('evenement_id', $enquete->evenement_id)
            ->whereIn('statut', ['validee', 'presente'])
            ->count();

        $tauxReponse = $nbInscriptionsValidees > 0
            ? round(($totalReponses / $nbInscriptionsValidees) * 100, 1)
            : 0;

        // ─── Analyse par question ───
        $items = $enquete->questions['items'] ?? [];
        $analyseParQuestion = [];

        foreach ($items as $item) {
            $questionId = $item['id'];
            $analyse = [
                'id'          => $questionId,
                'label'       => $item['label'],
                'type'        => $item['type'],
                'obligatoire' => $item['obligatoire'] ?? false,
            ];

            // Collecter toutes les valeurs pour cette question
            $valeurs = $reponses
                ->pluck('reponses')
                ->map(fn ($r) => $r[$questionId] ?? null)
                ->filter(fn ($v) => $v !== null && $v !== '');

            $analyse['nb_reponses'] = $valeurs->count();

            switch ($item['type']) {
                case 'note':
                    $notes = $valeurs->map(fn ($v) => (float) $v)->filter(fn ($v) => $v > 0);
                    $analyse['echelle'] = $item['echelle'] ?? 5;
                    $analyse['note_moyenne'] = $notes->count() > 0
                        ? round($notes->avg(), 2)
                        : 0;
                    // Distribution
                    $analyse['distribution'] = [];
                    for ($i = 1; $i <= $analyse['echelle']; $i++) {
                        $analyse['distribution'][$i] = $notes->filter(fn ($v) => $v == $i)->count();
                    }
                    break;

                case 'choix_unique':
                    $analyse['options'] = $item['options'] ?? [];
                    $analyse['distribution'] = [];
                    foreach ($analyse['options'] as $option) {
                        $analyse['distribution'][$option] = $valeurs->filter(fn ($v) => $v === $option)->count();
                    }
                    break;

                case 'choix_multiple':
                    $analyse['options'] = $item['options'] ?? [];
                    $analyse['distribution'] = [];
                    foreach ($analyse['options'] as $option) {
                        $count = $valeurs->filter(fn ($v) => is_array($v) && in_array($option, $v))->count();
                        $analyse['distribution'][$option] = $count;
                    }
                    break;

                case 'oui_non':
                    $analyse['distribution'] = [
                        'oui' => $valeurs->filter(fn ($v) => $v === 'oui')->count(),
                        'non' => $valeurs->filter(fn ($v) => $v === 'non')->count(),
                    ];
                    $totalOuiNon = $analyse['distribution']['oui'] + $analyse['distribution']['non'];
                    $analyse['taux_oui'] = $totalOuiNon > 0
                        ? round(($analyse['distribution']['oui'] / $totalOuiNon) * 100, 1)
                        : 0;
                    break;

                case 'texte_long':
                case 'texte_court':
                    // Récupérer les verbatims non vides
                    $analyse['verbatims'] = $valeurs
                        ->map(fn ($v) => (string) $v)
                        ->filter(fn ($v) => trim($v) !== '')
                        ->take(20)
                        ->values()
                        ->all();
                    break;
            }

            $analyseParQuestion[] = $analyse;
        }

        // ─── Note globale (moyenne des questions de type "note") ───
        $notesGlobales = collect($analyseParQuestion)
            ->where('type', 'note')
            ->pluck('note_moyenne')
            ->filter(fn ($v) => $v > 0);

        $noteGlobale = $notesGlobales->count() > 0
            ? round($notesGlobales->avg(), 2)
            : null;

        return Inertia::render('Satisfaction/Analyse', [
            'evenement' => [
                'id'    => $enquete->evenement->id,
                'titre' => $enquete->evenement->titre,
            ],
            'enquete' => [
                'id'        => $enquete->id,
                'titre'     => $enquete->titre,
                'type'      => $enquete->type,
                'statut'    => $enquete->statut,
                'created_at' => $enquete->created_at->toIso8601String(),
            ],
            'kpis' => [
                'total_reponses'      => $totalReponses,
                'inscriptions_total'  => $nbInscriptionsValidees,
                'taux_reponse'        => $tauxReponse,
                'note_globale'        => $noteGlobale,
                'nb_questions'        => count($items),
            ],
            'analyseParQuestion' => $analyseParQuestion,
        ]);
    }

    /**
     * Helper de permission.
     */
    private function autoriserAcces(Evenement $evenement): void
    {
        $user = Auth::user();

        abort_unless(
            $user->hasRole('responsable_dcirp') ||
                ($user->hasRole('organisateur') && $evenement->created_by === $user->id),
            403,
            'Vous n\'avez pas accès à cette analyse.'
        );
    }
}