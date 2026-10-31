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
        Schema::table('signalements_qualite', function (Blueprint $table) {
            $table->string('ai_categorie_predite')->nullable()->after('statut');
            $table->string('ai_gravite_predite')->nullable()->after('ai_categorie_predite');
            $table->integer('ai_score_risque')->default(0)->after('ai_gravite_predite');
            $table->string('ai_vision_label')->nullable()->after('ai_score_risque');
            $table->text('ai_resume_triage')->nullable()->after('ai_vision_label');
            $table->string('ai_model_used')
                ->default('Xenova/multilingual-e5-small + Xenova/vit-base-patch16-224')
                ->after('ai_resume_triage');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('ai_sentiment')->nullable()->after('deleted_by_sender');
            $table->integer('ai_urgence_score')->default(0)->after('ai_sentiment');
            $table->string('ai_model_used')
                ->default('Xenova/distilbert-base-multilingual-cased-sentiments-student')
                ->after('ai_urgence_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signalements_qualite', function (Blueprint $table) {
            $table->dropColumn([
                'ai_categorie_predite',
                'ai_gravite_predite',
                'ai_score_risque',
                'ai_vision_label',
                'ai_resume_triage',
                'ai_model_used',
            ]);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn([
                'ai_sentiment',
                'ai_urgence_score',
                'ai_model_used',
            ]);
        });
    }
};
