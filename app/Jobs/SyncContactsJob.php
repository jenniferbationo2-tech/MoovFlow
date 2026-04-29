<?php

namespace App\Jobs;

use App\Services\MoovCrmIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncContactsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Exécute la synchronisation asynchrone des contacts.
     */
    public function handle(MoovCrmIntegrationService $service): void
    {
        $service->syncContacts();
    }
}