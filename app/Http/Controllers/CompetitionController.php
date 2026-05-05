<?php

namespace App\Http\Controllers;

use App\Models\Classement;
use App\Models\CompetitionPhase;
use App\Models\Equipe;
use App\Models\Evenement;
use App\Models\Rencontre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    /**
     * Affiche le module Compétition pour un événement.
     */
    public function index(Evenement $evenement): Response
    {
        $this->authorizeAccess($evenement);

        $evenement->load(['typeEvenement', 'lieu']);

        // Charger toutes les équipes de l'événement
        $equipes = Equipe::query()
            ->where('evenement_id', $evenement->id)
            ->withCount('membres')
            ->orderBy('nom')
            ->get()
            ->map(fn (Equipe $e) => [
                'id'           => $e->id,
                'nom'          => $e->nom,
                'capitaine'    => $e->capitaine ?? null,
                'categorie'    => $e->categorie ?? null,
                'membres_count'=> $e->membres_count ?? 0,
                'created_at'   => optional($e->created_at)?->toIso8601String(),
            ])->all();

        // Charger toutes les phases
        $phases = CompetitionPhase::query()
            ->where('evenement_id', $evenement->id)
            ->with(['rencontres.equipeA', 'rencontres.equipeB', 'rencontres.vainqueur'])
            ->orderBy('ordre')
            ->get()
            ->map(fn (CompetitionPhase $p) => [
                'id'         => $p->id,
                'nom'        => $p->nom,
                'ordre'      => $p->ordre,
                'date_debut' => optional($p->date_debut)?->toIso8601String(),
                'date_fin'   => optional($p->date_fin)?->toIso8601String(),
                'statut'     => $p->statut,
                'rencontres' => $p->rencontres->map(fn (Rencontre $r) => [
                    'id'            => $r->id,
                    'date_match'    => optional($r->date_match)?->toIso8601String(),
                    'lieu_match'    => $r->lieu_match,
                    'arbitre'       => $r->arbitre,
                    'score_equipe_a'=> $r->score_equipe_a,
                    'score_equipe_b'=> $r->score_equipe_b,
                    'statut'        => $r->statut,
                    'observations'  => $r->observations,
                    'equipe_a'      => $r->equipeA ? [
                        'id'  => $r->equipeA->id,
                        'nom' => $r->equipeA->nom,
                    ] : null,
                    'equipe_b'      => $r->equipeB ? [
                        'id'  => $r->equipeB->id,
                        'nom' => $r->equipeB->nom,
                    ] : null,
                    'vainqueur'     => $r->vainqueur ? [
                        'id'  => $r->vainqueur->id,
                        'nom' => $r->vainqueur->nom,
                    ] : null,
                ])->all(),
            ])->all();

        // Classements par phase
        $classements = Classement::query()
            ->whereIn('competition_phase_id', collect($phases)->pluck('id'))
            ->with('equipe:id,nom')
            ->orderBy('rang')
            ->get()
            ->groupBy('competition_phase_id')
            ->map(fn ($items) => $items->map(fn (Classement $c) => [
                'id'              => $c->id,
                'equipe'          => $c->equipe ? ['id' => $c->equipe->id, 'nom' => $c->equipe->nom] : null,
                'points'          => $c->points,
                'matchs_joues'    => $c->matchs_joues,
                'victoires'       => $c->victoires,
                'nuls'            => $c->nuls,
                'defaites'        => $c->defaites,
                'buts_marques'    => $c->buts_marques,
                'buts_encaisses'  => $c->buts_encaisses,
                'difference_buts' => $c->difference_buts,
                'rang'            => $c->rang,
            ])->values()->all());

        return Inertia::render('Competitions/Index', [
            'evenement'   => [
                'id'    => $evenement->id,
                'titre' => $evenement->titre,
                'type'  => $evenement->typeEvenement?->code,
                'lieu'  => $evenement->lieu ? ['nom' => $evenement->lieu->nom] : null,
            ],
            'equipes'     => $equipes,
            'phases'      => $phases,
            'classements' => $classements,
        ]);
    }

    
    //   ÉQUIPES
   

    public function storeEquipe(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        $validated = $request->validate([
            'nom'       => ['required', 'string', 'max:255'],
            'capitaine' => ['nullable', 'string', 'max:255'],
            'categorie' => ['nullable', 'string', 'max:50'],
        ]);

        Equipe::create([
            'evenement_id' => $evenement->id,
            'nom'          => $validated['nom'],
            'capitaine'    => $validated['capitaine'] ?? null,
            'categorie'    => $validated['categorie'] ?? null,
        ]);

        return back()->with('success', 'Équipe ajoutée avec succès.');
    }

    public function destroyEquipe(Evenement $evenement, Equipe $equipe): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        abort_unless($equipe->evenement_id === $evenement->id, 403);

        $equipe->delete();

        return back()->with('success', 'Équipe supprimée.');
    }

    //   PHASES
    

    public function storePhase(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        $validated = $request->validate([
            'nom'        => ['required', 'string', 'max:255'],
            'ordre'      => ['required', 'integer', 'min:1'],
            'date_debut' => ['nullable', 'date'],
            'date_fin'   => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        CompetitionPhase::create([
            'evenement_id' => $evenement->id,
            'nom'          => $validated['nom'],
            'ordre'        => $validated['ordre'],
            'date_debut'   => $validated['date_debut'] ?? null,
            'date_fin'     => $validated['date_fin'] ?? null,
            'statut'       => 'a_venir',
        ]);

        return back()->with('success', 'Phase créée avec succès.');
    }

    public function destroyPhase(Evenement $evenement, CompetitionPhase $phase): RedirectResponse
    {
        $this->authorizeAccess($evenement);
        abort_unless($phase->evenement_id === $evenement->id, 403);

        $phase->delete();

        return back()->with('success', 'Phase supprimée.');
    }

    
    //   RENCONTRE

    public function storeRencontre(Request $request, Evenement $evenement, CompetitionPhase $phase): RedirectResponse
    {
        $this->authorizeAccess($evenement);
        abort_unless($phase->evenement_id === $evenement->id, 403);

        $validated = $request->validate([
            'equipe_a_id' => ['required', 'exists:equipes,id', 'different:equipe_b_id'],
            'equipe_b_id' => ['required', 'exists:equipes,id'],
            'date_match'  => ['required', 'date'],
            'lieu_match'  => ['nullable', 'string', 'max:255'],
            'arbitre'     => ['nullable', 'string', 'max:255'],
        ]);

        Rencontre::create([
            'competition_phase_id' => $phase->id,
            'equipe_a_id'          => $validated['equipe_a_id'],
            'equipe_b_id'          => $validated['equipe_b_id'],
            'date_match'           => $validated['date_match'],
            'lieu_match'           => $validated['lieu_match'] ?? null,
            'arbitre'              => $validated['arbitre'] ?? null,
            'statut'               => 'planifiee',
        ]);

        return back()->with('success', 'Rencontre planifiée.');
    }

    /**
     * Saisir le score d'une rencontre + recalculer le classement.
     */
    public function updateScore(Request $request, Evenement $evenement, Rencontre $rencontre): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        $validated = $request->validate([
            'score_equipe_a' => ['required', 'integer', 'min:0'],
            'score_equipe_b' => ['required', 'integer', 'min:0'],
            'observations'   => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($rencontre, $validated) {
            // Mettre à jour le score
            $rencontre->update([
                'score_equipe_a' => $validated['score_equipe_a'],
                'score_equipe_b' => $validated['score_equipe_b'],
                'observations'   => $validated['observations'] ?? null,
                'statut'         => 'terminee',
            ]);

            // Déterminer le vainqueur
            $vainqueurId = $rencontre->determinerVainqueur();
            $rencontre->update(['vainqueur_id' => $vainqueurId]);

            // Recalculer le classement de la phase
            $this->recalculerClassement($rencontre->competition_phase_id);
        });

        return back()->with('success', 'Score enregistré et classement mis à jour.');
    }

    public function destroyRencontre(Evenement $evenement, Rencontre $rencontre): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        $phaseId = $rencontre->competition_phase_id;
        $rencontre->delete();
        $this->recalculerClassement($phaseId);

        return back()->with('success', 'Rencontre supprimée.');
    }


    /**
     * Recalcule le classement d'une phase.
     * Système : 3 points victoire, 1 point nul, 0 point défaite.
     */
    private function recalculerClassement(int $phaseId): void
    {
        $rencontres = Rencontre::query()
            ->where('competition_phase_id', $phaseId)
            ->where('statut', 'terminee')
            ->get();

        $stats = [];

        foreach ($rencontres as $r) {
            // Initialiser les stats des 2 équipes si pas encore là
            foreach ([$r->equipe_a_id, $r->equipe_b_id] as $eqId) {
                if (!isset($stats[$eqId])) {
                    $stats[$eqId] = [
                        'matchs_joues'    => 0,
                        'victoires'       => 0,
                        'nuls'            => 0,
                        'defaites'        => 0,
                        'buts_marques'    => 0,
                        'buts_encaisses'  => 0,
                        'points'          => 0,
                    ];
                }
            }

            // Compteur matchs joués
            $stats[$r->equipe_a_id]['matchs_joues']++;
            $stats[$r->equipe_b_id]['matchs_joues']++;

            // Buts
            $stats[$r->equipe_a_id]['buts_marques']   += $r->score_equipe_a;
            $stats[$r->equipe_a_id]['buts_encaisses'] += $r->score_equipe_b;
            $stats[$r->equipe_b_id]['buts_marques']   += $r->score_equipe_b;
            $stats[$r->equipe_b_id]['buts_encaisses'] += $r->score_equipe_a;

            // Résultat 
            if ($r->score_equipe_a > $r->score_equipe_b) {
                $stats[$r->equipe_a_id]['victoires']++;
                $stats[$r->equipe_a_id]['points'] += 3;
                $stats[$r->equipe_b_id]['defaites']++;
            } elseif ($r->score_equipe_b > $r->score_equipe_a) {
                $stats[$r->equipe_b_id]['victoires']++;
                $stats[$r->equipe_b_id]['points'] += 3;
                $stats[$r->equipe_a_id]['defaites']++;
            } else {
                $stats[$r->equipe_a_id]['nuls']++;
                $stats[$r->equipe_b_id]['nuls']++;
                $stats[$r->equipe_a_id]['points']++;
                $stats[$r->equipe_b_id]['points']++;
            }
        }

        // Supprimer ancien classement
        Classement::where('competition_phase_id', $phaseId)->delete();

        
        $classementTrie = collect($stats)->map(function ($s, $equipeId) {
            $s['equipe_id']       = $equipeId;
            $s['difference_buts'] = $s['buts_marques'] - $s['buts_encaisses'];
            return $s;
        })->sort(function ($a, $b) {
            if ($a['points'] !== $b['points']) return $b['points'] - $a['points'];
            if ($a['difference_buts'] !== $b['difference_buts']) return $b['difference_buts'] - $a['difference_buts'];
            return $b['buts_marques'] - $a['buts_marques'];
        })->values();

        // Insérer avec rang
        foreach ($classementTrie as $rang => $stat) {
            Classement::create([
                'competition_phase_id' => $phaseId,
                'equipe_id'            => $stat['equipe_id'],
                'matchs_joues'         => $stat['matchs_joues'],
                'victoires'            => $stat['victoires'],
                'nuls'                 => $stat['nuls'],
                'defaites'             => $stat['defaites'],
                'buts_marques'         => $stat['buts_marques'],
                'buts_encaisses'       => $stat['buts_encaisses'],
                'difference_buts'      => $stat['difference_buts'],
                'points'               => $stat['points'],
                'rang'                 => $rang + 1,
            ]);
        }
    }

    /**
     * Vérifie l'accès à la compétition (admin/responsable/créateur).
     */
    private function authorizeAccess(Evenement $evenement): void
    {
        $user = Auth::user();
        abort_unless(
            $user && (
                $user->hasAnyRole(['admin', 'responsable_dcirp']) ||
                (int) $evenement->created_by === (int) $user->id
            ),
            403,
            'Accès réservé au staff de l\'événement.'
        );
    }
}