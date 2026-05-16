<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('postes_benevoles', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('evenement_id');
            $table->foreign('evenement_id')->references('id')->on('evenements')->onDelete('cascade');

            $table->string('nom_poste', 200);
            $table->enum('categorie', [
                'accueil',
                'securite',
                'technique',
                'logistique',
                'communication',
                'restauration',
                'animation',
                'autre',
            ]);
            $table->text('description');
            $table->text('competences_requises')->nullable();
            $table->integer('places_max')->default(1);

            $table->timestamp('horaire_debut')->nullable();
            $table->timestamp('horaire_fin')->nullable();

            $table->enum('statut', ['ouvert', 'ferme', 'complet'])->default('ouvert');

            $table->unsignedBigInteger('cree_par_id')->nullable();
            $table->foreign('cree_par_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->index(['evenement_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postes_benevoles');
    }
};