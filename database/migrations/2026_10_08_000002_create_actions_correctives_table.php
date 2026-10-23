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
        Schema::create('actions_correctives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signalement_qualite_id')
                ->constrained('signalements_qualite')
                ->cascadeOnDelete();
            $table->foreignId('responsable_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('type_decision');
            $table->string('mesure_appliquee', 255);
            $table->text('rapport_intervention');
            $table->string('statut_action')->default('en_cours');
            $table->date('date_application');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions_correctives');
    }
};
