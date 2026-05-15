<?php

namespace App\Services;

use App\Mail\InscriptionMail;
use App\Models\EmailLog;
use App\Models\Inscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InscriptionEmailService
{
    /**
     * Envoyer un email lié à une inscription et le logger en BDD.
     *
     * @param  Inscription  $inscription  L'inscription concernée
     * @param  string       $type         Type d'email (constantes EmailLog::TYPE_*)
     * @param  array        $donnees      Données supplémentaires (motif, lien, etc.)
     */
    public function envoyer(Inscription $inscription, string $type, array $donnees = []): EmailLog
    {
        $destinataire = $inscription->user?->email
                       ?? $inscription->email
                       ?? null;

        // Créer le log AVANT envoi
        $log = EmailLog::create([
            'inscription_id' => $inscription->id,
            'type'           => $type,
            'destinataire'   => $destinataire ?? 'inconnu@moov.bf',
            'sujet'          => $this->getSujet($type, $inscription),
            'statut'         => EmailLog::STATUT_QUEUED,
        ]);

        // Si pas de destinataire, on log et on stoppe
        if (!$destinataire) {
            $log->marquerEchec('Aucun destinataire trouvé');
            return $log;
        }

        // Tentative d'envoi
        try {
            Mail::to($destinataire)
                ->send(new InscriptionMail($inscription, $type, $donnees));

            $log->marquerEnvoye();

            Log::info("Email envoyé : type={$type}, inscription_id={$inscription->id}, dest={$destinataire}");

        } catch (\Exception $e) {
            $log->marquerEchec($e->getMessage());

            Log::error("Échec envoi email : type={$type}, inscription_id={$inscription->id}, erreur={$e->getMessage()}");
        }

        return $log;
    }

   
    private function getSujet(string $type, Inscription $inscription): string
    {
        $titreEvent = $inscription->evenement?->titre ?? 'Événement';

        return match ($type) {
            'preinscription_recue' => ' Votre pré-inscription reçue — ' . $titreEvent,
            'preselectionne'       => ' Vous êtes présélectionné(e) — ' . $titreEvent,
            'dossier_recu'         => ' Votre dossier reçu — ' . $titreEvent,
            'accepte'              => ' Bienvenue à ' . $titreEvent . ' !',
            'refuse'               => 'Réponse à votre candidature — ' . $titreEvent,
            'rappel_veille'        => ' Demain ! — ' . $titreEvent,
            'remerciement'         => ' Merci pour votre participation — ' . $titreEvent,
            'rappel_dossier'       => ' Pensez à compléter votre dossier — ' . $titreEvent,
            default                => 'Notification — ' . $titreEvent,
        };
    }
}