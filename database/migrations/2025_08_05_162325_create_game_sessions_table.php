<?php

declare(strict_types=1);

use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->enum('mode', ['sync_list', 'save_swap']);
            $table->integer('swap_interval_min')->default(5); // seconds
            $table->integer('swap_interval_max')->default(10); // seconds
            $table->string('state')->default('stopped');
            $table->integer('current_round')->default(0);
            $table->timestampTz('state_at')->default(new Expression('CURRENT_TIMESTAMP'));
            $table->timestamps();
        });

        Schema::create('session_games', function (Blueprint $table): void {
            $table->foreignIdFor(GameSession::class)->constrained()->cascadeOnDelete();
            $table->string('game_file');
            $table->foreign('game_file')
                ->references('file')
                ->on('games')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_games');
        Schema::dropIfExists('game_sessions');
    }
};
