<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificats', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 50)->unique(); // CERT-2026-0001
            
            // Liens
            $table->unsignedBigInteger('evenement_id');
            $table->unsignedBigInteger('user_id');
            
            // Fichier
            $table->string('fichier_path', 500); // certificats/CERT-2026-0001.pdf
            
            // Email
            $table->boolean('envoye_email')->default(false);
            $table->timestamp('envoye_at')->nullable();
            
            // Generation
            $table->timestamp('genere_at')->useCurrent();
            
            $table->timestamps();
            
            // Index
            $table->index(['evenement_id']);
            $table->index(['user_id']);
            $table->index(['evenement_id', 'user_id']); // unique pair
            
            // Foreign keys (style explicite pour éviter errno 150)
            $table->foreign('evenement_id')
                  ->references('id')
                  ->on('evenements')
                  ->onDelete('cascade');
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificats');
    }
};