<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Si la table n'existe pas : on la crée
        if (!Schema::hasTable('lieux')) {
            Schema::create('lieux', function (Blueprint $table): void {
                $table->id();
                $table->string('nom', 200);
                $table->string('adresse', 500)->nullable();
                $table->string('ville', 100)->nullable();
                $table->integer('capacite_max')->nullable();
                $table->text('description')->nullable();
                $table->string('photo', 500)->nullable();
                $table->boolean('actif')->default(true);
                $table->timestamps();
            });
            return;
        }

        // Si elle existe : on ajoute les colonnes manquantes
        Schema::table('lieux', function (Blueprint $table): void {
            if (!Schema::hasColumn('lieux', 'adresse')) {
                $table->string('adresse', 500)->nullable()->after('nom');
            }
            if (!Schema::hasColumn('lieux', 'ville')) {
                $table->string('ville', 100)->nullable()->after('adresse');
            }
            if (!Schema::hasColumn('lieux', 'capacite_max')) {
                $table->integer('capacite_max')->nullable()->after('ville');
            }
            if (!Schema::hasColumn('lieux', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('lieux', 'photo')) {
                $table->string('photo', 500)->nullable();
            }
            if (!Schema::hasColumn('lieux', 'actif')) {
                $table->boolean('actif')->default(true);
            }
        });
    }

    public function down(): void
    {
        // No-op pour éviter de casser une base existante
    }
};