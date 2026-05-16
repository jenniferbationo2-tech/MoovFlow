<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evenement_materiel', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('evenement_id');
            $table->foreign('evenement_id')->references('id')->on('evenements')->onDelete('cascade');

            $table->unsignedBigInteger('materiel_id');
            $table->foreign('materiel_id')->references('id')->on('materiels')->onDelete('cascade');

            $table->integer('quantite_prevue');
            $table->integer('quantite_sortie')->default(0);
            $table->integer('quantite_retournee')->default(0);

            $table->enum('statut', ['prevu', 'sorti', 'retourne'])->default('prevu');
            $table->text('note')->nullable();

            $table->unsignedBigInteger('cree_par_id')->nullable();
            $table->foreign('cree_par_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->index(['evenement_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenement_materiel');
    }
};