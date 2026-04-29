<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MoovCrmIntegrationService
{
    /**
     * Simule une synchronisation bidirectionnelle avec le CRM Moov.
     *
     * @return array<string, mixed>
     */
    public function syncContacts(): array
    {
        $contacts = User::query()
            ->whereHas('inscriptions')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $exportes = $contacts->take(5)->map(fn (User $user) => $this->exportToMoov($user))->all();
        $importes = $this->importFromMoov();

        Log::info('Synchronisation CRM Moov simulée.', [
            'exportes' => count($exportes),
            'importes' => count($importes),
        ]);

        return [
            'exportes' => $exportes,
            'importes' => $importes,
        ];
    }

    /**
     * Simule l'export d'un contact vers Moov.
     *
     * @return array<string, mixed>
     */
    public function exportToMoov(User $user): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'synced_at' => now()->toIso8601String(),
        ];

        Log::info('Export CRM Moov simulé.', $payload);

        return $payload;
    }

    /**
     * Simule l'import de contacts depuis Moov.
     *
     * @return array<int, array<string, mixed>>
     */
    public function importFromMoov(): array
    {
        $contacts = [
            [
                'source' => 'moov',
                'name' => 'Prospect Moov 1',
                'email' => 'prospect.moov1@example.test',
                'societe' => 'Moov Partner',
            ],
            [
                'source' => 'moov',
                'name' => 'Prospect Moov 2',
                'email' => 'prospect.moov2@example.test',
                'societe' => 'Moov Business',
            ],
        ];

        Log::info('Import CRM Moov simulé.', [
            'contacts' => $contacts,
        ]);

        return $contacts;
    }
}