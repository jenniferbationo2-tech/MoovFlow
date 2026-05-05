<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void
    {
        Schema::create('rencontres', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_phase_id')
                  ->constrained('competition_phases')
                  ->cascadeOnDelete();

            $table->foreignId('equipe_a_id')
                  ->constrained('equipes')
                  ->cascadeOnDelete();
            $table->foreignId('equipe_b_id')
                  ->constrained('equipes')
                  ->cascadeOnDelete();

            $table->dateTime('date_match');
            $table->string('lieu_match')->nullable();
            $table->string('arbitre')->nullable();

            // Score
            $table->unsignedSmallInteger('score_equipe_a')->nullable();
            $table->unsignedSmallInteger('score_equipe_b')->nullable();

            $table->enum('statut', [
                'planifiee', 'en_cours', 'terminee', 'annulee', 'reportee'
            ])->default('planifiee');

            $table->foreignId('vainqueur_id')
                  ->nullable()
                  ->constrained('equipes')
                  ->nullOnDelete();

            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rencontres');
    }
};