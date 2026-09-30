<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnnuaireController extends Controller
{
    /**
     * Annuaire des participants (vue staff).
     * Liste les utilisateurs ayant participé à au moins un événement.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        abort_unless(
            $user->hasAnyRole(['responsable_dcirp', 'organisateur']),
            403,
            'Accès réservé au staff.'
        );

        // Sous-requête : participants ayant au moins une inscription
        $participantQuery = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'participant'))
            ->whereHas('inscriptions');

        // Si organisateur → uniquement les participants de SES événements
        if ($user->hasRole('organisateur') && !$user->hasRole('responsable_dcirp')) {
            $participantQuery->whereHas('inscriptions.evenement',
                fn ($q) => $q->where('created_by', $user->id)
            );
        }

        // Recherche
        if ($request->filled('search')) {
            $q = $request->search;
            $participantQuery->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', "%$q%")
                    ->orWhere('prenom', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%")
                    ->orWhere('telephone', 'like', "%$q%");
            });
        }

        // Filtre par événement
        if ($request->filled('evenement_id')) {
            $participantQuery->whereHas('inscriptions',
                fn ($q) => $q->where('evenement_id', $request->evenement_id)
            );
        }

        // Filtre par statut d'inscription
        if ($request->filled('statut')) {
            $participantQuery->whereHas('inscriptions',
                fn ($q) => $q->where('statut', $request->statut)
            );
        }

        // Charger les compteurs et la dernière inscription
        $participantQuery->withCount([
            'inscriptions',
            'inscriptions as confirmees_count' => fn ($q) =>
                $q->whereIn('statut', ['confirmee', 'present']),
            'inscriptions as presents_count' => fn ($q) =>
                $q->where('statut', 'present'),
        ])->with([
            'inscriptions' => fn ($q) =>
                $q->latest()->take(1)->with('evenement:id,titre,date_debut'),
        ]);

        $participants = $participantQuery
            ->orderBy('nom')
            ->paginate(20)
            ->through(fn (User $u): array => [
                'id'                 => $u->id,
                'nom'                => $u->nom,
                'prenom'             => $u->prenom,
                'email'              => $u->email,
                'telephone'          => $u->telephone,
                'is_active'          => (bool) $u->is_active,
                'created_at'         => optional($u->created_at)?->toIso8601String(),
                'inscriptions_count' => $u->inscriptions_count,
                'confirmees_count'   => $u->confirmees_count,
                'presents_count'     => $u->presents_count,
                'derniere_inscription' => $u->inscriptions->first() ? [
                    'id'        => $u->inscriptions->first()->id,
                    'statut'    => $u->inscriptions->first()->statut,
                    'evenement' => $u->inscriptions->first()->evenement ? [
                        'titre'      => $u->inscriptions->first()->evenement->titre,
                        'date_debut' => optional($u->inscriptions->first()->evenement->date_debut)?->toIso8601String(),
                    ] : null,
                ] : null,
            ]);

        // Statistiques globales
        $statsQuery = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'participant'))
            ->whereHas('inscriptions');

        if ($user->hasRole('organisateur') && !$user->hasRole('responsable_dcirp')) {
            $statsQuery->whereHas('inscriptions.evenement',
                fn ($q) => $q->where('created_by', $user->id)
            );
        }

        $totalParticipants = (clone $statsQuery)->count();

        $inscriptionsQuery = Inscription::query();
        if ($user->hasRole('organisateur') && !$user->hasRole('responsable_dcirp')) {
            $inscriptionsQuery->whereHas('evenement', fn ($q) => $q->where('created_by', $user->id));
        }

        $totalInscriptions  = (clone $inscriptionsQuery)->count();
        $totalConfirmees    = (clone $inscriptionsQuery)->whereIn('statut', ['confirmee', 'present'])->count();
        $totalPresents      = (clone $inscriptionsQuery)->where('statut', 'present')->count();
        $tauxPresence       = $totalConfirmees > 0
            ? round(($totalPresents / $totalConfirmees) * 100, 1)
            : 0;

        $stats = [
            'total_participants' => $totalParticipants,
            'total_inscriptions' => $totalInscriptions,
            'total_confirmees'   => $totalConfirmees,
            'total_presents'     => $totalPresents,
            'taux_presence'      => $tauxPresence,
        ];

        // Liste des événements pour filtre
        $evenements = Evenement::query()
            ->select('id', 'titre')
            ->when(
                $user->hasRole('organisateur') && !$user->hasRole('responsable_dcirp'),
                fn ($q) => $q->where('created_by', $user->id)
            )
            ->orderBy('titre')
            ->get();

        return Inertia::render('Annuaire/Index', [
            'participants' => $participants,
            'stats'        => $stats,
            'evenements'   => $evenements,
            'filters'      => $request->only(['search', 'evenement_id', 'statut']),
        ]);
    }

    /**
     * Détail complet d'un participant.
     */
    public function show(User $user, Request $request): Response
    {
        $auth = Auth::user();

        abort_unless(
            $auth->hasAnyRole(['responsable_dcirp', 'organisateur']),
            403
        );

        $user->load([
            'inscriptions.evenement.typeEvenement',
            'inscriptions.evenement.lieu',
            'inscriptions.tarif',
        ]);

        // Si organisateur → uniquement les inscriptions à ses événements
        $inscriptions = $user->inscriptions;
        if ($auth->hasRole('organisateur') && !$auth->hasRole('responsable_dcirp')) {
            $inscriptions = $inscriptions->filter(fn ($i) =>
                $i->evenement && (int) $i->evenement->created_by === (int) $auth->id
            );
        }

        return Inertia::render('Annuaire/Show', [
            'participant' => [
                'id'        => $user->id,
                'nom'       => $user->nom,
                'prenom'    => $user->prenom,
                'email'     => $user->email,
                'telephone' => $user->telephone,
                'is_active' => (bool) $user->is_active,
                'created_at'=> optional($user->created_at)?->toIso8601String(),
            ],
            'inscriptions' => $inscriptions->values()->map(fn ($i) => [
                'id'         => $i->id,
                'statut'     => $i->statut,
                'qr_code'    => $i->qr_code,
                'created_at' => optional($i->created_at)?->toIso8601String(),
                'evenement'  => $i->evenement ? [
                    'id'         => $i->evenement->id,
                    'titre'      => $i->evenement->titre,
                    'date_debut' => optional($i->evenement->date_debut)?->toIso8601String(),
                    'lieu'       => $i->evenement->lieu ? ['nom' => $i->evenement->lieu->nom] : null,
                    'type'       => $i->evenement->typeEvenement ? [
                        'nom'  => $i->evenement->typeEvenement->nom,
                        'code' => $i->evenement->typeEvenement->code,
                    ] : null,
                ] : null,
                'tarif' => $i->tarif ? [
                    'libelle' => $i->tarif->libelle,
                    'montant' => (float) $i->tarif->montant,
                    'devise'  => $i->tarif->devise ?? 'XOF',
                ] : null,
            ])->all(),
        ]);
    }
}