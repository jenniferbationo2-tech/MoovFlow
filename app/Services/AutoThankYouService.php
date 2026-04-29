<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\MootNotification;

class AutoThankYouService
{
    /**
     * Envoie des remerciements personnalisés à tous les participants d'un événement terminé.
     *
     * @return array<int, MootNotification>
     */
    public function sendThankYou(Evenement $evenement): array
    {
        $inscriptions = $evenement->inscriptions()
            ->with('user')
            ->get()
            ->filter(fn ($inscription) => $inscription->user !== null)
            ->values();

        $notifications = [];

        foreach ($inscriptions as $inscription) {
            $notifications[] = MootNotification::query()->create([
                'user_id' => $inscription->user->id,
                'type' => 'remerciement_evenement',
                'canal' => 'email',
                'titre' => 'Merci pour votre participation',
                'message' => sprintf(
                    'Bonjour %s, merci pour votre participation à %s.',
                    $inscription->user->name,
                    $evenement->titre
                ),
                'statut' => 'envoye',
                'date_envoi' => now(),
            ]);
        }

        return $notifications;
    }
}