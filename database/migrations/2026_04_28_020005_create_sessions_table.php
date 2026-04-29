<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sessions_evenement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->foreignId('salle_id')->nullable()->constrained('salles')->nullOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->dateTime('heure_debut');
            $table->dateTime('heure_fin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_evenement');
    }
};