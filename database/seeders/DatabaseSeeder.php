<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $filePath = config()->string('game.files_path');
        if (! is_dir($filePath)) {
            mkdir($filePath, 0755, true);
        }
        $existingFiles = array_diff(scandir($filePath), ['.', '..']);
        $fileCount = count($existingFiles);

        if ($fileCount < 10) {
            foreach (range(1, 10 - $fileCount) as $_) {
                $filename = fake()->word . '.txt';
                file_put_contents($filePath . '/' . $filename, '');
            }
        }
        foreach ($existingFiles as $filename) {
            Game::firstOrCreate([
                'file' => $filename,
            ]);
        }
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $session = GameSession::create([
            'user_id' => $user->id,
            'name' => 'test',
            'mode' => 'sync_list',
        ]);

        $session->games()->attach(
            fake()->randomElements(Game::all(), 5)
        );
    }
}
