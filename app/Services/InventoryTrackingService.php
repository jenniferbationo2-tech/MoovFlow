<?php

namespace App\Services;

use App\Models\Dotation;

class InventoryTrackingService
{
    /**
     * Retourne les statistiques d'inventaire d'un événement.
     *
     * @return array<string, int>
     */
    public function getStats(int $evenementId): array
    {
        $query = Dotation::query()->where('evenement_id', $evenementId);

        $totalDistribue = (clone $query)->count();
        $retournes = (clone $query)->whereNotNull('date_retour')->count();
        $manquants = (clone $query)
            ->whereNull('date_retour')
            ->whereDate('date_retour_prevue', '<', now()->toDateString())
            ->count();

        return [
            'total_distribue' => $totalDistribue,
            'retourne' => $retournes,
            'en_cours' => max($totalDistribue - $retournes - $manquants, 0),
            'manquant' => $manquants,
        ];
    }

    /**
     * Retourne les équipements proches d'une rupture de stock.
     *
     * @return array<int, array<string, mixed>>
     */
    public function alertsLowStock(int $evenementId): array
    {
        return Dotation::query()
            ->selectRaw('equipement, COUNT(*) as total, SUM(CASE WHEN date_retour IS NULL THEN 1 ELSE 0 END) as en_circulation')
            ->where('evenement_id', $evenementId)
            ->groupBy('equipement')
            ->get()
            ->filter(fn ($item) => (int) $item->en_circulation >= max((int) ceil(((int) $item->total) * 0.7), 2))
            ->map(fn ($item): array => [
                'equipement' => $item->equipement,
                'total' => (int) $item->total,
                'en_circulation' => (int) $item->en_circulation,
            ])
            ->values()
            ->all();
    }
}