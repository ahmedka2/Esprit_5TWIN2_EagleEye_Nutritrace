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
        Schema::create('produits_finaux', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('nom');
            $table->foreignId('transformation_id')
                ->constrained('transformations')
                ->cascadeOnDelete();

            $table->decimal('quantite', 10, 2);        // 400.00
            $table->string('unite', 10);               // "kg"

            $table->date('date_production');           // 2026-10-05
            $table->date('date_peremption')->nullable();

            $table->enum('statut', ['disponible', 'reserve', 'expedie'])
                ->default('disponible');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits_finaux');
    }
};
