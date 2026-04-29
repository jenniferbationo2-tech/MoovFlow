<?php

namespace App\Http\Controllers;

use App\Jobs\SendThankYouEmailsJob;
use App\Jobs\SyncContactsJob;
use App\Models\B2BMeeting;
use App\Models\Evenement;
use App\Models\Followup;
use App\Models\MootNotification;
use App\Models\User;
use App\Services\LeadScoringService;
use App\Services\LoyaltyProgramService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CrmController extends Controller
{
    public function __construct(
        private readonly LeadScoringService $leadScoringService,
        private readonly LoyaltyProgramService $loyaltyProgramService
    ) {
    }

    /**
     * Affiche la liste des contacts CRM avec filtres et indicateurs de score.
     */
    public function contacts(Request $request): Response
    {
        abort_unless($request->user()?->can('crm.view'), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'evenement' => $request->string('evenement')->toString(),
            'statut' => $request->string('statut')->toString(),
        ];

        $users = User::query()
            ->with([
                'inscriptions.evenement.typeEvenement',
                'followups',
                'reponsesEnquetes',
                'b2bMeetings',
            ])
            ->withCount('inscriptions')
            ->where(function ($query): void {
                $query
                    ->whereHas('inscriptions')
                    ->orWhereHas('followups')
                    ->orWhereHas('reponsesEnquetes')
                    ->orWhereHas('b2bMeetings');
            })
            ->when($filters['search'] !== '', function ($query) use ($filters): void {
                $query->where(function ($userQuery) use ($filters): void {
                    $userQuery
                        ->where('name', 'like', '%'.$filters['search'].'%')
                        ->orWhere('email', 'like', '%'.$filters['search'].'%')
                        ->orWhere('telephone', 'like', '%'.$filters['search'].'%');
                });
            })
            ->when($filters['evenement'] !== '', function ($query) use ($filters): void {
                $query->whereHas('inscriptions', fn ($inscriptions) => $inscriptions->where('evenement_id', $filters['evenement']));
            })
            ->orderBy('name')
            ->get()
            ->map(function (User $user): array {
                return $this->mapContactListItem($user);
            })
            ->filter(function (array $contact) use ($filters): bool {
                return $filters['statut'] === '' || $contact['classification'] === $filters['statut'];
            })
            ->values();

        return Inertia::render('CRM/Contacts/Index', [
            'contacts' => [
                'data' => $users->all(),
                'total' => $users->count(),
            ],
            'filters' => $filters,
            'evenements' => Evenement::query()->orderBy('titre')->get(['id', 'titre']),
            'statuts' => ['chaud', 'tiede', 'froid'],
            'stats' => [
                'total' => $users->count(),
                'chauds' => $users->where('classification', 'chaud')->count(),
                'tiedes' => $users->where('classification', 'tiede')->count(),
                'froids' => $users->where('classification', 'froid')->count(),
            ],
        ]);
    }

    /**
     * Affiche le profil CRM complet d'un contact avec son historique d'interactions.
     */
    public function showContact(User $user): Response
    {
        abort_unless(request()->user()?->can('crm.view'), 403);

        $user->load([
            'inscriptions.evenement.typeEvenement',
            'inscriptions.presence',
            'reponsesEnquetes.enquete.evenement',
            'followups.evenement',
            'b2bMeetings.evenement',
        ]);

        $score = $this->leadScoringService->score($user);
        $classification = $this->leadScoringService->classify($user);
        $timeline = collect($this->buildTimeline($user))
            ->sortByDesc('date')
            ->values();

        return Inertia::render('CRM/Contacts/Show', [
            'contact' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'telephone' => $user->telephone,
                'societe' => $this->resolveSociete($user),
                'score' => $score,
                'classification' => $classification,
                'nb_evenements' => $user->inscriptions->count(),
                'followups' => $user->followups->map(fn (Followup $followup): array => [
                    'id' => $followup->id,
                    'type' => $followup->type,
                    'statut' => $followup->statut,
                    'notes' => $followup->notes,
                    'date_prevue' => optional($followup->date_prevue)?->toDateString(),
                    'evenement' => $followup->evenement?->titre,
                ])->values()->all(),
                'timeline' => $timeline->all(),
            ],
            'evenements' => Evenement::query()->orderByDesc('date_debut')->get(['id', 'titre']),
            'followupTypes' => ['remerciement', 'relance', 'fidelisation', 'rdv_b2b'],
            'upcomingEvenements' => Evenement::query()
                ->where('date_debut', '>=', now())
                ->orderBy('date_debut')
                ->get(['id', 'titre']),
        ]);
    }

    /**
     * Affiche la liste des actions de suivi post-événement.
     */
    public function followups(Request $request): Response
    {
        abort_unless($request->user()?->can('crm.view'), 403);

        $filters = [
            'type' => $request->string('type')->toString(),
            'statut' => $request->string('statut')->toString(),
        ];

        $followups = Followup::query()
            ->with(['user:id,name,email', 'evenement:id,titre'])
            ->when($filters['type'] !== '', fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['statut'] !== '', fn ($query) => $query->where('statut', $filters['statut']))
            ->latest('date_prevue')
            ->get()
            ->map(fn (Followup $followup): array => [
                'id' => $followup->id,
                'contact' => [
                    'id' => $followup->user?->id,
                    'name' => $followup->user?->name,
                    'email' => $followup->user?->email,
                ],
                'evenement' => $followup->evenement?->titre,
                'type' => $followup->type,
                'statut' => $followup->statut,
                'notes' => $followup->notes,
                'date_prevue' => optional($followup->date_prevue)?->toDateString(),
                'date_realise' => optional($followup->date_realise)?->toDateString(),
                'en_retard' => $followup->statut === 'a_faire'
                    && $followup->date_prevue !== null
                    && $followup->date_prevue->isPast(),
            ])
            ->values();

        return Inertia::render('CRM/Followup/Index', [
            'followups' => $followups->all(),
            'filters' => $filters,
            'types' => ['remerciement', 'relance', 'fidelisation', 'rdv_b2b'],
            'statuts' => ['a_faire', 'fait', 'annule'],
            'contacts' => User::query()->whereHas('inscriptions')->orderBy('name')->get(['id', 'name', 'email']),
            'evenements' => Evenement::query()->orderByDesc('date_debut')->get(['id', 'titre']),
            'stats' => [
                'a_faire' => $followups->where('statut', 'a_faire')->count(),
                'fait' => $followups->where('statut', 'fait')->count(),
                'en_retard' => $followups->where('en_retard', true)->count(),
            ],
        ]);
    }

    /**
     * Crée une nouvelle action de suivi CRM.
     */
    public function createFollowup(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('crm.manage'), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'evenement_id' => ['nullable', 'exists:evenements,id'],
            'type' => ['required', Rule::in(['remerciement', 'relance', 'fidelisation', 'rdv_b2b'])],
            'statut' => ['nullable', Rule::in(['a_faire', 'fait', 'annule'])],
            'notes' => ['nullable', 'string'],
            'date_prevue' => ['nullable', 'date'],
            'date_realise' => ['nullable', 'date'],
        ]);

        Followup::query()->create([
            ...$validated,
            'statut' => $validated['statut'] ?? 'a_faire',
        ]);

        return back()->with('success', 'Action de suivi créée avec succès.');
    }

    /**
     * Met à jour une action de suivi existante.
     */
    public function updateFollowup(Request $request, Followup $followup): RedirectResponse
    {
        abort_unless($request->user()?->can('crm.manage'), 403);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['remerciement', 'relance', 'fidelisation', 'rdv_b2b'])],
            'statut' => ['required', Rule::in(['a_faire', 'fait', 'annule'])],
            'notes' => ['nullable', 'string'],
            'date_prevue' => ['nullable', 'date'],
            'date_realise' => ['nullable', 'date'],
        ]);

        $followup->update($validated);

        return back()->with('success', 'Action de suivi mise à jour.');
    }

    /**
     * Affiche les rendez-vous B2B rattachés à un événement.
     */
    public function b2bMeetings(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('crm.view'), 403);

        $evenement->load(['typeEvenement', 'b2bMeetings.organisateur', 'b2bMeetings.prospect']);

        $meetings = $evenement->b2bMeetings
            ->sortBy('date_rdv')
            ->values()
            ->map(function (B2BMeeting $meeting): array {
                return [
                    'id' => $meeting->id,
                    'prospect' => $meeting->prospect?->name ?? $meeting->prospect_nom ?? 'Prospect à confirmer',
                    'prospect_id' => $meeting->prospect_id,
                    'email' => $meeting->prospect?->email ?? $meeting->prospect_email,
                    'societe' => $meeting->prospect_societe ?? $this->resolveSociete($meeting->prospect),
                    'objet' => $meeting->objet,
                    'date' => optional($meeting->date_rdv)?->toIso8601String(),
                    'lieu' => $meeting->lieu,
                    'statut' => $meeting->statut,
                    'score_lead' => $meeting->score_lead,
                    'notes' => $meeting->notes,
                    'organisateur' => $meeting->organisateur?->name,
                ];
            });

        $contacts = User::query()
            ->with(['inscriptions.evenement.typeEvenement', 'followups', 'reponsesEnquetes', 'b2bMeetings'])
            ->whereHas('inscriptions')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'score' => $this->leadScoringService->score($user),
                'classification' => $this->leadScoringService->classify($user),
            ])
            ->values();

        return Inertia::render('CRM/B2B/Meetings', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
                'type' => $evenement->typeEvenement?->nom,
            ],
            'meetings' => $meetings->all(),
            'contacts' => $contacts->all(),
            'stats' => [
                'total' => $meetings->count(),
                'confirmes' => $meetings->where('statut', 'confirme')->count(),
                'realises' => $meetings->where('statut', 'realise')->count(),
            ],
        ]);
    }

    /**
     * Planifie un rendez-vous B2B pour un salon ou forum professionnel.
     */
    public function createMeeting(Request $request, Evenement $evenement): RedirectResponse
    {
        abort_unless($request->user()?->can('crm.manage'), 403);

        $validated = $request->validate([
            'prospect_id' => ['nullable', 'exists:users,id'],
            'prospect_nom' => ['nullable', 'string', 'max:255'],
            'prospect_email' => ['nullable', 'email'],
            'prospect_societe' => ['nullable', 'string', 'max:255'],
            'objet' => ['required', 'string', 'max:255'],
            'date_rdv' => ['required', 'date'],
            'lieu' => ['nullable', 'string', 'max:255'],
            'statut' => ['nullable', Rule::in(['planifie', 'confirme', 'realise', 'annule'])],
            'notes' => ['nullable', 'string'],
        ]);

        $prospect = ! empty($validated['prospect_id'])
            ? User::query()->find($validated['prospect_id'])
            : null;

        B2BMeeting::query()->create([
            'evenement_id' => $evenement->id,
            'organisateur_id' => $request->user()->id,
            'prospect_id' => $prospect?->id,
            'prospect_nom' => $validated['prospect_nom'] ?? $prospect?->name,
            'prospect_email' => $validated['prospect_email'] ?? $prospect?->email,
            'prospect_societe' => $validated['prospect_societe'] ?? $this->resolveSociete($prospect),
            'objet' => $validated['objet'],
            'date_rdv' => $validated['date_rdv'],
            'lieu' => $validated['lieu'] ?? null,
            'statut' => $validated['statut'] ?? 'planifie',
            'notes' => $validated['notes'] ?? null,
            'score_lead' => $prospect ? $this->leadScoringService->score($prospect) : null,
        ]);

        return back()->with('success', 'Rendez-vous B2B planifié avec succès.');
    }

    /**
     * Affiche les participants récurrents du programme fidélité.
     */
    public function loyalty(): Response
    {
        abort_unless(request()->user()?->can('crm.view'), 403);

        $participants = $this->loyaltyProgramService
            ->getRecurringParticipants()
            ->map(function (User $user): array {
                $firstInscription = $user->inscriptions->sortBy('created_at')->first();
                $lastInscription = $user->inscriptions->sortByDesc('created_at')->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nb_evenements' => $user->inscriptions_count,
                    'anciennete' => $user->created_at
                        ? Carbon::parse($user->created_at)->diffForHumans(now(), true)
                        : 'N/A',
                    'dernier_evenement' => $lastInscription?->evenement?->titre,
                    'premiere_participation' => optional($firstInscription?->created_at)?->toIso8601String(),
                ];
            })
            ->values();

        return Inertia::render('CRM/Loyalty/Index', [
            'participants' => $participants->all(),
            'evenements' => Evenement::query()->where('date_debut', '>=', now())->orderBy('date_debut')->get(['id', 'titre']),
            'stats' => [
                'total_fideles' => $participants->count(),
                'nouveaux_ce_mois' => $participants->filter(function (array $participant): bool {
                    return $participant['premiere_participation'] !== null
                        && Carbon::parse($participant['premiere_participation'])->isCurrentMonth();
                })->count(),
            ],
        ]);
    }

    /**
     * Déclenche la synchronisation bidirectionnelle avec le CRM Moov.
     */
    public function syncMoov(): RedirectResponse
    {
        abort_unless(request()->user()?->can('crm.sync'), 403);

        dispatch(new SyncContactsJob());

        return back()->with('success', 'Synchronisation CRM Moov lancée.');
    }

    /**
     * Déclenche l'envoi des emails de remerciement post-événement.
     */
    public function sendThankYou(Evenement $evenement): RedirectResponse
    {
        abort_unless(request()->user()?->can('crm.manage'), 403);

        dispatch(new SendThankYouEmailsJob($evenement->id));

        return back()->with('success', 'Envoi des emails de remerciement planifié.');
    }

    /**
     * Envoie une invitation prioritaire à un participant fidèle.
     */
    public function sendPriorityInvitation(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('crm.manage'), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'evenement_id' => ['required', 'exists:evenements,id'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);
        $evenement = Evenement::query()->findOrFail($validated['evenement_id']);

        $this->loyaltyProgramService->sendPriorityInvitation($user, $evenement);

        return back()->with('success', 'Invitation prioritaire envoyée.');
    }

    /**
     * Formate un contact pour la liste CRM.
     *
     * @return array<string, mixed>
     */
    private function mapContactListItem(User $user): array
    {
        $score = $this->leadScoringService->score($user);
        $classification = $this->leadScoringService->classify($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'societe' => $this->resolveSociete($user),
            'nb_evenements' => $user->inscriptions_count ?? $user->inscriptions->count(),
            'score' => $score,
            'classification' => $classification,
            'derniere_interaction' => $this->resolveLastInteraction($user),
        ];
    }

    /**
     * Retourne la société la plus probable d'un contact.
     */
    private function resolveSociete(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $meeting = $user->b2bMeetings->firstWhere('prospect_societe', '!=', null);

        return $meeting?->prospect_societe ?? 'Non renseignée';
    }

    /**
     * Retourne la date de dernière interaction connue du contact.
     */
    private function resolveLastInteraction(User $user): ?string
    {
        $dates = collect()
            ->merge($user->inscriptions->pluck('created_at'))
            ->merge($user->inscriptions->pluck('presence.scan_time'))
            ->merge($user->reponsesEnquetes->pluck('created_at'))
            ->merge($user->followups->pluck('updated_at'))
            ->merge($user->b2bMeetings->pluck('date_rdv'))
            ->filter();

        $lastDate = $dates->sortDesc()->first();

        return $lastDate ? Carbon::parse($lastDate)->toIso8601String() : null;
    }

    /**
     * Construit la timeline consolidée des interactions d'un contact.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTimeline(User $user): array
    {
        $timeline = [];

        foreach ($user->inscriptions as $inscription) {
            $timeline[] = [
                'type' => 'inscription',
                'titre' => 'Inscription enregistrée',
                'description' => $inscription->evenement?->titre ?? 'Événement',
                'date' => optional($inscription->created_at)?->toIso8601String(),
                'statut' => $inscription->statut,
            ];

            if ($inscription->presence) {
                $timeline[] = [
                    'type' => 'presence',
                    'titre' => 'Présence confirmée',
                    'description' => $inscription->evenement?->titre ?? 'Événement',
                    'date' => optional($inscription->presence->scan_time)?->toIso8601String(),
                    'statut' => 'present',
                ];
            }
        }

        foreach ($user->reponsesEnquetes as $reponse) {
            $timeline[] = [
                'type' => 'enquete',
                'titre' => 'Réponse à une enquête',
                'description' => $reponse->enquete?->titre ?? 'Enquête',
                'date' => optional($reponse->created_at)?->toIso8601String(),
                'statut' => 'termine',
            ];
        }

        foreach ($user->followups as $followup) {
            $timeline[] = [
                'type' => 'suivi',
                'titre' => 'Action de suivi',
                'description' => $followup->notes ?: ucfirst($followup->type),
                'date' => optional($followup->date_prevue ?? $followup->created_at)?->toIso8601String(),
                'statut' => $followup->statut,
            ];
        }

        foreach ($user->b2bMeetings as $meeting) {
            $timeline[] = [
                'type' => 'b2b',
                'titre' => 'Rendez-vous B2B',
                'description' => $meeting->objet,
                'date' => optional($meeting->date_rdv)?->toIso8601String(),
                'statut' => $meeting->statut,
            ];
        }

        foreach (MootNotification::query()->where('user_id', $user->id)->latest('date_envoi')->limit(10)->get() as $notification) {
            $timeline[] = [
                'type' => 'notification',
                'titre' => $notification->titre,
                'description' => $notification->message,
                'date' => optional($notification->date_envoi)?->toIso8601String(),
                'statut' => $notification->statut,
            ];
        }

        return $timeline;
    }
}