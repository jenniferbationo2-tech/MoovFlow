<?php

namespace App\Http\Controllers;

use App\Models\Prestataire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PrestataireController extends Controller
{
    /**
     * Liste des prestataires.
     */
    public function index(Request $request): Response
    {
        $this->authorizeAccess();

        $query = Prestataire::query();

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->categorie);
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->actif === 'oui');
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($s) use ($q) {
                $s->where('nom', 'like', "%$q%")
                  ->orWhere('contact_nom', 'like', "%$q%")
                  ->orWhere('email', 'like', "%$q%")
                  ->orWhere('ville', 'like', "%$q%");
            });
        }

        $prestataires = $query->orderBy('nom')->paginate(20);

        $kpis = [
            'total'      => Prestataire::count(),
            'actifs'     => Prestataire::where('actif', true)->count(),
            'inactifs'   => Prestataire::where('actif', false)->count(),
            'top_notes'  => Prestataire::where('note_interne', '>=', 4)->count(),
        ];

        return Inertia::render('Prestataires/Index', [
            'prestataires' => $prestataires,
            'kpis'         => $kpis,
            'categories'   => Prestataire::CATEGORIES,
            'filters'      => $request->only(['categorie', 'actif', 'search']),
            'permissions'  => $this->getPermissions(),
        ]);
    }

    /**
     * Formulaire de création.
     */
    public function create(): Response
    {
        $this->authorizeCreation();

        return Inertia::render('Prestataires/Create', [
            'categories' => Prestataire::CATEGORIES,
        ]);
    }

    /**
     * Création d'un prestataire.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCreation();

        $validated = $this->validerDonnees($request);
        $prestataire = Prestataire::create($validated);

        return redirect()->route('prestataires.index')
            ->with('success', "Prestataire \"{$prestataire->nom}\" ajouté à l'annuaire.");
    }

    /**
     * Voir un prestataire.
     */
    public function show(Prestataire $prestataire): Response
    {
        $this->authorizeAccess();

        $prestataire->load(['evenements' => function ($q) {
            $q->latest('date_debut')->take(10);
        }]);

        return Inertia::render('Prestataires/Show', [
            'prestataire' => $prestataire,
            'permissions' => $this->getPermissions(),
        ]);
    }

    /**
     * Formulaire d'édition.
     */
    public function edit(Prestataire $prestataire): Response
    {
        $this->authorizeCreation();

        return Inertia::render('Prestataires/Edit', [
            'prestataire' => $prestataire,
            'categories'  => Prestataire::CATEGORIES,
        ]);
    }

    /**
     * Mise à jour.
     */
    public function update(Request $request, Prestataire $prestataire): RedirectResponse
    {
        $this->authorizeCreation();

        $validated = $this->validerDonnees($request);
        $prestataire->update($validated);

        return redirect()->route('prestataires.index')
            ->with('success', 'Prestataire mis à jour avec succès.');
    }

    /**
     * Suppression.
     */
    public function destroy(Prestataire $prestataire): RedirectResponse
    {
        $this->authorizeSupression();

        $affectations = $prestataire->evenements()
            ->whereIn('statut', ['publie', 'en_cours'])
            ->count();

        if ($affectations > 0) {
            return back()->with('error', "Ce prestataire est associé à {$affectations} événement(s) en cours. Désactivez-le plutôt.");
        }

        $prestataire->delete();

        return redirect()->route('prestataires.index')
            ->with('success', 'Prestataire supprimé de l\'annuaire.');
    }

    // ════════════════════════════════════════
    //   MÉTHODES PRIVÉES
    // ════════════════════════════════════════

    private function validerDonnees(Request $request): array
    {
        return $request->validate([
            'nom'           => ['required', 'string', 'max:200'],
            'categorie'     => ['required', 'in:' . implode(',', array_keys(Prestataire::CATEGORIES))],
            'contact_nom'   => ['nullable', 'string', 'max:200'],
            'email'         => ['nullable', 'email', 'max:255'],
            'telephone'     => ['nullable', 'string', 'max:50'],
            'adresse'       => ['nullable', 'string', 'max:500'],
            'ville'         => ['nullable', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'site_web'      => ['nullable', 'url', 'max:500'],
            'note_interne'  => ['nullable', 'integer', 'min:1', 'max:5'],
            'note'          => ['nullable', 'string'],
            'actif'         => ['boolean'],
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
            'Vous n\'êtes pas autorisé à modifier l\'annuaire.'
        );
    }

    private function authorizeSupression(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp']),
            403,
            'Seul le responsable dCIRP peut supprimer un prestataire.'
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