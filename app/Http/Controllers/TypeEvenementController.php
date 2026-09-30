<?php

namespace App\Http\Controllers;

use App\Models\TypeEvenement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TypeEvenementController extends Controller
{
    /**
     * Liste des typologies d'événements. Réservé au responsable dCIRP.
     */
    public function index(): Response
    {
        $this->authorizeGestion();

        $types = TypeEvenement::withCount('evenements')
            ->orderBy('nom')
            ->get();

        return Inertia::render('TypesEvenement/Index', [
            'types' => $types,
        ]);
    }

    /**
     * Crée une nouvelle typologie d'événement.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeGestion();

        $validated = $request->validate([
            'nom'  => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50'],
        ]);

        // Normalisation du code : MAJUSCULES, underscores, sans caractères spéciaux.
        $code = Str::of($validated['code'])
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->value();

        if ($code === '') {
            return back()->withErrors(['code' => 'Le code doit contenir au moins une lettre ou un chiffre.']);
        }

        if (TypeEvenement::where('code', $code)->exists()) {
            return back()->withErrors(['code' => 'Ce code est déjà utilisé par une autre typologie.']);
        }

        TypeEvenement::create([
            'nom'  => $validated['nom'],
            'code' => $code,
        ]);

        return back()->with('success', 'Typologie créée avec succès.');
    }

    /**
     * Supprime une typologie, uniquement si elle n'est utilisée par aucun événement.
     */
    public function destroy(TypeEvenement $typeEvenement): RedirectResponse
    {
        $this->authorizeGestion();

        if ($typeEvenement->evenements()->exists()) {
            return back()->withErrors(['suppression' => 'Cette typologie est utilisée par au moins un événement et ne peut pas être supprimée.']);
        }

        $typeEvenement->delete();

        return back()->with('success', 'Typologie supprimée.');
    }

    private function authorizeGestion(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole('responsable_dcirp'), 403, 'Seul le responsable dCIRP peut gérer les typologies d\'événements.');
    }
}
