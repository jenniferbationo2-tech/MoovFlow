<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('objectifs_rse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->string('type_impact', 100);
            $table->integer('nb_beneficiaires_directs')->default(0);
            $table->integer('nb_beneficiaires_indirects')->default(0);
            $table->integer('nb_associations_soutenues')->default(0);
            $table->integer('nb_projets_accompagnes')->default(0);
            $table->integer('nb_femmes_beneficiaires')->default(0);
            $table->decimal('montants_collectes', 12, 2)->default(0);
            $table->decimal('retombees_partenaires', 12, 2)->default(0);
            $table->integer('nb_emplois_crees')->default(0);
            $table->decimal('score_environnemental', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('objectifs_rse');
    }
};