<?php

namespace App\Services;

use App\Models\CommunicationCampaign;
use App\Models\EmailLog;
use App\Models\Evenement;
use App\Models\Inscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailCampaignService
{
    /**
     * Remplace les variables dynamiques dans un texte.
     * Variables : {prenom}, {nom}, {email}, {evenement}, {date_evenement}, {lieu}, {reference}, {app_name}
     */
    public function remplacerVariables(string $texte, ?User $user = null, ?Evenement $evenement = null, ?Inscription $inscription = null): string
    {
        $remplacements = [
            '{prenom}'   => $user?->prenom ?? '[Prénom]',
            '{nom}'      => $user?->nom ?? '[Nom]',
            '{email}'    => $user?->email ?? '[Email]',
            '{evenement}' => $evenement?->titre ?? '[Événement]',
            '{date_evenement}' => $evenement?->date_debut
                ? Carbon::parse($evenement->date_debut)->locale('fr')->isoFormat('LL')
                : '[Date]',
            '{lieu}'      => $evenement?->lieu?->nom ?? '[Lieu]',
            '{reference}' => $inscription?->reference ?? '[Référence]',
            '{app_name}'  => setting('app_name', 'MoovFlow'),
        ];

        return str_replace(array_keys($remplacements), array_values($remplacements), $texte);
    }

    /**
     * Récupère les destinataires d'une campagne selon le mode_destinataires.
     */
    public function getDestinataires(CommunicationCampaign $campagne): Collection
    {
        $query = Inscription::where('evenement_id', $campagne->evenement_id)
            ->with('user');

        switch ($campagne->mode_destinataires) {
            case 'tous':
                // Tous sauf brouillon
                $query->whereNotIn('statut', ['en_attente_validation_responsable']);
                break;

            case 'valides':
                $query->whereIn('statut', ['validee', 'present']);
                break;

            case 'presents':
                $query->where('statut', 'present');
                break;

            case 'absents':
                $query->where('statut', 'absente');
                break;

            case 'refuses':
                $query->where('statut', 'refusee');
                break;

            case 'personnalises':
                // Si emails personnalisés sont définis
                $emails = $campagne->emails_personnalises ?? [];
                if (count($emails) > 0) {
                    $query->whereHas('user', function ($q) use ($emails) {
                        $q->whereIn('email', $emails);
                    });
                } else {
                    return collect();
                }
                break;

            default:
                $query->where('statut', 'validee');
        }

        return $query->get();
    }

    /**
     * Compte le nombre de destinataires sans envoyer (pour le preview).
     */
    public function compterDestinataires(int $evenementId, string $mode): int
    {
        $query = Inscription::where('evenement_id', $evenementId);

        switch ($mode) {
            case 'tous':
                $query->whereNotIn('statut', ['en_attente_validation_responsable']);
                break;
            case 'valides':
                $query->whereIn('statut', ['validee', 'present']);
                break;
            case 'presents':
                $query->where('statut', 'present');
                break;
            case 'absents':
                $query->where('statut', 'absente');
                break;
            case 'refuses':
                $query->where('statut', 'refusee');
                break;
            default:
                $query->where('statut', 'validee');
        }

        return $query->count();
    }

    /**
     * Envoie la campagne à tous ses destinataires.
     * Retourne un résumé : ['envoyes' => X, 'erreurs' => Y]
     */
    public function envoyerCampagne(CommunicationCampaign $campagne): array
    {
        $resume = [
            'envoyes' => 0,
            'erreurs' => 0,
            'total'   => 0,
        ];

        // Charger l'événement
        $campagne->load('evenement.lieu');
        $evenement = $campagne->evenement;

        // Récupérer les destinataires
        $destinataires = $this->getDestinataires($campagne);
        $resume['total'] = $destinataires->count();

        if ($resume['total'] === 0) {
            return $resume;
        }

        // Envoyer à chacun
        foreach ($destinataires as $inscription) {
            $user = $inscription->user;
            if (!$user || !$user->email) {
                $resume['erreurs']++;
                continue;
            }

            // Personnaliser le contenu
            $sujetPerso = $this->remplacerVariables(
                $campagne->objet,
                $user,
                $evenement,
                $inscription
            );

            $corpsPerso = $this->remplacerVariables(
                $campagne->contenu,
                $user,
                $evenement,
                $inscription
            );

            // Créer un log d'envoi
            $log = EmailLog::create([
                'inscription_id' => $inscription->id,
                'campagne_id'    => $campagne->id,
                'type'           => 'rappel_veille', // mappé à un enum existant
                'destinataire'   => $user->email,
                'sujet'          => $sujetPerso,
                'contenu'        => $corpsPerso,
                'statut'         => 'queued',
            ]);

            // Envoyer le mail
            try {
                Mail::raw($corpsPerso, function ($message) use ($user, $sujetPerso) {
                    $message->to($user->email)
                            ->subject($sujetPerso);
                });

                $log->update([
                    'statut'     => 'sent',
                    'envoye_at'  => now(),
                ]);

                $resume['envoyes']++;
            } catch (\Exception $e) {
                $log->update([
                    'statut' => 'failed',
                    'erreur' => substr($e->getMessage(), 0, 500),
                ]);

                Log::error('Erreur envoi campagne', [
                    'campagne_id' => $campagne->id,
                    'user_id'     => $user->id,
                    'error'       => $e->getMessage(),
                ]);

                $resume['erreurs']++;
            }
        }

        // Mettre à jour la campagne
        $campagne->update([
            'statut'           => 'envoyee',
            'date_envoi'       => now(),
            'nb_destinataires' => $resume['total'],
        ]);

        return $resume;
    }

    /**
     * Génère un aperçu de la campagne pour un user de démo.
     */
    public function genererApercu(CommunicationCampaign $campagne, ?User $userTest = null): array
    {
        $campagne->load('evenement.lieu');

        // Si pas d'user test, prendre le premier participant validé
        if (!$userTest) {
            $inscription = Inscription::where('evenement_id', $campagne->evenement_id)
                ->whereIn('statut', ['validee', 'present'])
                ->with('user')
                ->first();

            $userTest = $inscription?->user;
        } else {
            $inscription = Inscription::where('evenement_id', $campagne->evenement_id)
                ->where('user_id', $userTest->id)
                ->first();
        }

        return [
            'destinataire' => $userTest?->email ?? '[Aucun destinataire]',
            'objet'        => $this->remplacerVariables(
                                $campagne->objet,
                                $userTest,
                                $campagne->evenement,
                                $inscription ?? null
                              ),
            'corps'        => $this->remplacerVariables(
                                $campagne->contenu,
                                $userTest,
                                $campagne->evenement,
                                $inscription ?? null
                              ),
        ];
    }
}