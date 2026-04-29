<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dotations', function (Blueprint $table): void {
            $table->date('date_retour')->nullable()->after('date_retour_prevue');
        });
    }

    public function down(): void
    {
        Schema::table('dotations', function (Blueprint $table): void {
            $table->dropColumn('date_retour');
        });
    }
};