<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('messages')) {
            Schema::create('messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('produit_final_id')
                    ->constrained('produits_finaux')
                    ->cascadeOnDelete();
                $table->foreignId('sender_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->foreignId('receiver_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                $table->text('message');
                $table->boolean('is_read')->default(false);
                $table->timestamps();

                $table->index(['produit_final_id', 'sender_id', 'receiver_id']);
                $table->index(['receiver_id', 'is_read']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
