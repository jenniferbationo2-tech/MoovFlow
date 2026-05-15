<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('benevoles', function (Blueprint $table): void {
            // Rendre evenement_id nullable (pour les bénévoles "vivier")
            $table->unsignedBigInteger('evenement_id')->nullable()->change();

            // Ajouter colonne disponibilites si pas existante
            if (!Schema::hasColumn('benevoles', 'disponibilites')) {
                $table->string('disponibilites')->nullable();
            }
            // Ajouter colonne competences
            if (!Schema::hasColumn('benevoles', 'competences')) {
                $table->text('competences')->nullable()->after('disponibilites');
            }
            $table->string('horaires')->nullable();
        });

        Schema::table('intervenants', function (Blueprint $table): void {
            if (Schema::hasColumn('intervenants', 'evenement_id')) {
                $table->unsignedBigInteger('evenement_id')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        
    }
};