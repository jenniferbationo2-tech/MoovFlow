<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\IntervenantSession;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IntervenantController extends Controller
{
    /**
     * Affiche la liste des intervenants et de leurs sessions.
     */
    public function index(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('logistique.view'), 403);

        $sessions = Session::query()
            ->with(['salle', 'intervenantsSessions.user'])
            ->where('evenement_id', $evenement->id)
            ->orderBy('heure_debut')
            ->get();

        $intervenants = $sessions
            ->flatMap(fn (Session $session) => $session->intervenantsSessions)
            ->groupBy('user_id')
            ->map(function ($assignments): array {
                $first = $assignments->first();

                return [
                    'id' => $first->user?->id,
                    'name' => $first->user?->name,
                    'email' => $first->user?->email,
                    'roles' => $assignments->pluck('role')->unique()->values()->all(),
                    'sessions' => $assignments->map(fn (IntervenantSession $assignment): array => [
                        'assignment_id' => $assignment->id,
                        'id' => $assignment->session?->id,
                        'titre' => $assignment->session?->titre,
                        'role' => $assignment->role,
                        'heure_debut' => optional($assignment->session?->heure_debut)?->toIso8601String(),
                        'heure_fin' => optional($assignment->session?->heure_fin)?->toIso8601String(),
                        'salle' => $assignment->session?->salle ? [
                            'id' => $assignment->session->salle->id,
                            'nom' => $assignment->session->salle->nom,
                        ] : null,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();

        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->all();

        $sessionsPayload = $sessions->map(fn (Session $session): array => [
            'id' => $session->id,
            'titre' => $session->titre,
            'heure_debut' => optional($session->heure_debut)?->toIso8601String(),
            'heure_fin' => optional($session->heure_fin)?->toIso8601String(),
            'salle' => $session->salle ? $session->salle->nom : null,
            'intervenants_count' => $session->intervenantsSessions->count(),
        ])->all();

        return Inertia::render('Logistique/Intervenants/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'intervenants' => $intervenants,
            'sessions' => $sessionsPayload,
            'utilisateurs' => $users,
            'stats' => [
                'total_intervenants' => count($intervenants),
                'sessions_couvertes' => collect($sessionsPayload)->where('intervenants_count', '>', 0)->count(),
            ],
            'perdiems' => $this->buildPerdiems($intervenants),
        ]);
    }

    /**
     * Assigne un intervenant à une session.
     */
    public function assignToSession(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('logistique.manage'), 403);

        $validated = $request->validate([
            'session_id' => ['required', 'exists:sessions_evenement,id'],
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'string', 'max:100'],
        ]);

        IntervenantSession::query()->firstOrCreate([
            'session_id' => $validated['session_id'],
            'user_id' => $validated['user_id'],
        ], [
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Intervenant assigné à la session.');
    }

    /**
     * Retire un intervenant d'une session.
     */
    public function removeFromSession(IntervenantSession $intervenantSession): RedirectResponse
    {
        abort_unless(request()->user()?->can('logistique.manage'), 403);

        $intervenantSession->delete();

        return back()->with('success', 'Intervenant retiré de la session.');
    }

    /**
     * Affiche une synthèse des perdiems et frais.
     */
    public function perdiems(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('logistique.view'), 403);

        $sessions = Session::query()
            ->with('intervenantsSessions.user')
            ->where('evenement_id', $evenement->id)
            ->get();

        $intervenants = $sessions
            ->flatMap(fn (Session $session) => $session->intervenantsSessions)
            ->groupBy('user_id')
            ->values()
            ->all();

        return Inertia::render('Logistique/Intervenants/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'intervenants' => [],
            'sessions' => [],
            'utilisateurs' => [],
            'stats' => [
                'total_intervenants' => count($intervenants),
                'sessions_couvertes' => 0,
            ],
            'perdiems' => $this->buildPerdiemsFromGroups($intervenants),
            'focusSection' => 'perdiems',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $intervenants
     * @return array<int, array<string, mixed>>
     */
    private function buildPerdiems(array $intervenants): array
    {
        return collect($intervenants)
            ->map(fn (array $intervenant): array => [
                'id' => $intervenant['id'],
                'intervenant' => $intervenant['name'],
                'email' => $intervenant['email'],
                'sessions' => count($intervenant['sessions']),
                'perdiem_estime' => count($intervenant['sessions']) * 25000,
                'transport_estime' => count($intervenant['sessions']) > 0 ? 15000 : 0,
                'total_estime' => (count($intervenant['sessions']) * 25000) + (count($intervenant['sessions']) > 0 ? 15000 : 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $groups
     * @return array<int, array<string, mixed>>
     */
    private function buildPerdiemsFromGroups(array $groups): array
    {
        return collect($groups)
            ->map(function ($assignments): array {
                $first = collect($assignments)->first();
                $count = count($assignments);

                return [
                    'id' => $first?->user?->id,
                    'intervenant' => $first?->user?->name,
                    'email' => $first?->user?->email,
                    'sessions' => $count,
                    'perdiem_estime' => $count * 25000,
                    'transport_estime' => $count > 0 ? 15000 : 0,
                    'total_estime' => ($count * 25000) + ($count > 0 ? 15000 : 0),
                ];
            })
            ->values()
            ->all();
    }
}