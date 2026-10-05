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
        Schema::create('transformations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();

            $table->string('matiere_premiere');             // "Tomate"
            $table->string('unite', 10); //kg

            $table->decimal('quantite_utilisee', 10, 2);
            $table->json('etapes');
            $table->integer('etape_actuelle')->default(0);
            $table->decimal('rendement', 5, 2)->nullable(); // Rendement de la transformation (en pourcentage)
            $table->enum('statut', ['brouillon', 'en_cours', 'complete'])
                ->default('brouillon');

            $table->dateTime('date_debut')->nullable();
            $table->dateTime('date_fin')->nullable();

            // $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transformations');
    }
};
