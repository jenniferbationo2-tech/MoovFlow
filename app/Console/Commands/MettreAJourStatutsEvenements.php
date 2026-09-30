<?php

namespace App\Console\Commands;

use App\Models\Evenement;
use Illuminate\Console\Command;
use Carbon\Carbon;

class MettreAJourStatutsEvenements extends Command
{
    protected $signature = 'evenements:update-statuts';
    protected $description = 'Met à jour automatiquement les statuts des événements selon les dates';

    public function handle(): int
    {
        $now = Carbon::now();

        // Publié + date_debut <= maintenant <= date_fin → en_cours
        $passesEnCours = Evenement::where('statut', 'publie')
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now)
            ->update(['statut' => 'en_cours']);

        // En_cours + date_fin < maintenant → termine
        $passesEnTermine = Evenement::where('statut', 'en_cours')
            ->where('date_fin', '<', $now)
            ->update(['statut' => 'termine']);

        // Publié + date_fin < maintenant (cas où on a sauté en_cours) → termine
        $publiesEnTermine = Evenement::where('statut', 'publie')
            ->where('date_fin', '<', $now)
            ->update(['statut' => 'termine']);

        // Terminé (ou publié/en_cours resté coincé) depuis plus d'une semaine → archivé,
        // et disparaît donc du catalogue public.
        $archives = Evenement::whereIn('statut', ['publie', 'en_cours', 'termine'])
            ->where('date_fin', '<=', $now->copy()->subWeek())
            ->update(['statut' => 'archive']);

        $this->info("✅ {$passesEnCours} événements passés en cours");
        $this->info("✅ {$passesEnTermine} événements passés en terminé");
        $this->info("✅ {$publiesEnTermine} événements passés directement de publié à terminé");
        $this->info("✅ {$archives} événements archivés (terminés depuis plus d'une semaine)");

        return Command::SUCCESS;
    }
}