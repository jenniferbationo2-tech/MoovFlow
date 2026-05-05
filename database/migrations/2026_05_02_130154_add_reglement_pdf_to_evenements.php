<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Ajoute le champ pour stocker le règlement PDF de l'événement.
     * Le participant doit prendre connaissance de ce règlement avant de s'inscrire.
     */
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            $table->string('reglement_pdf')
                  ->nullable()
                  ->after('visuel');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            $table->dropColumn('reglement_pdf');
        });
    }
};