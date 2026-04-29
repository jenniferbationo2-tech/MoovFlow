<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->enum('mode', ['cash', 'moov_money', 'gratuit']);
            $table->enum('statut', ['initie', 'paye', 'echec', 'rembourse'])->default('initie');
            $table->string('reference_transaction')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};