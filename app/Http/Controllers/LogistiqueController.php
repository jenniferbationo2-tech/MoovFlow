<?php

namespace App\Http\Controllers;

use App\Models\Benevole;
use App\Models\Evenement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LogistiqueController extends Controller
{
    /**
     * Page principale du module Logistique.
     */
    public function index(Evenement $evenement): Response
    {
        $this->authorizeAccess($evenement);

        $evenement->load(['lieu.salles']);

        // Ressources (matériel)
        $ressources = DB::table('ressources')
            ->where('evenement_id', $evenement->id)
            ->orderBy('nom')
            ->get()
            ->map(fn ($r) => [
                'id'             => $r->id,
                'nom'            => $r->nom,
                'type'           => $r->type ?? 'materiel',
                'quantite_totale'=> $r->quantite_totale ?? 0,
                'quantite_dispo' => $r->quantite_disponible ?? 0,
                'unite'          => $r->unite ?? null,
                'cout_unitaire'  => (float) ($r->cout_unitaire ?? 0),
                'fournisseur'    => $r->fournisseur ?? null,
                'observations'   => $r->observations ?? null,
            ])->all();

        // Dotations (matériel distribué)
        $dotations = DB::table('dotations')
            ->where('evenement_id', $evenement->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($d) => [
                'id'             => $d->id,
                'beneficiaire'   => $d->beneficiaire ?? '—',
                'item'           => $d->item ?? '—',
                'quantite'       => $d->quantite ?? 1,
                'taille'         => $d->taille ?? null,
                'date_remise'    => $d->date_remise ?? null,
                'statut'         => $d->statut ?? 'distribue',
                'a_retourner'    => (bool) ($d->a_retourner ?? false),
                'date_retour'    => $d->date_retour ?? null,
            ])->all();

        // Bénévoles
        $benevoles = Benevole::query()
            ->where('evenement_id', $evenement->id)
            ->orderBy('nom')
            ->get()
            ->map(fn (Benevole $b) => [
                'id'              => $b->id,
                'nom'             => $b->nom,
                'prenom'          => $b->prenom,
                'email'           => $b->email,
                'telephone'       => $b->telephone,
                'poste_affecte'   => $b->poste_affecte,
                'horaires'        => $b->horaires,
                'statut'          => $b->statut ?? 'inscrit',
                'date_inscription'=> optional($b->created_at)?->toIso8601String(),
            ])->all();

        return Inertia::render('Logistique/Index', [
            'evenement' => [
                'id'    => $evenement->id,
                'titre' => $evenement->titre,
                'lieu'  => $evenement->lieu ? ['nom' => $evenement->lieu->nom] : null,
            ],
            'ressources' => $ressources,
            'dotations'  => $dotations,
            'benevoles'  => $benevoles,
        ]);
    }

    
    public function storeRessource(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        $validated = $request->validate([
            'nom'             => ['required', 'string', 'max:255'],
            'type'            => ['nullable', 'string', 'max:50'],
            'quantite_totale' => ['required', 'integer', 'min:1'],
            'unite'           => ['nullable', 'string', 'max:50'],
            'cout_unitaire'   => ['nullable', 'numeric', 'min:0'],
            'fournisseur'     => ['nullable', 'string', 'max:255'],
            'observations'    => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('ressources')->insert([
            'evenement_id'        => $evenement->id,
            'nom'                 => $validated['nom'],
            'type'                => $validated['type'] ?? 'materiel',
            'quantite_totale'     => $validated['quantite_totale'],
            'quantite_disponible' => $validated['quantite_totale'],
            'unite'               => $validated['unite'] ?? null,
            'cout_unitaire'       => $validated['cout_unitaire'] ?? 0,
            'fournisseur'         => $validated['fournisseur'] ?? null,
            'observations'        => $validated['observations'] ?? null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return back()->with('success', 'Ressource ajoutée à l\'inventaire.');
    }

    public function destroyRessource(Evenement $evenement, int $ressourceId): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        DB::table('ressources')
            ->where('id', $ressourceId)
            ->where('evenement_id', $evenement->id)
            ->delete();

        return back()->with('success', 'Ressource supprimée.');
    }

    

    public function storeDotation(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        $validated = $request->validate([
            'beneficiaire' => ['required', 'string', 'max:255'],
            'item'         => ['required', 'string', 'max:255'],
            'quantite'     => ['required', 'integer', 'min:1'],
            'taille'       => ['nullable', 'string', 'max:50'],
            'a_retourner'  => ['nullable', 'boolean'],
        ]);

        DB::table('dotations')->insert([
            'evenement_id' => $evenement->id,
            'beneficiaire' => $validated['beneficiaire'],
            'item'         => $validated['item'],
            'quantite'     => $validated['quantite'],
            'taille'       => $validated['taille'] ?? null,
            'date_remise'  => now(),
            'statut'       => 'distribue',
            'a_retourner'  => $validated['a_retourner'] ?? false,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('success', 'Dotation enregistrée.');
    }

    public function returnDotation(Evenement $evenement, int $dotationId): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        DB::table('dotations')
            ->where('id', $dotationId)
            ->where('evenement_id', $evenement->id)
            ->update([
                'statut'      => 'retourne',
                'date_retour' => now(),
                'updated_at'  => now(),
            ]);

        return back()->with('success', 'Retour enregistré.');
    }

    public function destroyDotation(Evenement $evenement, int $dotationId): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        DB::table('dotations')
            ->where('id', $dotationId)
            ->where('evenement_id', $evenement->id)
            ->delete();

        return back()->with('success', 'Dotation supprimée.');
    }

    

    public function storeBenevole(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeAccess($evenement);

        $validated = $request->validate([
            'nom'           => ['required', 'string', 'max:255'],
            'prenom'        => ['required', 'string', 'max:255'],
            'email'         => ['nullable', 'email', 'max:255'],
            'telephone'     => ['nullable', 'string', 'max:50'],
            'poste_affecte' => ['nullable', 'string', 'max:255'],
            'horaires'      => ['nullable', 'string', 'max:255'],
        ]);

        Benevole::create([
            'evenement_id'  => $evenement->id,
            'nom'           => $validated['nom'],
            'prenom'        => $validated['prenom'],
            'email'         => $validated['email'] ?? null,
            'telephone'     => $validated['telephone'] ?? null,
            'poste_affecte' => $validated['poste_affecte'] ?? null,
            'horaires'      => $validated['horaires'] ?? null,
            'statut'        => 'inscrit',
        ]);

        return back()->with('success', 'Bénévole ajouté à l\'équipe.');
    }

    public function destroyBenevole(Evenement $evenement, Benevole $benevole): RedirectResponse
    {
        $this->authorizeAccess($evenement);
        abort_unless($benevole->evenement_id === $evenement->id, 403);

        $benevole->delete();

        return back()->with('success', 'Bénévole retiré.');
    }

    /**
     * Vérifie l'accès logistique (admin/responsable/créateur).
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
            'Accès réservé au staff.'
        );
    }
}