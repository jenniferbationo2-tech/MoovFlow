<?php

namespace App\Http\Controllers;

use App\Models\CommunicationCampaign;
use App\Models\Evenement;
use App\Models\User;
use App\Services\CampaignService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService
    ) {
    }

    /**
     * Affiche les campagnes d'invitation déjà envoyées pour un événement.
     */
    public function campaigns(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('communication.view'), 403);

        $campaigns = $evenement->communicationCampaigns()
            ->withCount('invitations')
            ->latest('date_envoi')
            ->get()
            ->map(fn (CommunicationCampaign $campaign): array => [
                'id' => $campaign->id,
                'objet' => $campaign->objet,
                'statut' => $campaign->statut,
                'date_envoi' => optional($campaign->date_envoi)?->toIso8601String(),
                'nb_destinataires' => $campaign->nb_destinataires ?? $campaign->invitations_count,
                'nb_ouvertures' => $campaign->nb_ouvertures,
                'nb_acceptations' => $campaign->nb_acceptations,
                'taux_acceptation' => ($campaign->nb_destinataires ?? 0) > 0
                    ? round((($campaign->nb_acceptations ?? 0) / $campaign->nb_destinataires) * 100, 1)
                    : 0,
            ])
            ->all();

        return Inertia::render('Communication/Campaigns/Index', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'campaigns' => $campaigns,
            'stats' => [
                'envoyees' => $evenement->communicationCampaigns()->where('statut', 'envoyee')->count(),
                'ouvertes' => (int) $evenement->communicationCampaigns()->sum('nb_ouvertures'),
                'acceptees' => (int) $evenement->communicationCampaigns()->sum('nb_acceptations'),
            ],
        ]);
    }

    /**
     * Affiche le formulaire de création de campagne email.
     */
    public function createCampaign(Evenement $evenement): Response
    {
        abort_unless(request()->user()?->can('communication.manage'), 403);

        $participants = $evenement->inscriptions()
            ->with('user:id,name,email')
            ->get()
            ->map(fn ($inscription): ?array => $inscription->user ? [
                'id' => $inscription->user->id,
                'name' => $inscription->user->name,
                'email' => $inscription->user->email,
            ] : null)
            ->filter()
            ->values()
            ->all();

        return Inertia::render('Communication/Campaigns/Create', [
            'evenement' => [
                'id' => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'participants' => $participants,
            'templates' => $this->emailTemplates(),
        ]);
    }

    /**
     * Enregistre puis envoie ou planifie une campagne d'invitations.
     */
    public function sendCampaign(Request $request, Evenement $evenement): RedirectResponse
    {
        abort_unless($request->user()?->can('communication.send'), 403);

        $validated = $request->validate([
            'objet' => ['required', 'string', 'max:255'],
            'contenu' => ['required', 'string'],
            'mode_destinataires' => ['required', Rule::in(['tous_participants', 'participants_evenement', 'liste_personnalisee'])],
            'emails_personnalises' => ['nullable', 'array'],
            'emails_personnalises.*' => ['required', 'email'],
            'date_planification' => ['nullable', 'date', 'after_or_equal:now'],
        ]);

        $emails = $this->resolveCampaignEmails($evenement, $validated);
        $datePlanification = ! empty($validated['date_planification'])
            ? Carbon::parse($validated['date_planification'])
            : null;

        $campaign = CommunicationCampaign::query()->create([
            'evenement_id' => $evenement->id,
            'objet' => $validated['objet'],
            'contenu' => $validated['contenu'],
            'mode_destinataires' => $validated['mode_destinataires'],
            'emails_personnalises' => $validated['emails_personnalises'] ?? [],
            'statut' => $datePlanification && $datePlanification->isFuture() ? 'planifiee' : 'envoyee',
            'date_envoi' => $datePlanification ?? now(),
            'nb_destinataires' => count($emails),
            'nb_ouvertures' => 0,
            'nb_acceptations' => 0,
        ]);

        $invitations = $this->campaignService->sendBulkInvitations(
            $evenement,
            $emails,
            $validated['contenu'],
            $campaign,
            $datePlanification,
        );

        if (! ($datePlanification && $datePlanification->isFuture())) {
            $campaign->update([
                'date_envoi' => now(),
                'nb_destinataires' => count($invitations),
            ]);
        }

        return redirect()
            ->route('communication.campaigns.index', $evenement)
            ->with('success', 'Campagne enregistrée et traitée avec succès.');
    }

    /**
     * Affiche le catalogue de modèles d'emails réutilisables.
     */
    public function templates(): Response
    {
        abort_unless(request()->user()?->can('communication.view'), 403);

        return Inertia::render('Communication/Templates/Index', [
            'templates' => $this->emailTemplates(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, string>
     */
    private function resolveCampaignEmails(Evenement $evenement, array $validated): array
    {
        if ($validated['mode_destinataires'] === 'liste_personnalisee') {
            return collect($validated['emails_personnalises'] ?? [])
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return $evenement->inscriptions()
            ->with('user:id,email')
            ->get()
            ->map(fn ($inscription) => $inscription->user?->email)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function emailTemplates(): array
    {
        return [
            [
                'id' => 'invitation-standard',
                'nom' => 'Invitation standard',
                'objet' => 'Invitation officielle à votre événement',
                'contenu' => "Bonjour,\n\nNous avons le plaisir de vous inviter à notre événement.\n\nCordialement,\nL'équipe MOOT Event",
            ],
            [
                'id' => 'relance-participant',
                'nom' => 'Relance participant',
                'objet' => 'Rappel de participation',
                'contenu' => "Bonjour,\n\nNous vous rappelons que votre présence est attendue.\n\nMerci,\nL'équipe MOOT Event",
            ],
            [
                'id' => 'merci-presence',
                'nom' => 'Remerciement après événement',
                'objet' => 'Merci pour votre participation',
                'contenu' => "Bonjour,\n\nMerci pour votre participation active à notre événement.\n\nÀ très bientôt,\nL'équipe MOOT Event",
            ],
        ];
    }
}