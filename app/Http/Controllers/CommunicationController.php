<?php

namespace App\Http\Controllers;

use App\Models\CommunicationCampaign;
use App\Models\EmailLog;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Services\EmailCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationController extends Controller
{
    // ════════════════════════════════════════════
    //   PAGE LISTE DES CAMPAGNES
    // ════════════════════════════════════════════
    public function campaigns(Evenement $evenement): Response
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        $campagnes = CommunicationCampaign::where('evenement_id', $evenement->id)
            ->withCount(['envois as nb_envoyes' => function ($q) {
                $q->where('statut', 'sent');
            }])
            ->withCount(['envois as nb_erreurs' => function ($q) {
                $q->where('statut', 'failed');
            }])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CommunicationCampaign $c) => [
                'id'                  => $c->id,
                'objet'               => $c->objet,
                'mode_destinataires'  => $c->mode_destinataires,
                'statut'              => $c->statut,
                'date_envoi'          => $c->date_envoi?->toIso8601String(),
                'nb_destinataires'    => $c->nb_destinataires,
                'nb_envoyes'          => $c->nb_envoyes,
                'nb_erreurs'          => $c->nb_erreurs,
                'created_at'          => $c->created_at->toIso8601String(),
            ]);

        // Compteurs pour KPIs
        $kpis = [
            'total_campagnes' => $campagnes->count(),
            'campagnes_envoyees' => $campagnes->where('statut', 'envoyee')->count(),
            'campagnes_brouillon' => $campagnes->where('statut', 'brouillon')->count(),
            'total_emails_envoyes' => $campagnes->sum('nb_envoyes'),
        ];

        return Inertia::render('Communication/Campaigns/Index', [
            'evenement' => [
                'id'         => $evenement->id,
                'titre'      => $evenement->titre,
                'date_debut' => $evenement->date_debut?->toIso8601String(),
                'statut'     => $evenement->statut,
            ],
            'campagnes' => $campagnes,
            'kpis'      => $kpis,
        ]);
    }

    // ════════════════════════════════════════════
    //   PAGE CRÉATION CAMPAGNE
    // ════════════════════════════════════════════
    public function createCampaign(Evenement $evenement, EmailCampaignService $service): Response
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        // Compteurs par mode pour aider l'utilisateur
        $compteurs = [
            'tous'      => $service->compterDestinataires($evenement->id, 'tous'),
            'valides'   => $service->compterDestinataires($evenement->id, 'valides'),
            'presents'  => $service->compterDestinataires($evenement->id, 'presents'),
            'absents'   => $service->compterDestinataires($evenement->id, 'absents'),
            'refuses'   => $service->compterDestinataires($evenement->id, 'refuses'),
        ];

        return Inertia::render('Communication/Campaigns/Create', [
            'evenement' => [
                'id'    => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'compteurs'        => $compteurs,
            'cibles'           => CommunicationCampaign::ciblesDisponibles(),
            'modelesPredefinis' => CommunicationCampaign::modelesPredefinis(),
            'variablesDispos'  => [
                '{prenom}'        => 'Prénom du participant',
                '{nom}'           => 'Nom du participant',
                '{email}'         => 'Email du participant',
                '{evenement}'     => "Titre de l'événement",
                '{date_evenement}' => "Date de l'événement",
                '{lieu}'          => "Lieu de l'événement",
                '{reference}'     => "Référence d'inscription",
                '{app_name}'      => "Nom de l'application",
            ],
        ]);
    }

    // ════════════════════════════════════════════
    //   ENREGISTRER + ÉVENTUELLEMENT ENVOYER
    // ════════════════════════════════════════════
    public function sendCampaign(Request $request, Evenement $evenement, EmailCampaignService $service): RedirectResponse
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        $validated = $request->validate([
            'objet'              => ['required', 'string', 'max:255'],
            'contenu'            => ['required', 'string', 'min:10'],
            'mode_destinataires' => ['required', 'in:tous,valides,presents,absents,refuses'],
            'action'             => ['required', 'in:brouillon,envoyer'],
        ]);

        // Créer la campagne
        $campagne = CommunicationCampaign::create([
            'evenement_id'       => $evenement->id,
            'objet'              => $validated['objet'],
            'contenu'            => $validated['contenu'],
            'mode_destinataires' => $validated['mode_destinataires'],
            'statut'             => 'brouillon',
            'nb_destinataires'   => 0,
        ]);

        // Si action = envoyer, envoyer maintenant
        if ($validated['action'] === 'envoyer') {
            $resume = $service->envoyerCampagne($campagne);

            $message = "Campagne envoyée : {$resume['envoyes']} succès";
            if ($resume['erreurs'] > 0) {
                $message .= ", {$resume['erreurs']} erreurs";
            }
            $message .= '.';

            return redirect()
                ->route('communication.campaigns.show', [$evenement, $campagne])
                ->with('success', $message);
        }

        return redirect()
            ->route('communication.campaigns.show', [$evenement, $campagne])
            ->with('success', 'Campagne enregistrée en brouillon.');
    }

    // ════════════════════════════════════════════
    //   DÉTAILS D'UNE CAMPAGNE (avec stats + historique)
    // ════════════════════════════════════════════
    public function showCampaign(Evenement $evenement, CommunicationCampaign $campaign, EmailCampaignService $service): Response
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        // Vérifier que la campagne appartient bien à cet événement
        abort_unless($campaign->evenement_id === $evenement->id, 404);

        // Charger les envois avec users
        $envois = EmailLog::where('campagne_id', $campaign->id)
            ->with('inscription.user:id,prenom,nom,email')
            ->latest()
            ->get()
            ->map(fn (EmailLog $log) => [
                'id'           => $log->id,
                'destinataire' => $log->destinataire,
                'sujet'        => $log->sujet,
                'statut'       => $log->statut,
                'erreur'       => $log->erreur,
                'envoye_at'    => $log->envoye_at?->toIso8601String(),
                'created_at'   => $log->created_at->toIso8601String(),
                'user'         => $log->inscription?->user ? [
                    'id'     => $log->inscription->user->id,
                    'prenom' => $log->inscription->user->prenom,
                    'nom'    => $log->inscription->user->nom,
                ] : null,
            ]);

        // Stats
        $stats = [
            'total'         => $envois->count(),
            'nb_envoyes'    => $envois->where('statut', 'sent')->count(),
            'nb_erreurs'    => $envois->where('statut', 'failed')->count(),
            'nb_en_attente' => $envois->where('statut', 'queued')->count(),
        ];

        // Aperçu (au cas où c'est encore en brouillon)
        $apercu = $service->genererApercu($campaign);

        return Inertia::render('Communication/Campaigns/Show', [
            'evenement' => [
                'id'    => $evenement->id,
                'titre' => $evenement->titre,
            ],
            'campagne' => [
                'id'                  => $campaign->id,
                'objet'               => $campaign->objet,
                'contenu'             => $campaign->contenu,
                'mode_destinataires'  => $campaign->mode_destinataires,
                'statut'              => $campaign->statut,
                'date_envoi'          => $campaign->date_envoi?->toIso8601String(),
                'nb_destinataires'    => $campaign->nb_destinataires,
                'created_at'          => $campaign->created_at->toIso8601String(),
            ],
            'envois' => $envois,
            'stats'  => $stats,
            'apercu' => $apercu,
            'cibles' => CommunicationCampaign::ciblesDisponibles(),
        ]);
    }

   
    public function sendExistingCampaign(Evenement $evenement, CommunicationCampaign $campaign, EmailCampaignService $service): RedirectResponse
    {
        $this->autoriserOrganisateurOuAdmin($evenement);

        abort_unless($campaign->evenement_id === $evenement->id, 404);

        if ($campaign->statut !== 'brouillon') {
            return back()->withErrors(['statut' => 'Seuls les brouillons peuvent être envoyés.']);
        }

        $resume = $service->envoyerCampagne($campaign);

        $message = "Campagne envoyée : {$resume['envoyes']} succès";
        if ($resume['erreurs'] > 0) {
            $message .= ", {$resume['erreurs']} erreurs";
        }
        $message .= '.';

        return back()->with('success', $message);
    }

  
    public function previewCampaign(Request $request, EmailCampaignService $service): JsonResponse
    {
        $request->validate([
            'evenement_id' => ['required', 'exists:evenements,id'],
            'objet'        => ['required', 'string'],
            'contenu'      => ['required', 'string'],
        ]);

        $evenement = Evenement::with('lieu')->findOrFail($request->evenement_id);

        // Trouver un user de test
        $inscription = Inscription::where('evenement_id', $evenement->id)
            ->whereIn('statut', ['validee', 'present'])
            ->with('user')
            ->first();

        $userTest = $inscription?->user;

        return response()->json([
            'objet'        => $service->remplacerVariables($request->objet, $userTest, $evenement, $inscription),
            'corps'        => $service->remplacerVariables($request->contenu, $userTest, $evenement, $inscription),
            'destinataire' => $userTest?->email ?? '[Aucun destinataire test]',
        ]);
    }

    public function templates(): Response
    {
        return Inertia::render('Communication/Templates/Index', [
            'modeles' => CommunicationCampaign::modelesPredefinis(),
        ]);
    }

    
    private function autoriserOrganisateurOuAdmin(Evenement $evenement): void
    {
        $user = Auth::user();

        abort_unless(
            $user->hasRole('responsable_dcirp') ||
                ($user->hasRole('organisateur') && $evenement->created_by === $user->id),
            403,
            'Vous n\'avez pas accès à cet événement.'
        );
    }
}