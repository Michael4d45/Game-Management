<?php

declare(strict_types=1);

use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;
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
        Schema::create('game_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->enum('mode', ['sync_list', 'save_swap']);
            $table->integer('swap_interval')->default(0); // seconds
            $table->string('status')->nullable();
            $table->integer('current_round')->default(0);
            $table->timestamp('start_at')->nullable();
            $table->timestamps();
        });

        Schema::create('session_games', function (Blueprint $table): void {
            $table->foreignIdFor(GameSession::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Game::class)->constrained()->cascadeOnDelete();
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
