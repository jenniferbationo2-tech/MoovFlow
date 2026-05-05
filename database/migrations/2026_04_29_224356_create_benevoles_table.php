<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Bénévoles : gérés par l'organisateur, PAS de compte plateforme
     * (raison de sécurité — pas dans le DCU comme acteur).
     */
    public function up(): void
    {
        Schema::create('benevoles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evenement_id')
                  ->constrained('evenements')
                  ->cascadeOnDelete();

            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->string('poste_affecte')->nullable();
            $table->text('creneaux_horaires')->nullable();

            $table->enum('statut', ['actif', 'inactif'])->default('actif');

            $table->timestamps();

            $table->index('evenement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benevoles');
    }
};