<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Tache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TacheController extends Controller
{
    /**
     * Affiche la liste des tâches de l'événement.
     */
    public function index(Request $request, Evenement $evenement): RedirectResponse|JsonResponse
    {
        $evenement->load('taches.responsable');

        if ($request->expectsJson()) {
            return response()->json([
                'evenement_id' => $evenement->id,
                'taches' => $evenement->taches->map(fn (Tache $tache): array => $this->mapTache($tache))->all(),
            ]);
        }

        return redirect()->route('evenements.show', [
            'evenement' => $evenement,
            'tab' => 'taches',
        ]);
    }

    /**
     * Redirige vers l'onglet tâches.
     */
    public function create(Evenement $evenement): RedirectResponse
    {
        return redirect()->route('evenements.show', [
            'evenement' => $evenement,
            'tab' => 'taches',
        ]);
    }

    /**
     * Enregistre une nouvelle tâche liée à un événement.
     */
    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeStaff($evenement);

        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'echeance' => ['nullable', 'date'],
            'statut' => ['nullable', Rule::in(['a_faire', 'en_cours', 'termine'])],
        ]);

        $evenement->taches()->create([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'responsable_id' => $validated['responsable_id'] ?? null,
            'echeance' => $validated['echeance'] ?? null,
            'statut' => $validated['statut'] ?? 'a_faire',
        ]);

        return back()->with('success', 'Tâche créée avec succès.');
    }

    /**
     * Retourne le détail d'une tâche.
     */
    public function show(Tache $tache): JsonResponse
    {
        $tache->load('responsable');

        return response()->json([
            'tache' => $this->mapTache($tache),
        ]);
    }

    /**
     * Retourne les données d'édition d'une tâche.
     */
    public function edit(Tache $tache): JsonResponse
    {
        $tache->load('responsable');

        return response()->json([
            'tache' => $this->mapTache($tache),
        ]);
    }

    /**
     * Met à jour une tâche existante.
     */
    public function update(Request $request, Tache $tache): RedirectResponse
    {
        $tache->load('evenement');
        $this->authorizeStaff($tache->evenement);

        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'responsable_id' => ['nullable', 'integer', 'exists:users,id'],
            'echeance' => ['nullable', 'date'],
            'statut' => ['required', Rule::in(['a_faire', 'en_cours', 'termine'])],
        ]);

        $tache->update($validated);

        return back()->with('success', 'Tâche mise à jour avec succès.');
    }

    /**
     * Supprime une tâche.
     */
    public function destroy(Tache $tache): RedirectResponse
    {
        $tache->load('evenement');
        $this->authorizeStaff($tache->evenement);
        $tache->delete();

        return back()->with('success', 'Tâche supprimée avec succès.');
    }

    /**
     * Met à jour uniquement le statut d'une tâche.
     */
    public function updateStatut(Request $request, Tache $tache): RedirectResponse
    {
        $tache->load('evenement');
        $this->authorizeStaff($tache->evenement);

        $validated = $request->validate([
            'statut' => ['required', Rule::in(['a_faire', 'en_cours', 'termine'])],
        ]);

        $tache->update([
            'statut' => $validated['statut'],
        ]);

        return back()->with('success', 'Statut de la tâche mis à jour.');
    }

    /**
     * Normalise les données d'une tâche pour les réponses JSON.
     *
     * @return array<string, mixed>
     */
    private function mapTache(Tache $tache): array
    {
        return [
            'id' => $tache->id,
            'evenement_id' => $tache->evenement_id,
            'titre' => $tache->titre,
            'description' => $tache->description,
            'echeance' => optional($tache->echeance)?->toDateString(),
            'statut' => $tache->statut,
            'responsable' => $tache->responsable ? [
                'id' => $tache->responsable->id,
                'name' => $tache->responsable->name,
            ] : null,
        ];
    }

    private function authorizeStaff(?Evenement $evenement): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);
        abort_unless(
            $user->hasRole('responsable_dcirp') ||
            ($user->hasRole('organisateur') && $evenement && $evenement->created_by === $user->id),
            403,
            'Vous n\'êtes pas autorisé à gérer les tâches de cet événement.'
        );
    }
}
