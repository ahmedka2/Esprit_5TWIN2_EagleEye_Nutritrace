<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres_premieres', function (Blueprint $table): void {
            $table->decimal('quantite', 10, 3)->nullable()->after('price');
            $table->string('unite', 10)->nullable()->after('quantite');
        });
    }

    public function down(): void
    {
        Schema::table('matieres_premieres', function (Blueprint $table): void {
            $table->dropColumn(['quantite', 'unite']);
        });
    }
};