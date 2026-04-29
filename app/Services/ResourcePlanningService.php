<?php

namespace App\Services;

use App\Models\Salle;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ResourcePlanningService
{
    /**
     * Vérifie si une salle est libre sur un créneau donné.
     */
    public function checkConflicts(int $salleId, string $debut, string $fin): bool
    {
        $start = Carbon::parse($debut);
        $end = Carbon::parse($fin);

        return ! Session::query()
            ->where('salle_id', $salleId)
            ->where('heure_debut', '<', $end)
            ->where('heure_fin', '>', $start)
            ->exists();
    }

    /**
     * Retourne les salles disponibles pour un créneau et une capacité.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function suggestAvailableRooms(string $debut, string $fin, int $capaciteMin = 0): Collection
    {
        $start = Carbon::parse($debut);
        $end = Carbon::parse($fin);

        return Salle::query()
            ->with('lieu')
            ->when($capaciteMin > 0, fn ($query) => $query->where('capacite', '>=', $capaciteMin))
            ->whereDoesntHave('sessions', function ($query) use ($start, $end): void {
                $query
                    ->where('heure_debut', '<', $end)
                    ->where('heure_fin', '>', $start);
            })
            ->orderByDesc('capacite')
            ->orderBy('nom')
            ->get()
            ->map(fn (Salle $salle): array => [
                'id' => $salle->id,
                'nom' => $salle->nom,
                'capacite' => $salle->capacite,
                'lieu' => $salle->lieu ? [
                    'id' => $salle->lieu->id,
                    'nom' => $salle->lieu->nom,
                ] : null,
                'equipements' => $salle->equipements ?? [],
            ]);
    }
}