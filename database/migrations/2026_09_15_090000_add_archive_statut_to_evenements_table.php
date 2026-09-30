<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE evenements MODIFY COLUMN statut ENUM(
            'brouillon',
            'en_validation',
            'publie',
            'en_cours',
            'termine',
            'archive',
            'annule'
        ) NOT NULL DEFAULT 'brouillon'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE evenements MODIFY COLUMN statut ENUM(
            'brouillon',
            'en_validation',
            'publie',
            'en_cours',
            'termine',
            'annule'
        ) NOT NULL DEFAULT 'brouillon'");
    }
};
