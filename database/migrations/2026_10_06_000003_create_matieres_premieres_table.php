<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matieres_premieres', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->decimal('price', 10, 2);
            $table->foreignId('categorie_id')
                ->constrained('categories')
                ->restrictOnDelete();
            $table->foreignId('producteur_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->index(['categorie_id', 'producteur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matieres_premieres');
    }
};