<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materiels', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 200);
            $table->enum('categorie', [
                'audiovisuel',     // Sono, projecteur, micros
                'mobilier',        // Tables, chaises, podium
                'signaletique',    // Roll-ups, banderoles, panneaux
                'informatique',    // Ordinateurs, écrans, câbles
                'restauration',    // Vaisselle, glacières
                'securite',        // Talkies, gilets, barrières
                'goodies',         // Sacs, stylos, t-shirts
                'fourniture',      // Papier, stylos bureau
                'autre',
            ]);
            $table->text('description')->nullable();
            $table->integer('quantite_totale')->default(0);
            $table->integer('quantite_disponible')->default(0);
            $table->string('unite', 50)->default('pièce');
            $table->enum('etat', ['neuf', 'bon', 'usage', 'hs'])->default('bon');
            $table->string('photo', 500)->nullable();
            $table->string('lieu_stockage', 200)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['categorie', 'etat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};