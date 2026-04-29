<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->string('objet');
            $table->longText('contenu');
            $table->string('mode_destinataires', 100);
            $table->json('emails_personnalises')->nullable();
            $table->string('statut', 50)->default('envoyee');
            $table->dateTime('date_envoi')->nullable();
            $table->unsignedInteger('nb_destinataires')->default(0);
            $table->unsignedInteger('nb_ouvertures')->default(0);
            $table->unsignedInteger('nb_acceptations')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_campaigns');
    }
};