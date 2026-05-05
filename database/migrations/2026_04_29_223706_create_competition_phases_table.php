<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Phases d'une compétition (tournoi sportif, hackathon, etc.).
     * Ex: Phase de groupes → Quarts → Demi → Finale
     */
    public function up(): void
    {
        Schema::create('competition_phases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evenement_id')
                  ->constrained('evenements')
                  ->cascadeOnDelete();

            $table->string('nom');                    // "Phase de groupes", "Finale"
            $table->unsignedTinyInteger('ordre');     // 1, 2, 3, 4...
            $table->dateTime('date_debut')->nullable();
            $table->dateTime('date_fin')->nullable();

            $table->enum('statut', [
                'a_venir', 'en_cours', 'terminee'
            ])->default('a_venir');

            $table->timestamps();

            $table->index(['evenement_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_phases');
    }
};