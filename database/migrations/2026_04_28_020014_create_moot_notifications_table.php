<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('moot_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 100);
            $table->enum('canal', ['email', 'sms', 'push']);
            $table->string('titre');
            $table->text('message');
            $table->enum('statut', ['envoye', 'echoue', 'en_attente'])->default('en_attente');
            $table->dateTime('date_envoi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moot_notifications');
    }
};