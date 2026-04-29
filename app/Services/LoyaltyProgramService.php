<?php

namespace App\Services;

use App\Models\Evenement;
use App\Models\MootNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class LoyaltyProgramService
{
    /**
     * Retourne les participants fidèles ayant assisté à plusieurs événements.
     *
     * @return Collection<int, User>
     */
    public function getRecurringParticipants(int $min_events = 2): Collection
    {
        return User::query()
            ->with(['inscriptions.evenement'])
            ->withCount('inscriptions')
            ->orderByDesc('inscriptions_count')
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $user->inscriptions_count >= $min_events)
            ->values();
    }

    /**
     * Simule l'envoi d'une invitation prioritaire à un participant fidèle.
     */
    public function sendPriorityInvitation(User $user, Evenement $evenement): MootNotification
    {
        return MootNotification::query()->create([
            'user_id' => $user->id,
            'type' => 'invitation_prioritaire',
            'canal' => 'email',
            'titre' => 'Invitation prioritaire',
            'message' => sprintf(
                'Bonjour %s, vous êtes invité en priorité à l’événement %s.',
                $user->name,
                $evenement->titre
            ),
            'statut' => 'envoye',
            'date_envoi' => now(),
        ]);
    }
}