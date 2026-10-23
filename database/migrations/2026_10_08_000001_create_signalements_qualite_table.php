<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signalements_qualite', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('produit_final_id')->constrained('produits_finaux')->cascadeOnDelete();
            $table->string('code_lot_saisi', 50);
            $table->string('type_anomalie');
            $table->string('gravite')->default('moyenne');
            $table->text('description');
            $table->string('lieu_achat')->nullable();
            $table->date('date_constat');
            $table->string('photo_preuve')->nullable();
            $table->string('statut')->default('ouvert');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signalements_qualite');
    }
};
