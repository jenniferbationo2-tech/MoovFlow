<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Champs spécifiques aux événements de type Salon.
     * Salon = Moov participe à un événement organisé par un tiers.
     */
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            // Infos sur le salon hôte (organisé par un tiers)
            $table->string('nom_salon_hote')->nullable()->after('description');
            // Ex: "SIAO 2025", "FESPACO 2025"

            $table->string('organisateur_externe')->nullable()->after('nom_salon_hote');
            // Ex: "Ministère du Commerce"

            // Stand Moov sur le salon
            $table->string('lieu_stand')->nullable()->after('organisateur_externe');
            // Ex: "Hall B - Stand 42"

            $table->unsignedInteger('superficie_stand')->nullable()->after('lieu_stand');
            // En m²

            $table->text('objectifs_stand')->nullable()->after('superficie_stand');
            $table->unsignedInteger('objectif_prospects')->nullable()->after('objectifs_stand');
            // Nombre de prospects visés
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            $table->dropColumn([
                'nom_salon_hote', 'organisateur_externe',
                'lieu_stand', 'superficie_stand',
                'objectifs_stand', 'objectif_prospects',
            ]);
        });
    }
};