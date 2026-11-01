<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->foreignId('produit_final_id')
                ->nullable()
                ->unique()
                ->after('vehicule_id')
                ->constrained('produits_finaux')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('livraisons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('produit_final_id');
        });
    }
};
