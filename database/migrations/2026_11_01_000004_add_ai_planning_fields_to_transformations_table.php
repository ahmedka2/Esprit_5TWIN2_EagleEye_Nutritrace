<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transformations', function (Blueprint $table): void {
            $table->text('original_request')->nullable();
            $table->json('ai_plan_raw')->nullable();
            $table->dateTime('planned_start')->nullable();
            $table->dateTime('planned_end')->nullable();
            $table->decimal('expected_yield_percent', 5, 2)->nullable();
            $table->unsignedSmallInteger('suggested_shelf_life_days')->nullable();
            $table->string('final_photo_path')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('validated_at')->nullable();
        });
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE transformations MODIFY statut ENUM('brouillon','planifiee','en_cours','finalisee') NOT NULL DEFAULT 'brouillon'");
        }
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('transformations')->where('statut', 'planifiee')->update(['statut' => 'brouillon']);
        Schema::table('transformations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn([
                'original_request', 'ai_plan_raw', 'planned_start', 'planned_end',
                'expected_yield_percent', 'suggested_shelf_life_days', 'final_photo_path', 'validated_at',
            ]);
        });
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE transformations MODIFY statut ENUM('brouillon','en_cours','finalisee') NOT NULL DEFAULT 'brouillon'");
        }
    }
};
