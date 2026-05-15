<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            if (!Schema::hasColumn('evenements', 'date_demande_validation')) {
                $table->timestamp('date_demande_validation')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'date_publication')) {
                $table->timestamp('date_publication')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'validated_by')) {
                $table->unsignedBigInteger('validated_by')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'motif_rejet')) {
                $table->text('motif_rejet')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            $table->dropColumn(['date_demande_validation', 'date_publication', 'validated_by', 'motif_rejet']);
        });
    }
};