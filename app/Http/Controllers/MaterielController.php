<?php

namespace App\Http\Controllers;

use App\Models\Materiel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MaterielController extends Controller
{
    /**
     * Liste du matériel.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAccess();

        $query = Materiel::query();

        // Filtre par catégorie
        if ($request->filled('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        // Filtre par état
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        // Recherche
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($s) use ($q) {
                $s->where('nom', 'like', "%$q%")
                  ->orWhere('description', 'like', "%$q%")
                  ->orWhere('lieu_stockage', 'like', "%$q%");
            });
        }

        $materiels = $query->orderBy('nom')->paginate(20);

        // KPIs
        $kpis = [
            'total'          => Materiel::count(),
            'disponible'     => Materiel::where('etat', '!=', 'hs')->sum('quantite_disponible'),
            'audiovisuel'    => Materiel::where('categorie', 'audiovisuel')->count(),
            'mobilier'       => Materiel::where('categorie', 'mobilier')->count(),
            'hors_service'   => Materiel::where('etat', 'hs')->count(),
        ];

        return Inertia::render('Materiels/Index', [
            'materiels'   => $materiels,
            'kpis'        => $kpis,
            'categories'  => Materiel::CATEGORIES,
            'etats'       => Materiel::ETATS,
            'filters'     => $request->only(['categorie', 'etat', 'search']),
            'permissions' => $this->getPermissions(),
        ]);
    }

    /**
     * Formulaire de création.
     */
    public function create(): Response
    {
        $this->authorizeCreation();

        return Inertia::render('Materiels/Create', [
            'categories' => Materiel::CATEGORIES,
            'etats'      => Materiel::ETATS,
        ]);
    }

    /**
     * Création d'un matériel.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCreation();

        $validated = $this->validerDonnees($request);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('materiels', 'public');
        }

        // Au début : quantité disponible = quantité totale
        $validated['quantite_disponible'] = $validated['quantite_totale'];

        $materiel = Materiel::create($validated);

        return redirect()->route('materiels.index')
            ->with('success', "Matériel \"{$materiel->nom}\" ajouté avec succès au catalogue.");
    }

    /**
     * Voir un matériel (avec ses affectations).
     */
    public function show(Materiel $materiel): Response
    {
        $this->authorizeAccess();

        $materiel->load(['evenements' => function ($q) {
            $q->latest('date_debut')->take(10);
        }]);

        return Inertia::render('Materiels/Show', [
            'materiel'    => $materiel,
            'permissions' => $this->getPermissions(),
        ]);
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(Materiel $materiel): Response
    {
        $this->authorizeCreation();

        return Inertia::render('Materiels/Edit', [
            'materiel'   => $materiel,
            'categories' => Materiel::CATEGORIES,
            'etats'      => Materiel::ETATS,
        ]);
    }

    /**
     * Mise à jour d'un matériel.
     */
    public function update(Request $request, Materiel $materiel): RedirectResponse
    {
        $this->authorizeCreation();

        $validated = $this->validerDonnees($request);

        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo
            if ($materiel->photo) {
                Storage::disk('public')->delete($materiel->photo);
            }
            $validated['photo'] = $request->file('photo')->store('materiels', 'public');
        }

        $materiel->update($validated);

        return redirect()->route('materiels.index')
            ->with('success', 'Matériel mis à jour avec succès.');
    }

    /**
     * Suppression d'un matériel.
     */
    public function destroy(Materiel $materiel): RedirectResponse
    {
        $this->authorizeSupression();

        // Vérifier qu'il n'est affecté à aucun événement en cours
        $affectations = $materiel->evenements()
            ->whereIn('statut', ['publie', 'en_cours'])
            ->count();

        if ($affectations > 0) {
            return back()->with('error', "Ce matériel est affecté à {$affectations} événement(s) en cours. Impossible de le supprimer.");
        }

        // Supprimer la photo
        if ($materiel->photo) {
            Storage::disk('public')->delete($materiel->photo);
        }

        $materiel->delete();

        return redirect()->route('materiels.index')
            ->with('success', 'Matériel supprimé du catalogue.');
    }

    // ════════════════════════════════════════
    //   MÉTHODES PRIVÉES
    // ════════════════════════════════════════

    private function validerDonnees(Request $request): array
    {
        return $request->validate([
            'nom'                  => ['required', 'string', 'max:200'],
            'categorie'            => ['required', 'in:' . implode(',', array_keys(Materiel::CATEGORIES))],
            'description'          => ['nullable', 'string'],
            'quantite_totale'      => ['required', 'integer', 'min:0'],
            'unite'                => ['required', 'string', 'max:50'],
            'etat'                 => ['required', 'in:' . implode(',', array_keys(Materiel::ETATS))],
            'photo'                => ['nullable', 'image', 'max:5120'],
            'lieu_stockage'        => ['nullable', 'string', 'max:200'],
            'note'                 => ['nullable', 'string'],
        ]);
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']),
            403,
            'Accès réservé au staff.'
        );
    }

    private function authorizeCreation(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']),
            403,
            'Vous n\'êtes pas autorisé à modifier le catalogue.'
        );
    }

    private function authorizeSupression(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp']),
            403,
            'Seul le responsable dCIRP peut supprimer du matériel.'
        );
    }

    private function getPermissions(): array
    {
        $user = Auth::user();
        return [
            'peut_creer'     => $user?->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']) ?? false,
            'peut_modifier'  => $user?->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']) ?? false,
            'peut_supprimer' => $user?->hasAnyRole(['admin', 'responsable_dcirp']) ?? false,
        ];
    }
}