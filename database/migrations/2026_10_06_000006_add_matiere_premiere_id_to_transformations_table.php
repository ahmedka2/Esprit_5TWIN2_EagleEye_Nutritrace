<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transformations', function (Blueprint $table): void {
            $table->foreignId('matiere_premiere_id')
                ->nullable()
                ->after('id')
                ->constrained('matieres_premieres')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transformations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('matiere_premiere_id');
        });
    }
};
