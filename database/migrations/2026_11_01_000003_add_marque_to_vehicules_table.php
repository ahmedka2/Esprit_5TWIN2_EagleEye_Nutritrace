<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicules', function (Blueprint $table) {
            $table->string('marque')->nullable()->after('immatriculation');
        });
    }

    public function down(): void
    {
        Schema::table('vehicules', function (Blueprint $table) {
            $table->dropColumn('marque');
        });
    }
};
