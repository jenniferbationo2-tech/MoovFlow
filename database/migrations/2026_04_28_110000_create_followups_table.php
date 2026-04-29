<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('evenement_id')->nullable()->constrained('evenements')->nullOnDelete();
            $table->enum('type', ['remerciement', 'relance', 'fidelisation', 'rdv_b2b']);
            $table->enum('statut', ['a_faire', 'fait', 'annule'])->default('a_faire');
            $table->text('notes')->nullable();
            $table->date('date_prevue')->nullable();
            $table->date('date_realise')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};