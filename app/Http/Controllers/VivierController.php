<?php

namespace App\Http\Controllers;

use App\Models\Benevole;
use App\Models\Intervenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VivierController extends Controller
{
    private function staffAccess(): void
    {
        $user = Auth::user();
        abort_unless(
            $user && $user->hasAnyRole(['admin', 'responsable_dcirp']),
            403,
            'Accès réservé au staff Moov.'
        );
    }

    public function benevoles(Request $request): Response
    {
        $this->staffAccess();

        $query = Benevole::query();
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($s) use ($q) {
                $s->where('nom', 'like', "%$q%")
                    ->orWhere('prenom', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%")
                    ->orWhere('telephone', 'like', "%$q%");
            });
        }

        $benevoles = $query->latest()->paginate(20)->through(fn(Benevole $b) => [
            'id'             => $b->id,
            'nom'            => $b->nom,
            'prenom'         => $b->prenom,
            'email'          => $b->email,
            'telephone'      => $b->telephone,
            'poste_affecte'  => $b->poste_affecte,
            'horaires'       => $b->horaires,
            'disponibilites' => $b->disponibilites ?? null,
            'competences'    => $b->competences ?? null,
            'statut'         => $b->statut ?? 'inscrit',
            'evenement_id'   => $b->evenement_id,
            'created_at'     => optional($b->created_at)?->toIso8601String(),
        ]);

        $stats = [
            'total'        => Benevole::count(),
            'sans_event'   => Benevole::whereNull('evenement_id')->count(),
            'avec_event'   => Benevole::whereNotNull('evenement_id')->count(),
        ];

        return Inertia::render('Vivier/Benevoles', [
            'benevoles' => $benevoles,
            'stats'     => $stats,
            'filters'   => $request->only(['search']),
        ]);
    }

    public function storeBenevole(Request $request): RedirectResponse
    {
        $this->staffAccess();

        $validated = $request->validate([
            'nom'            => ['required', 'string', 'max:255'],
            'prenom'         => ['required', 'string', 'max:255'],
            'email'          => ['nullable', 'email', 'max:255'],
            'telephone'      => ['nullable', 'string', 'max:50'],
            'poste_affecte'  => ['nullable', 'string', 'max:255'],
            'disponibilites' => ['nullable', 'string', 'max:255'],
            'competences'    => ['nullable', 'string', 'max:1000'],
        ]);

        Benevole::create(array_merge($validated, [
            'evenement_id' => null,
            'statut'       => 'inscrit',
        ]));

        return back()->with('success', 'Bénévole ajouté au vivier.');
    }

    public function destroyBenevole(Benevole $benevole): RedirectResponse
    {
        $this->staffAccess();
        $benevole->delete();
        return back()->with('success', 'Bénévole supprimé.');
    }



    public function intervenants(Request $request): Response
    {
        $this->staffAccess();

        $query = Intervenant::query();
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($s) use ($q) {
                $s->where('nom', 'like', "%$q%")
                    ->orWhere('prenom', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%")
                    ->orWhere('specialite', 'like', "%$q%");
            });
        }

        $intervenants = $query->latest()->paginate(20)->through(fn(Intervenant $i) => [
            'id'         => $i->id,
            'nom'        => $i->nom,
            'prenom'     => $i->prenom,
            'email'      => $i->email,
            'telephone'  => $i->telephone,
            'specialite' => $i->specialite ?? null,
            'biographie' => $i->biographie ?? null,
            'tarif_jour' => $i->tarif_jour ?? 0,
            'role' => $i->specialite ?? 'intervenant',
            'created_at' => optional($i->created_at)?->toIso8601String(),
        ]);

        $stats = [
            'total'         => Intervenant::count(),
            'intervenants' => Intervenant::count(),
            'jury'         => 0,
            'formateurs'   => 0,
        ];

        return Inertia::render('Vivier/Intervenants', [
            'intervenants' => $intervenants,
            'stats'        => $stats,
            'filters'      => $request->only(['search']),
        ]);
    }

    public function storeIntervenant(Request $request): RedirectResponse
    {
        $this->staffAccess();

        $validated = $request->validate([
            'nom'        => ['required', 'string', 'max:255'],
            'prenom'     => ['required', 'string', 'max:255'],
            'email'      => ['nullable', 'email', 'max:255'],
            'telephone'  => ['nullable', 'string', 'max:50'],
            'specialite' => ['nullable', 'string', 'max:255'],
            'biographie' => ['nullable', 'string', 'max:2000'],
            'tarif_jour' => ['nullable', 'numeric', 'min:0'],
            'role'       => ['required', 'in:intervenant,jury,formateur'],
        ]);

        Intervenant::create(array_merge($validated, [
            'evenement_id' => null,
        ]));

        return back()->with('success', 'Intervenant ajouté au vivier.');
    }

    public function destroyIntervenant(Intervenant $intervenant): RedirectResponse
    {
        $this->staffAccess();
        $intervenant->delete();
        return back()->with('success', 'Intervenant supprimé.');
    }
}
