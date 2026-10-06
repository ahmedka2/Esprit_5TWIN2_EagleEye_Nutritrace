<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empreintes_environnementales', function (Blueprint $table) {
            $table->id();
            // one footprint per transformation step (etape = index in transformations.etapes)
            $table->foreignId('transformation_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('etape');
            $table->decimal('co2_kg', 8, 2);
            $table->decimal('eau_litres', 10, 2);
            $table->timestamps();
            $table->unique(['transformation_id', 'etape']);
        });

        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_final_id')->constrained('produits_finaux')->cascadeOnDelete();
            // nullable: anonymous visitors can report too
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('motif');
            $table->text('description');
            $table->string('statut')->default('ouvert');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rates');
        Schema::dropIfExists('empreintes_environnementales');
    }
};
