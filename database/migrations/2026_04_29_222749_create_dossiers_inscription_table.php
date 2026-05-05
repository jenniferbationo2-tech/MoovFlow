<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
   
    public function up(): void
    {
        Schema::create('dossiers_inscription', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscription_id')
                  ->constrained('inscriptions')
                  ->cascadeOnDelete();

            //  Champs communs 
            $table->string('organisation')->nullable();
            $table->string('fonction')->nullable();
            $table->text('motivation')->nullable();
            $table->string('fichier_joint')->nullable();

            //  Bara Mousso (concours associations féminines
            $table->string('nom_association')->nullable();
            $table->text('description_projet')->nullable();
            $table->unsignedInteger('nb_membres_association')->nullable();
            $table->decimal('budget_projet', 15, 2)->nullable();

            // Moov Holiday Foot (tournoi sportif) 
            $table->string('nom_equipe')->nullable();
            $table->unsignedInteger('nb_joueurs')->nullable();
            $table->string('categorie_equipe')->nullable();   // junior/senior/mixte
            $table->string('responsable_equipe')->nullable();

            //  Hackathon 
            $table->string('competences_techniques')->nullable();
            $table->string('stack_technologique')->nullable();
            $table->string('nom_equipe_hack')->nullable();
            $table->unsignedInteger('nb_membres_equipe')->nullable();

            //  Formation numérique 
            $table->enum('niveau_formation', [
                'debutant', 'intermediaire', 'avance'
            ])->nullable();
            $table->text('objectifs_apprentissage')->nullable();

            // Salon (visiteur d'un stand Moov) 
            $table->string('secteur_activite')->nullable();
            $table->enum('type_visite_salon', [
                'visiteur', 'partenaire_potentiel', 'client_potentiel'
            ])->nullable();
            $table->text('interets_b2b')->nullable();

            // Challenge Innovation 
            $table->string('titre_idee')->nullable();
            $table->string('secteur_idee')->nullable();
            $table->string('fichier_presentation')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dossiers_inscription');
    }
};