<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('intervenants_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sessions_evenement')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 100)->default('intervenant');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intervenants_sessions');
    }
};