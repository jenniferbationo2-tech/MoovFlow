<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evenement_prestataire', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('evenement_id');
            $table->foreign('evenement_id')->references('id')->on('evenements')->onDelete('cascade');

            $table->unsignedBigInteger('prestataire_id');
            $table->foreign('prestataire_id')->references('id')->on('prestataires')->onDelete('cascade');

            $table->string('prestation', 500);
            $table->decimal('montant_prevu', 15, 2)->nullable();
            $table->decimal('montant_final', 15, 2)->nullable();

            $table->enum('statut', ['devis', 'confirme', 'paye', 'annule'])->default('devis');
            $table->string('contrat_pdf', 500)->nullable();
            $table->text('note')->nullable();

            $table->unsignedBigInteger('cree_par_id')->nullable();
            $table->foreign('cree_par_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
            $table->index(['evenement_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenement_prestataire');
    }
};