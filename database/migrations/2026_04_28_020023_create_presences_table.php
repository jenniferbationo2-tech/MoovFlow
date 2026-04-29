<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->unique()->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('scane_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scan_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};