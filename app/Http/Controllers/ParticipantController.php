<?php

namespace App\Http\Controllers;

use App\Imports\ParticipantsImport;
use App\Models\Evenement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ParticipantController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('inscriptions.view'), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'evenement' => $request->string('evenement')->toString(),
        ];

        $query = User::query()
            ->role('participant')
            ->withCount('inscriptions')
            ->withMax('inscriptions', 'created_at')
            ->when($filters['search'] !== '', function ($builder) use ($filters): void {
                $builder->where(function ($userQuery) use ($filters): void {
                    $userQuery
                        ->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%')
                        ->orWhere('telephone', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when($filters['evenement'] !== '', function ($builder) use ($filters): void {
                $builder->whereHas('inscriptions', fn ($inscriptions) => $inscriptions->where('evenement_id', $filters['evenement']));
            });

        $participants = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'nb_evenements' => $user->inscriptions_count,
                'derniere_inscription' => $user->inscriptions_max_created_at
                    ? Carbon::parse($user->inscriptions_max_created_at)->toIso8601String()
                    : null,
            ]);

        return Inertia::render('Participants/Index', [
            'participants' => $participants,
            'filters' => $filters,
            'evenements' => Evenement::query()->orderBy('titre')->get(['id', 'titre']),
        ]);
    }

    public function show(User $user): Response
    {
        abort_unless(request()->user()?->can('inscriptions.view'), 403);

        $user->load([
            'inscriptions.evenement',
            'inscriptions.tarif',
            'inscriptions.paiement.facture',
            'inscriptions.presence',
        ]);

        $totalInscriptions = $user->inscriptions->count();
        $totalPresences = $user->inscriptions->filter(fn ($inscription) => $inscription->presence !== null)->count();
        $totalPaiements = $user->inscriptions->filter(fn ($inscription) => $inscription->paiement?->statut === 'paye')->count();

        return Inertia::render('Participants/Show', [
            'participant' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'historique' => $user->inscriptions->map(fn ($inscription): array => [
                    'id' => $inscription->id,
                    'evenement' => $inscription->evenement?->titre,
                    'date_evenement' => optional($inscription->evenement?->date_debut)?->toIso8601String(),
                    'tarif' => $inscription->tarif?->nom ?? 'Gratuit',
                    'statut' => $inscription->statut,
                    'paiement_statut' => $inscription->paiement?->statut ?? 'gratuit',
                    'presence' => $inscription->presence ? 'present' : 'absent',
                ])->values()->all(),
                'stats' => [
                    'total_inscriptions' => $totalInscriptions,
                    'total_presences' => $totalPresences,
                    'total_paiements' => $totalPaiements,
                    'taux_presence' => $totalInscriptions > 0 ? round(($totalPresences / $totalInscriptions) * 100, 1) : 0,
                ],
            ],
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('inscriptions.view'), 403);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls,txt'],
        ]);

        Excel::import(new ParticipantsImport(), $validated['file']);

        return back()->with('success', 'Import des participants termine avec succes.');
    }
}