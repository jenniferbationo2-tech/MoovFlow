<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('visuel')->nullable();
            $table->foreignId('type_evenement_id')->constrained('types_evenement');
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->foreignId('lieu_id')->nullable()->constrained('lieux')->nullOnDelete();
            $table->enum('statut', ['brouillon', 'publie', 'en_cours', 'termine', 'annule'])->default('brouillon');
            $table->decimal('budget_prev', 10, 2)->nullable()->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type_evenement_id');
            $table->index('lieu_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};