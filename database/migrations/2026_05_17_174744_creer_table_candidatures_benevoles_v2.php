<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // On vérifie que la table n'existe pas avant de la créer
        if (Schema::hasTable('candidatures_benevoles')) {
            return;
        }

        Schema::create('candidatures_benevoles', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('poste_id');
            $table->foreign('poste_id')->references('id')->on('postes_benevoles')->onDelete('cascade');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->text('motivation');
            $table->text('experience')->nullable();
            $table->text('disponibilites')->nullable();

            $table->enum('statut', ['candidat', 'accepte', 'refuse', 'annule'])->default('candidat');
            $table->text('motif_refus')->nullable();

            $table->unsignedBigInteger('valide_par_id')->nullable();
            $table->foreign('valide_par_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('valide_le')->nullable();

            $table->timestamps();

            $table->unique(['poste_id', 'user_id']);
            $table->index(['statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures_benevoles');
    }
};