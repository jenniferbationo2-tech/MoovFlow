<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Evenement;
use App\Models\LigneBudget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    /**
     * Redirige vers la fiche événement sur l'onglet budget.
     */
    public function show(Evenement $evenement): RedirectResponse
    {
        return redirect()->route('evenements.show', [
            'evenement' => $evenement,
            'tab' => 'budget',
        ]);
    }

    /**
     * Ajoute une ligne de budget.
     */
    public function storeLigne(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizeGestion($budget->evenement);

        $validated = $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(['recette', 'depense'])],
        ]);

        $budget->lignesBudget()->create($validated);

        return back()->with('success', 'Ligne budgétaire ajoutée avec succès.');
    }

    /**
     * Met à jour une ligne de budget.
     */
    public function updateLigne(Request $request, LigneBudget $ligneBudget): RedirectResponse
    {
        $this->authorizeGestion($ligneBudget->budget->evenement);

        $validated = $request->validate([
            'libelle' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
            'type' => ['required', Rule::in(['recette', 'depense'])],
        ]);

        $ligneBudget->update($validated);

        return back()->with('success', 'Ligne budgétaire mise à jour avec succès.');
    }

    /**
     * Supprime une ligne de budget.
     */
    public function destroyLigne(LigneBudget $ligneBudget): RedirectResponse
    {
        $this->authorizeGestion($ligneBudget->budget->evenement);

        $ligneBudget->delete();

        return back()->with('success', 'Ligne budgétaire supprimée avec succès.');
    }

    /**
     * Seul le responsable dCIRP ou l'organisateur créateur de l'événement peut gérer son budget.
     */
    private function authorizeGestion(Evenement $evenement): void
    {
        $user = Auth::user();

        abort_unless(
            $user && (
                $user->hasRole('responsable_dcirp') ||
                ($user->hasRole('organisateur') && $evenement->created_by === $user->id)
            ),
            403,
            'Vous n\'êtes pas autorisé à gérer le budget de cet événement.'
        );
    }
}
