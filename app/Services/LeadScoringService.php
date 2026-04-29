<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

class LeadScoringService
{
    /**
     * Calcule un score prospect B2B à partir de l'historique du contact.
     */
    public function score(User $user): int
    {
        $user->loadMissing([
            'inscriptions.evenement.typeEvenement',
            'invitations',
            'followups',
            'b2bMeetings',
            'reponsesEnquetes',
        ]);

        $participations = $user->inscriptions->count();
        $typesScore = $user->inscriptions
            ->filter(fn ($inscription) => $inscription->evenement !== null)
            ->sum(function ($inscription): int {
                $code = strtolower((string) $inscription->evenement?->typeEvenement?->code);

                return match ($code) {
                    'salon' => 18,
                    'forum' => 14,
                    'conference' => 10,
                    default => 8,
                };
            });

        $ancienneteEnJours = $user->created_at instanceof Carbon
            ? (int) $user->created_at->diffInDays(now())
            : 0;

        $ancienneteScore = min(20, (int) floor($ancienneteEnJours / 45));

        $interactionsScore = min(30, (
            ($user->invitations->count() * 3)
            + ($user->followups->count() * 4)
            + ($user->b2bMeetings->count() * 6)
            + ($user->reponsesEnquetes->count() * 5)
        ));

        $participationScore = min(30, $participations * 7);
        $rawScore = $participationScore + min(20, $typesScore) + $ancienneteScore + $interactionsScore;

        return max(0, min(100, $rawScore));
    }

    /**
     * Classe le prospect selon son score.
     */
    public function classify(User $user): string
    {
        $score = $this->score($user);

        return match (true) {
            $score >= 70 => 'chaud',
            $score >= 40 => 'tiede',
            default => 'froid',
        };
    }
}