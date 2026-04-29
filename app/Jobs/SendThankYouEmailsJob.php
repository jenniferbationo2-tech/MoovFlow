<?php

namespace App\Jobs;

use App\Models\Evenement;
use App\Services\AutoThankYouService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendThankYouEmailsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $evenementId
    ) {
    }

    /**
     * Exécute l'envoi asynchrone des remerciements.
     */
    public function handle(AutoThankYouService $service): void
    {
        $evenement = Evenement::query()->find($this->evenementId);

        if ($evenement) {
            $service->sendThankYou($evenement);
        }
    }
}