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
        if (!Schema::hasTable('conversation_archives')) {
            Schema::create('conversation_archives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('produit_final_id')->constrained('produits_finaux')->onDelete('cascade');
                $table->foreignId('interlocutor_id')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['user_id', 'produit_final_id', 'interlocutor_id'], 'user_conv_archive_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_archives');
    }
};
