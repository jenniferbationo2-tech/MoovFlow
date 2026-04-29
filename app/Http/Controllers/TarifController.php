<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Tarif;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TarifController extends Controller
{
    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
        ]);

        $evenement->tarifs()->create($validated);

        return back()->with('success', 'Tarif cree avec succes.');
    }

    public function update(Request $request, Tarif $tarif): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'min:0'],
        ]);

        $tarif->update($validated);

        return back()->with('success', 'Tarif mis a jour avec succes.');
    }

    public function destroy(Tarif $tarif): RedirectResponse
    {
        $tarif->delete();

        return back()->with('success', 'Tarif supprime avec succes.');
    }
}