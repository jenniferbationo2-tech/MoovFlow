<?php

namespace App\Services;

use App\Models\CommunicationCampaign;
use App\Models\Evenement;
use App\Models\Invitation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    /**
     * Crée les invitations d'une campagne et simule leur envoi ou leur planification.
     *
     * @param  array<int, string>  $emails
     * @return array<int, Invitation>
     */
    public function sendBulkInvitations(
        Evenement $evenement,
        array $emails,
        string $template_content,
        ?CommunicationCampaign $campaign = null,
        ?\DateTimeInterface $plannedFor = null
    ): array {
        $dateEnvoi = $plannedFor ? Carbon::instance($plannedFor) : now();
        $invitations = [];

        foreach (collect($emails)->filter()->unique()->values() as $email) {
            $user = User::query()->where('email', $email)->first();

            $invitation = Invitation::query()->create([
                'campaign_id' => $campaign?->id,
                'evenement_id' => $evenement->id,
                'user_id' => $user?->id,
                'email' => $email,
                'statut' => $plannedFor && $plannedFor > now() ? 'en_attente' : 'envoyee',
                'date_envoi' => $dateEnvoi,
            ]);

            Log::info('Invitation de campagne simulée.', [
                'invitation_id' => $invitation->id,
                'campaign_id' => $campaign?->id,
                'evenement_id' => $evenement->id,
                'email' => $email,
                'contenu' => $template_content,
                'planifiee' => $plannedFor && $plannedFor > now(),
            ]);

            $invitations[] = $invitation;
        }

        return $invitations;
    }
}