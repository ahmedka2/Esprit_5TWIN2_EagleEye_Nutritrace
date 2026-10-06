<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matiere_premiere_certification', function (Blueprint $table): void {
            $table->foreignId('matiere_premiere_id')
                ->constrained('matieres_premieres')
                ->cascadeOnDelete();
            $table->foreignId('certification_id')
                ->constrained('certifications')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['matiere_premiere_id', 'certification_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matiere_premiere_certification');
    }
};