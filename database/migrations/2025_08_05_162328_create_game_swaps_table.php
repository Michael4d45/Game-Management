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
        Schema::create('game_swaps', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(GameSession::class)->constrained()->cascadeOnDelete();
            $table->string('player_id');
            $table->string('game_name');
            $table->string('save_state_path')->nullable();
            $table->string('initiated_by');
            $table->integer('round_number')->default(0);
            $table->timestampTz('swap_at');
            $table->timestampTz('executed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_swaps');
    }
};
