<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void
    {
        Schema::create('classements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('competition_phase_id')
                  ->constrained('competition_phases')
                  ->cascadeOnDelete();
            $table->foreignId('equipe_id')
                  ->constrained('equipes')
                  ->cascadeOnDelete();

            $table->unsignedSmallInteger('points')->default(0);
            $table->unsignedSmallInteger('matchs_joues')->default(0);
            $table->unsignedSmallInteger('victoires')->default(0);
            $table->unsignedSmallInteger('nuls')->default(0);
            $table->unsignedSmallInteger('defaites')->default(0);
            $table->smallInteger('difference_buts')->default(0);
            $table->unsignedSmallInteger('buts_marques')->default(0);
            $table->unsignedSmallInteger('buts_encaisses')->default(0);

            $table->unsignedSmallInteger('rang')->nullable();

            $table->timestamps();

            $table->unique(['competition_phase_id', 'equipe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classements');
    }
};