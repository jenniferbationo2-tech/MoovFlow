<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('inscription_id')->nullable();
            $table->foreign('inscription_id')->references('id')->on('inscriptions')->onDelete('cascade');

            $table->enum('type', [
                'preinscription_recue',
                'preselectionne',
                'dossier_recu',
                'accepte',
                'refuse',
                'rappel_veille',
                'remerciement',
                'rappel_dossier',
            ]);

            $table->string('destinataire', 255);
            $table->string('sujet', 500);
            $table->text('contenu')->nullable();
            $table->enum('statut', ['queued', 'sent', 'failed'])->default('queued');
            $table->text('erreur')->nullable();
            $table->timestamp('envoye_at')->nullable();

            $table->timestamps();

            $table->index(['inscription_id', 'type']);
            $table->index(['statut', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};