<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Tarif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TarifController extends Controller
{
    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeEditionEvenement($evenement);

        $validated = $request->validate([
            'nom'     => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
        ]);

        $evenement->tarifs()->create($validated);

        return back()->with('success', 'Tarif créé avec succès.');
    }

    public function update(Request $request, Tarif $tarif): RedirectResponse
    {
        $tarif->load('evenement');
        $this->authorizeEditionEvenement($tarif->evenement);

        $validated = $request->validate([
            'nom'     => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
        ]);

        $tarif->update($validated);

        return back()->with('success', 'Tarif mis à jour avec succès.');
    }

    public function destroy(Tarif $tarif): RedirectResponse
    {
        $tarif->load('evenement');
        $this->authorizeEditionEvenement($tarif->evenement);

        $tarif->delete();

        return back()->with('success', 'Tarif supprimé avec succès.');
    }

    private function authorizeEditionEvenement(Evenement $evenement): void
    {
        $user = Auth::user();
        abort_unless(
            $user && (
                $user->hasRole('responsable_dcirp') ||
                $evenement->created_by === $user->id
            ),
            403,
            'Vous n\'êtes pas autorisé à modifier les tarifs de cet événement.'
        );
    }
}
