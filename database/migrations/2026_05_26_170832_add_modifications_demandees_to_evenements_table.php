<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->text('modifications_demandees')->nullable()->after('motif_rejet');
            $table->timestamp('modifications_demandees_le')->nullable()->after('modifications_demandees');
            $table->unsignedBigInteger('modifications_demandees_par')->nullable()->after('modifications_demandees_le');
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn(['modifications_demandees', 'modifications_demandees_le', 'modifications_demandees_par']);
        });
    }
};