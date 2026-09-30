<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Materiel;
use App\Models\Prestataire;
use App\Models\PosteBenevole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LogistiqueController extends Controller
{
    /**
     * Dashboard logistique d'un événement.
     */
    public function show(Evenement $evenement): Response
    {
        $this->authorizeAccess($evenement);

        $evenement->load([
            'typeEvenement',
            'lieu',
            'materiels',
            'prestataires',
            'postesBenevoles' => function ($q) {
                $q->with(['candidatures.user']);
            },
        ]);

        // Calculs synthèse
        $totalDepenses = $evenement->prestataires->sum(function ($p) {
            return $p->pivot->montant_final ?? $p->pivot->montant_prevu ?? 0;
        });

        $kpisLogistique = [
            'materiel_count'     => $evenement->materiels->count(),
            'prestataire_count'  => $evenement->prestataires->count(),
            'postes_count'       => $evenement->postesBenevoles->count(),
            'benevoles_acceptes' => $evenement->postesBenevoles->sum(function ($p) {
                return $p->candidatures->where('statut', 'accepte')->count();
            }),
            'total_depenses'     => $totalDepenses,
        ];

        // Listes pour les modales d'affectation
        $materielsDispo   = Materiel::orderBy('nom')->get();
        $prestatairesDispo = Prestataire::where('actif', true)->orderBy('nom')->get();

        return Inertia::render('Evenements/Logistique', [
            'evenement'         => $evenement,
            'kpis'              => $kpisLogistique,
            'materielsDispo'    => $materielsDispo,
            'prestatairesDispo' => $prestatairesDispo,
            'categoriesPostes'  => PosteBenevole::CATEGORIES,
            'permissions'       => $this->getPermissions($evenement),
        ]);
    }

    // ════════════════════════════════════════
    //   MATÉRIEL : Affectation à l'événement
    // ════════════════════════════════════════

    public function affecterMateriel(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeModification($evenement);

        $validated = $request->validate([
            'materiel_id'     => ['required', 'exists:materiels,id'],
            'quantite_prevue' => ['required', 'integer', 'min:1'],
            'note'            => ['nullable', 'string', 'max:500'],
        ]);

        // Empêcher les doublons
        if ($evenement->materiels()->where('materiel_id', $validated['materiel_id'])->exists()) {
            return back()->with('error', 'Ce matériel est déjà affecté à cet événement.');
        }

        $evenement->materiels()->attach($validated['materiel_id'], [
            'quantite_prevue'    => $validated['quantite_prevue'],
            'quantite_sortie'    => 0,
            'quantite_retournee' => 0,
            'statut'             => 'prevu',
            'note'               => $validated['note'] ?? null,
            'cree_par_id'        => Auth::id(),
        ]);

        return back()->with('success', 'Matériel affecté à l\'événement.');
    }

    public function detacherMateriel(Evenement $evenement, $materielId): RedirectResponse
    {
        $this->authorizeModification($evenement);

        $evenement->materiels()->detach($materielId);

        return back()->with('success', 'Matériel retiré de l\'événement.');
    }

    public function updateStatutMateriel(Request $request, Evenement $evenement, $materielId): RedirectResponse
    {
        $this->authorizeModification($evenement);

        $request->validate([
            'statut'             => ['required', 'in:prevu,sorti,retourne'],
            'quantite_sortie'    => ['nullable', 'integer', 'min:0'],
            'quantite_retournee' => ['nullable', 'integer', 'min:0'],
        ]);

        $evenement->materiels()->updateExistingPivot($materielId, [
            'statut'             => $request->statut,
            'quantite_sortie'    => $request->quantite_sortie ?? 0,
            'quantite_retournee' => $request->quantite_retournee ?? 0,
        ]);

        return back()->with('success', 'Statut du matériel mis à jour.');
    }

    // ════════════════════════════════════════
    //   PRESTATAIRES : Affectation à l'événement
    // ════════════════════════════════════════

    public function affecterPrestataire(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeModification($evenement);

        $validated = $request->validate([
            'prestataire_id' => ['required', 'exists:prestataires,id'],
            'prestation'     => ['required', 'string', 'max:500'],
            'montant_prevu'  => ['nullable', 'numeric', 'min:0'],
            'contrat_pdf'    => ['nullable', 'file', 'mimetypes:application/pdf', 'max:10240'],
            'note'           => ['nullable', 'string', 'max:1000'],
        ]);

        if ($evenement->prestataires()->where('prestataire_id', $validated['prestataire_id'])->exists()) {
            return back()->with('error', 'Ce prestataire est déjà affecté.');
        }

        $contratPath = null;
        if ($request->hasFile('contrat_pdf')) {
            $contratPath = $request->file('contrat_pdf')->store('contrats', 'public');
        }

        $evenement->prestataires()->attach($validated['prestataire_id'], [
            'prestation'    => $validated['prestation'],
            'montant_prevu' => $validated['montant_prevu'] ?? null,
            'statut'        => 'devis',
            'contrat_pdf'   => $contratPath,
            'note'          => $validated['note'] ?? null,
            'cree_par_id'   => Auth::id(),
        ]);

        return back()->with('success', 'Prestataire affecté à l\'événement.');
    }

    public function detacherPrestataire(Evenement $evenement, $prestataireId): RedirectResponse
    {
        $this->authorizeModification($evenement);
        $evenement->prestataires()->detach($prestataireId);
        return back()->with('success', 'Prestataire retiré.');
    }

    public function updateStatutPrestataire(Request $request, Evenement $evenement, $prestataireId): RedirectResponse
    {
        $this->authorizeModification($evenement);

        $request->validate([
            'statut'        => ['required', 'in:devis,confirme,paye,annule'],
            'montant_final' => ['nullable', 'numeric', 'min:0'],
        ]);

        $evenement->prestataires()->updateExistingPivot($prestataireId, [
            'statut'        => $request->statut,
            'montant_final' => $request->montant_final,
        ]);

        return back()->with('success', 'Statut prestataire mis à jour.');
    }

    // ════════════════════════════════════════
    //   POSTES BÉNÉVOLES
    // ════════════════════════════════════════

    public function creerPosteBenevole(Request $request, Evenement $evenement): RedirectResponse
    {
        $this->authorizeModification($evenement);

        $validated = $request->validate([
            'nom_poste'            => ['required', 'string', 'max:200'],
            'categorie'            => ['required', 'in:' . implode(',', array_keys(PosteBenevole::CATEGORIES))],
            'description'          => ['required', 'string'],
            'competences_requises' => ['nullable', 'string'],
            'places_max'           => ['required', 'integer', 'min:1'],
            'horaire_debut'        => ['nullable', 'date'],
            'horaire_fin'          => ['nullable', 'date', 'after_or_equal:horaire_debut'],
        ]);

        PosteBenevole::create(array_merge($validated, [
            'evenement_id' => $evenement->id,
            'statut'       => 'ouvert',
            'cree_par_id'  => Auth::id(),
        ]));

        return back()->with('success', 'Poste bénévole créé.');
    }

    public function updatePosteBenevole(Request $request, PosteBenevole $poste): RedirectResponse
    {
        $this->authorizeModification($poste->evenement);

        $validated = $request->validate([
            'nom_poste'            => ['required', 'string', 'max:200'],
            'categorie'            => ['required', 'in:' . implode(',', array_keys(PosteBenevole::CATEGORIES))],
            'description'          => ['required', 'string'],
            'competences_requises' => ['nullable', 'string'],
            'places_max'           => ['required', 'integer', 'min:1'],
            'horaire_debut'        => ['nullable', 'date'],
            'horaire_fin'          => ['nullable', 'date'],
            'statut'               => ['required', 'in:ouvert,ferme,complet'],
        ]);

        $poste->update($validated);

        return back()->with('success', 'Poste bénévole mis à jour.');
    }

    public function supprimerPosteBenevole(PosteBenevole $poste): RedirectResponse
    {
        $this->authorizeModification($poste->evenement);
        $poste->delete();
        return back()->with('success', 'Poste bénévole supprimé.');
    }

    // ════════════════════════════════════════
    //   MÉTHODES PRIVÉES
    // ════════════════════════════════════════

    private function authorizeAccess(Evenement $evenement): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['responsable_dcirp', 'organisateur']),
            403, 'Accès réservé au staff.'
        );
    }

    private function authorizeModification(Evenement $evenement): void
    {
        $user = Auth::user();

        // Responsable dCIRP : tout pouvoir
        if ($user->hasRole('responsable_dcirp')) {
            return;
        }

        // Organisateur : seulement SES événements
        if ($user->hasRole('organisateur') && $evenement->created_by === $user->id) {
            return;
        }

        abort(403, 'Vous ne pouvez modifier la logistique que de vos propres événements.');
    }

    private function getPermissions(Evenement $evenement): array
    {
        $user = Auth::user();
        $estResponsable = $user?->hasRole('responsable_dcirp') ?? false;
        $estProprio = $user?->hasRole('organisateur') && $evenement->created_by === $user?->id;

        return [
            'peut_modifier'  => $estResponsable || $estProprio,
            'est_responsable'=> $estResponsable,
            'est_proprio'    => $estProprio,
        ];
    }
}