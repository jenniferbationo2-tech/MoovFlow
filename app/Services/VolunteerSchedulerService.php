<?php

namespace App\Services;

use App\Models\BenevoleAffectation;

class VolunteerSchedulerService
{
    /**
     * Génère une vue chronologique des rotations bénévoles.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generatePlanning(int $evenementId): array
    {
        return BenevoleAffectation::query()
            ->with('user')
            ->where('evenement_id', $evenementId)
            ->orderBy('creneau_debut')
            ->get()
            ->groupBy('user_id')
            ->map(function ($affectations, $userId): array {
                $first = $affectations->first();

                return [
                    'user_id' => (int) $userId,
                    'benevole' => [
                        'id' => $first?->user?->id,
                        'name' => $first?->user?->name,
                        'email' => $first?->user?->email,
                    ],
                    'creneaux' => $affectations->map(fn (BenevoleAffectation $affectation): array => [
                        'id' => $affectation->id,
                        'poste' => $affectation->poste,
                        'creneau_debut' => optional($affectation->creneau_debut)?->toIso8601String(),
                        'creneau_fin' => optional($affectation->creneau_fin)?->toIso8601String(),
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}