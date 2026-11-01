<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empreintes_environnementales', function (Blueprint $table) {
            $table->boolean('estime_par_ia')->default(false)->after('eau_litres');
        });
    }

    public function down(): void
    {
        Schema::table('empreintes_environnementales', function (Blueprint $table) {
            $table->dropColumn('estime_par_ia');
        });
    }
};
