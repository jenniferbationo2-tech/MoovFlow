<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {

            //BARA MOUSSO 
            if (!Schema::hasColumn('evenements', 'criteres_candidature')) {
                $table->text('criteres_candidature')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'domaines_acceptes')) {
                $table->json('domaines_acceptes')->nullable(); // array JSON
            }
            if (!Schema::hasColumn('evenements', 'dotation_principale')) {
                $table->decimal('dotation_principale', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'nombre_laureates')) {
                $table->integer('nombre_laureates')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'age_min')) {
                $table->integer('age_min')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'age_max')) {
                $table->integer('age_max')->nullable();
            }

            // CONFÉRENCE
            if (!Schema::hasColumn('evenements', 'theme_principal')) {
                $table->string('theme_principal', 300)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'profession_cible')) {
                $table->string('profession_cible', 100)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'programme_agenda')) {
                $table->text('programme_agenda')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'diffusion_en_ligne')) {
                $table->boolean('diffusion_en_ligne')->default(false);
            }
            if (!Schema::hasColumn('evenements', 'lien_zoom')) {
                $table->string('lien_zoom', 500)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'document_joint')) {
                $table->string('document_joint', 500)->nullable();
            }

            // TOURNOI SPORTIF 
            if (!Schema::hasColumn('evenements', 'discipline')) {
                $table->string('discipline', 100)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'categorie_age')) {
                $table->string('categorie_age', 50)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'nombre_max_equipes')) {
                $table->integer('nombre_max_equipes')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'effectif_min')) {
                $table->integer('effectif_min')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'effectif_max')) {
                $table->integer('effectif_max')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'format_competition')) {
                $table->string('format_competition', 100)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'trophees_prix')) {
                $table->text('trophees_prix')->nullable();
            }

            // CHALLENGE INNOVATION 
            if (!Schema::hasColumn('evenements', 'thematique_challenge')) {
                $table->string('thematique_challenge', 300)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'criteres_evaluation')) {
                $table->text('criteres_evaluation')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'stades_acceptes')) {
                $table->json('stades_acceptes')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'dotation_totale')) {
                $table->decimal('dotation_totale', 15, 2)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'date_cloture_dossiers')) {
                $table->date('date_cloture_dossiers')->nullable();
            }

            //  FORMATION 
            if (!Schema::hasColumn('evenements', 'domaine_formation')) {
                $table->string('domaine_formation', 100)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'niveau_requis')) {
                $table->string('niveau_requis', 50)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'duree_heures')) {
                $table->integer('duree_heures')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'certification')) {
                $table->boolean('certification')->default(false);
            }
            if (!Schema::hasColumn('evenements', 'nom_certification')) {
                $table->string('nom_certification', 200)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'programme_detaille')) {
                $table->text('programme_detaille')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'materiel_requis')) {
                $table->text('materiel_requis')->nullable();
            }

            // HACKATHON 
            if (!Schema::hasColumn('evenements', 'theme_hackathon')) {
                $table->string('theme_hackathon', 300)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'duree_heures_hack')) {
                $table->integer('duree_heures_hack')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'equipe_min')) {
                $table->integer('equipe_min')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'equipe_max')) {
                $table->integer('equipe_max')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'technologies_suggerees')) {
                $table->json('technologies_suggerees')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'criteres_evaluation_hack')) {
                $table->text('criteres_evaluation_hack')->nullable();
            }

            // SALON 
            if (!Schema::hasColumn('evenements', 'nom_salon_hote')) {
                $table->string('nom_salon_hote', 300)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'organisateur_externe')) {
                $table->string('organisateur_externe', 300)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'lieu_stand')) {
                $table->string('lieu_stand', 200)->nullable();
            }
            if (!Schema::hasColumn('evenements', 'superficie_stand')) {
                $table->integer('superficie_stand')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'objectifs_stand')) {
                $table->text('objectifs_stand')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'objectif_prospects')) {
                $table->integer('objectif_prospects')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            $table->dropColumn([
                // Bara Mousso
                'criteres_candidature', 'domaines_acceptes', 'dotation_principale',
                'nombre_laureates', 'age_min', 'age_max',
                // Conférence
                'theme_principal', 'profession_cible', 'programme_agenda',
                'diffusion_en_ligne', 'lien_zoom', 'document_joint',
                // Sport
                'discipline', 'categorie_age', 'nombre_max_equipes',
                'effectif_min', 'effectif_max', 'format_competition', 'trophees_prix',
                // Challenge
                'thematique_challenge', 'criteres_evaluation', 'stades_acceptes',
                'dotation_totale', 'date_cloture_dossiers',
                // Formation
                'domaine_formation', 'niveau_requis', 'duree_heures', 'certification',
                'nom_certification', 'programme_detaille', 'materiel_requis',
                // Hackathon
                'theme_hackathon', 'duree_heures_hack', 'equipe_min', 'equipe_max',
                'technologies_suggerees', 'criteres_evaluation_hack',
                // Salon
                'nom_salon_hote', 'organisateur_externe', 'lieu_stand', 'superficie_stand',
                'objectifs_stand', 'objectif_prospects',
            ]);
        });
    }
};