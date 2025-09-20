<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game_slug');
            $table->unsignedInteger('best_score')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'game_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_scores');
    }
};


