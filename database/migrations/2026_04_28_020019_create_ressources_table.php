<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ressources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained('evenements')->cascadeOnDelete();
            $table->enum('type', ['transport', 'materiel', 'restauration']);
            $table->string('nom');
            $table->text('description')->nullable();
            $table->integer('quantite')->default(1);
            $table->enum('statut', ['disponible', 'reserve', 'utilise'])->default('disponible');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ressources');
    }
};