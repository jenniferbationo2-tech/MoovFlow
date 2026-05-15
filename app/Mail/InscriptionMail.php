<?php

namespace App\Mail;

use App\Models\Inscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscriptionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inscription $inscription,
        public string $type,
        public array $donnees = [],
    ) {}

    public function envelope(): Envelope
    {
        $sujet = match ($this->type) {
            'preinscription_recue' => ' Votre pré-inscription a bien été reçue',
            'preselectionne'       => ' Vous êtes présélectionné(e) !',
            'dossier_recu'         => ' Votre dossier complet a été reçu',
            'accepte'              => ' Félicitations, vous êtes accepté(e) !',
            'refuse'               => 'Réponse à votre candidature',
            'rappel_veille'        => ' Rappel : votre événement demain',
            'remerciement'         => ' Merci pour votre participation',
            'rappel_dossier'       => 'Rappel : complétez votre dossier',
            default                => 'Notification MoovEvents',
        };

        return new Envelope(
            subject: $sujet . ' — ' . $this->inscription->evenement?->titre,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscription',
            with: [
                'inscription' => $this->inscription,
                'evenement'   => $this->inscription->evenement,
                'user'        => $this->inscription->user,
                'type'        => $this->type,
                'donnees'     => $this->donnees,
            ],
        );
    }
}