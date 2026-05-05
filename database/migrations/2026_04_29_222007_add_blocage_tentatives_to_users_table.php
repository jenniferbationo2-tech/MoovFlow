<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ajoute le système de blocage après 3 tentatives échouées (DS1).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedTinyInteger('tentatives_connexion')
                  ->default(0)
                  ->after('is_active');
            $table->dateTime('bloque_jusqu_a')
                  ->nullable()
                  ->after('tentatives_connexion');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['tentatives_connexion', 'bloque_jusqu_a']);
        });
    }
};