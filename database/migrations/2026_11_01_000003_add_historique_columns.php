<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres_premieres', function (Blueprint $table): void {
            $table->decimal('quantite_initiale', 12, 3)->nullable()->after('quantite');
        });

        Schema::table('transformations', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('matiere_premiere_id')->constrained('users')->nullOnDelete();
        });

        // Reconstitue la quantité initiale : stock restant + quantités déjà utilisées.
        DB::table('matieres_premieres')->whereNotNull('quantite')->update([
            'quantite_initiale' => DB::raw('quantite + COALESCE((select sum(quantite_utilisee) from transformations where transformations.matiere_premiere_id = matieres_premieres.id), 0)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('transformations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('matieres_premieres', function (Blueprint $table): void {
            $table->dropColumn('quantite_initiale');
        });
    }
};
