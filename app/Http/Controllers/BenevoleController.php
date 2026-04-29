<?php

namespace App\Http\Controllers;

use App\Models\BenevoleAffectation;
use App\Models\Evenement;
use App\Models\User;
use App\Services\VolunteerSchedulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BenevoleController extends Controller
{
    public function __construct(
        private readonly VolunteerSchedulerService $volunteerSchedulerService
    ) {
    }

    /**
     * Affiche les bénévoles affectés et leur planning.
     */
    public function index(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('logistique.view'), 403);

        $affectations = BenevoleAffectation::query()
            ->with('user')
            ->where('evenement_id', $evenement->id)
            ->orderBy('creneau_debut')
            ->get();

        $rows = $affectations->map(fn (BenevoleAffectation $affectation): array => [
            'id' => $affectation->id,
            'user' => [
                'id' => $affectation->user?->id,
                'name' => $affectation->user?->name,
                'email' => $affectation->user?->email,
            ],
            'poste' => $affectation->poste,
            'creneau_debut' => optional($affectation->creneau_debut)?->toIso8601String(),
            'creneau_fin' => optional($affectation->creneau_fin)?->toIso8601String(),
        ])->all();

        return Inertia::render('Logistique/Benevoles/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'benevoles' => $rows,
            'utilisateurs' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->toArray(),
            'stats' => [
                'total_benevoles' => $affectations->pluck('user_id')->unique()->count(),
                'postes_couverts' => $affectations->pluck('poste')->unique()->count(),
            ],
            'postes' => $affectations->pluck('poste')->unique()->values()->all(),
        ]);
    }

    /**
     * Affecte un bénévole à un poste.
     */
    public function store(Request $request, Evenement $evenement): RedirectResponse
    {
        abort_unless($request->user()?->can('logistique.manage'), 403);

        $validated = $this->validateAffectation($request);
        $validated['evenement_id'] = $evenement->id;

        BenevoleAffectation::query()->create($validated);

        return back()->with('success', 'Bénévole affecté avec succès.');
    }

    /**
     * Met à jour une affectation.
     */
    public function update(Request $request, BenevoleAffectation $benevoleAffectation): RedirectResponse
    {
        abort_unless($request->user()?->can('logistique.manage'), 403);

        $validated = $this->validateAffectation($request);

        $benevoleAffectation->update($validated);

        return back()->with('success', 'Affectation bénévole mise à jour.');
    }

    /**
     * Retire une affectation.
     */
    public function destroy(BenevoleAffectation $benevoleAffectation): RedirectResponse
    {
        abort_unless(request()->user()?->can('logistique.manage'), 403);

        $benevoleAffectation->delete();

        return back()->with('success', 'Affectation retirée.');
    }

    /**
     * Affiche le planning de rotation des bénévoles.
     */
    public function planning(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('logistique.view'), 403);

        return Inertia::render('Logistique/Benevoles/Planning', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'planning' => $this->volunteerSchedulerService->generatePlanning($evenement->id),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAffectation(Request $request): array
    {
        return $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'poste' => ['required', 'string', 'max:255'],
            'creneau_debut' => ['required', 'date'],
            'creneau_fin' => ['required', 'date', 'after:creneau_debut'],
        ]);
    }
}