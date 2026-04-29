<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('b2b_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->foreignId('organisateur_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('prospect_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('prospect_nom')->nullable();
            $table->string('prospect_email')->nullable();
            $table->string('prospect_societe')->nullable();
            $table->string('objet');
            $table->dateTime('date_rdv');
            $table->string('lieu')->nullable();
            $table->enum('statut', ['planifie', 'confirme', 'realise', 'annule'])->default('planifie');
            $table->text('notes')->nullable();
            $table->integer('score_lead')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_meetings');
    }
};