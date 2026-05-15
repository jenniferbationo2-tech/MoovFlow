<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            if (!Schema::hasColumn('evenements', 'public_cible')) {
                $table->string('public_cible', 200)->nullable()->after('description');
            }
            if (!Schema::hasColumn('evenements', 'cible_beneficiaires')) {
                $table->integer('cible_beneficiaires')->nullable();
            }
            if (!Schema::hasColumn('evenements', 'objectifs_principaux')) {
                $table->text('objectifs_principaux')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('evenements', function (Blueprint $table): void {
            $table->dropColumn(['public_cible', 'cible_beneficiaires', 'objectifs_principaux']);
        });
    }
};