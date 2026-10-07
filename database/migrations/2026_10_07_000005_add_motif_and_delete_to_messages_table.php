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
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'motif')) {
                $table->string('motif', 50)->nullable()->after('message');
            }
            if (!Schema::hasColumn('messages', 'deleted_by_sender')) {
                $table->boolean('deleted_by_sender')->default(false)->after('is_read');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('messages', 'motif')) {
                $cols[] = 'motif';
            }
            if (Schema::hasColumn('messages', 'deleted_by_sender')) {
                $cols[] = 'deleted_by_sender';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
