<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('equipes', function (Blueprint $table) {
            $table->string('capitaine')->nullable()->after('nom');
            $table->string('categorie')->nullable()->after('capitaine');
        });
    }

    public function down(): void
    {
        Schema::table('equipes', function (Blueprint $table) {
            $table->dropColumn(['capitaine', 'categorie']);
        });
    }
};
