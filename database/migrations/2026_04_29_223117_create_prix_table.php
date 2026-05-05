<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void
    {
        Schema::create('prix', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evenement_id')
                  ->constrained('evenements')
                  ->cascadeOnDelete();

            $table->unsignedInteger('rang');             // 1, 2, 3, etc.
            $table->string('libelle');                    // "1er Prix", "Prix Innovation"
            $table->text('description')->nullable();
            $table->decimal('valeur_monetaire', 15, 2)->default(0);
            $table->string('nature_prix')->nullable();    // "Ordinateur", "Bourse 500k"

            // Le gagnant — lié à une inscription validée
            $table->foreignId('gagnant_id')
                  ->nullable()
                  ->constrained('inscriptions')
                  ->nullOnDelete();
            $table->boolean('attribue')->default(false);

            $table->timestamps();

            $table->index('evenement_id');
            $table->index(['evenement_id', 'rang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prix');
    }
};