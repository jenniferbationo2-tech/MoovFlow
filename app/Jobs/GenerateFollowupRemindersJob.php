<?php

namespace App\Jobs;

use App\Models\Followup;
use App\Models\MootNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateFollowupRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Génère des rappels pour les suivis en retard ou à faire.
     */
    public function handle(): void
    {
        $followups = Followup::query()
            ->with('user')
            ->where('statut', 'a_faire')
            ->whereDate('date_prevue', '<=', today())
            ->get();

        foreach ($followups as $followup) {
            if (! $followup->user) {
                continue;
            }

            MootNotification::query()->create([
                'user_id' => $followup->user->id,
                'type' => 'rappel_suivi',
                'canal' => 'email',
                'titre' => 'Rappel de suivi',
                'message' => sprintf(
                    'Le suivi "%s" prévu pour %s nécessite une action.',
                    $followup->type,
                    optional($followup->date_prevue)?->format('d/m/Y') ?? 'une date non définie'
                ),
                'statut' => 'envoye',
                'date_envoi' => now(),
            ]);
        }
    }
}