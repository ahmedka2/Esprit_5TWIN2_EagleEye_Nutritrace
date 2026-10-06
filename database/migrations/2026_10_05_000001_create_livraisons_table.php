<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicule_id')->constrained()->restrictOnDelete();
            $table->string('depart');
            $table->string('arrivee');
            $table->dateTime('date_depart');
            $table->dateTime('date_arrivee')->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('temperature_moyenne', 5, 2)->nullable();
            $table->string('statut')->default('en_cours');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livraisons');
    }
};
