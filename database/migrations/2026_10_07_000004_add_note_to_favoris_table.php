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
        Schema::table('favoris', function (Blueprint $table) {
            if (!Schema::hasColumn('favoris', 'note')) {
                $table->text('note')->nullable()->after('date_ajout');
            }
            if (!Schema::hasColumn('favoris', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->after('note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('favoris', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('favoris', 'note')) {
                $cols[] = 'note';
            }
            if (Schema::hasColumn('favoris', 'rating')) {
                $cols[] = 'rating';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
