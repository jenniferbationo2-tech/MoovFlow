<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Changer la colonne statut en VARCHAR(50) au lieu d'ENUM restrictif
        DB::statement("ALTER TABLE email_logs MODIFY COLUMN statut VARCHAR(50) NOT NULL DEFAULT 'queued'");
    }

    public function down(): void
    {
        // Pas de rollback (on garde VARCHAR)
    }
};