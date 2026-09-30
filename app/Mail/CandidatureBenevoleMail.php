<?php

namespace App\Mail;

use App\Models\CandidatureBenevole;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CandidatureBenevoleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CandidatureBenevole $candidature,
        public string $type,
    ) {}

    public function envelope(): Envelope
    {
        $poste = $this->candidature->poste;

        $sujet = match ($this->type) {
            'soumise' => 'Votre candidature bénévole a bien été reçue',
            'acceptee' => 'Votre candidature bénévole est acceptée !',
            'refusee'  => 'Réponse à votre candidature bénévole',
            default    => 'Notification MoovFlow',
        };

        return new Envelope(
            subject: $sujet . ' — ' . ($poste?->nom_poste ?? 'Bénévolat'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.candidature-benevole',
            with: [
                'candidature' => $this->candidature,
                'poste'       => $this->candidature->poste,
                'evenement'   => $this->candidature->poste?->evenement,
                'user'        => $this->candidature->user,
                'type'        => $this->type,
            ],
        );
    }
}
