<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prestataires', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 200);
            $table->enum('categorie', [
                'traiteur',
                'audio_video',
                'securite',
                'photographie',
                'transport',
                'decoration',
                'communication',
                'animation',
                'nettoyage',
                'autre',
            ]);
            $table->string('contact_nom', 200)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('telephone', 50)->nullable();
            $table->string('adresse', 500)->nullable();
            $table->string('ville', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('site_web', 500)->nullable();
            $table->tinyInteger('note_interne')->nullable(); // /5
            $table->text('note')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['categorie', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestataires');
    }
};