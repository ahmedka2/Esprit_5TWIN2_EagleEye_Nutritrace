<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ponytail: lot_id has no FK constraint until the lots table exists, then ->constrained()->cascadeOnDelete()
        Schema::create('empreintes_environnementales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lot_id')->unique();
            $table->decimal('co2_kg', 8, 2);
            $table->decimal('eau_litres', 10, 2);
            $table->char('eco_score', 1);
            $table->timestamps();
        });

        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lot_id')->index();
            // ponytail: nullable until the public site has login, then make it required
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
