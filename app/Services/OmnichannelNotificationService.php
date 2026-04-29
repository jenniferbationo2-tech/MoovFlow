<?php

namespace App\Services;

use App\Models\MootNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OmnichannelNotificationService
{
    /**
     * Envoie une notification en choisissant automatiquement le meilleur canal.
     */
    public function send(User $user, string $titre, string $message, string $canal_prefere = null): MootNotification
    {
        $canal = $this->resolveCanal($user, $canal_prefere);

        if (! in_array($canal, ['email', 'sms', 'push'], true)) {
            throw ValidationException::withMessages([
                'canal' => 'Le canal de notification est invalide.',
            ]);
        }

        $notification = MootNotification::query()->create([
            'user_id' => $user->id,
            'type' => 'manuel',
            'canal' => $canal,
            'titre' => $titre,
            'message' => $message,
            'statut' => 'envoye',
            'date_envoi' => now(),
        ]);

        Log::info('Notification omnicanale simulée.', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'canal' => $canal,
            'titre' => $titre,
        ]);

        return $notification;
    }

    /**
     * Détermine le canal le plus pertinent pour l'utilisateur.
     */
    private function resolveCanal(User $user, ?string $canalPrefere): string
    {
        if ($canalPrefere !== null && $canalPrefere !== '') {
            return $canalPrefere;
        }

        if ((string) data_get($user, 'device_token', '') !== '') {
            return 'push';
        }

        if ((string) $user->telephone !== '') {
            return 'sms';
        }

        return 'email';
    }
}