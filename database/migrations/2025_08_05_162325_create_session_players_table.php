<?php

declare(strict_types=1);

use App\Models\GameSession;
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
        Schema::create('session_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(GameSession::class)->constrained()->cascadeOnDelete();
            $table->string('player_name');
            $table->string('player_id')->unique();
            $table->integer('ping')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->boolean('is_connected')->default(true);
            $table->string('current_game')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_players');
    }
};
