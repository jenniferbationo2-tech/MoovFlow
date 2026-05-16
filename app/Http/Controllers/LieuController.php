<?php

namespace App\Http\Controllers;

use App\Models\Lieu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class LieuController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAccess();

        $query = Lieu::query();

        if ($request->filled('ville')) {
            $query->where('ville', $request->ville);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($s) use ($q) {
                $s->where('nom', 'like', "%$q%")
                  ->orWhere('adresse', 'like', "%$q%")
                  ->orWhere('ville', 'like', "%$q%");
            });
        }

        $lieux = $query->withCount('evenements')->orderBy('nom')->paginate(20);

        $kpis = [
            'total'      => Lieu::count(),
            'actifs'     => Lieu::where('actif', true)->count(),
            'inactifs'   => Lieu::where('actif', false)->count(),
        ];

        return Inertia::render('Lieux/Index', [
            'lieux'       => $lieux,
            'kpis'        => $kpis,
            'filters'     => $request->only(['ville', 'search']),
            'permissions' => $this->getPermissions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorizeCreation();
        return Inertia::render('Lieux/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCreation();

        $validated = $this->validerDonnees($request);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('lieux', 'public');
        }

        $lieu = Lieu::create($validated);

        return redirect()->route('lieux.index')
            ->with('success', "Lieu \"{$lieu->nom}\" ajouté avec succès.");
    }

    public function show(Lieu $lieu): Response
    {
        $this->authorizeAccess();
        $lieu->load(['evenements' => fn ($q) => $q->latest('date_debut')->take(10)]);

        return Inertia::render('Lieux/Show', [
            'lieu'        => $lieu,
            'permissions' => $this->getPermissions(),
        ]);
    }

    public function edit(Lieu $lieu): Response
    {
        $this->authorizeCreation();
        return Inertia::render('Lieux/Edit', ['lieu' => $lieu]);
    }

    public function update(Request $request, Lieu $lieu): RedirectResponse
    {
        $this->authorizeCreation();

        $validated = $this->validerDonnees($request);

        if ($request->hasFile('photo')) {
            if ($lieu->photo) {
                Storage::disk('public')->delete($lieu->photo);
            }
            $validated['photo'] = $request->file('photo')->store('lieux', 'public');
        }

        $lieu->update($validated);

        return redirect()->route('lieux.index')
            ->with('success', 'Lieu mis à jour avec succès.');
    }

    public function destroy(Lieu $lieu): RedirectResponse
    {
        $this->authorizeSupression();

        $affectations = $lieu->evenements()
            ->whereIn('statut', ['publie', 'en_cours'])
            ->count();

        if ($affectations > 0) {
            return back()->with('error', "Ce lieu est associé à {$affectations} événement(s) en cours.");
        }

        if ($lieu->photo) {
            Storage::disk('public')->delete($lieu->photo);
        }

        $lieu->delete();

        return redirect()->route('lieux.index')
            ->with('success', 'Lieu supprimé.');
    }

    private function validerDonnees(Request $request): array
    {
        return $request->validate([
            'nom'          => ['required', 'string', 'max:200'],
            'adresse'      => ['nullable', 'string', 'max:500'],
            'ville'        => ['nullable', 'string', 'max:100'],
            'capacite_max' => ['nullable', 'integer', 'min:0'],
            'description'  => ['nullable', 'string'],
            'photo'        => ['nullable', 'image', 'max:5120'],
            'actif'        => ['boolean'],
        ]);
    }

    private function authorizeAccess(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']),
            403, 'Accès réservé au staff.'
        );
    }

    private function authorizeCreation(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp', 'organisateur']),
            403, 'Action non autorisée.'
        );
    }

    private function authorizeSupression(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp']),
            403, 'Seul le responsable dCIRP peut supprimer un lieu.'
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