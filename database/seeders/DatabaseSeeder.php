// database/seeders/DatabaseSeeder.php
<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tournament;
use App\Models\Match;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create users
        $organizer = User::create([
            'name' => 'Organizer',
            'email' => 'organizer@test.com',
            'password' => Hash::make('password'),
            'role' => 'organizer',
        ]);

        $players = [];
        for ($i = 1; $i <= 8; $i++) {
            $players[] = User::create([
                'name' => "Player $i",
                'email' => "player$i@test.com",
                'password' => Hash::make('password'),
                'role' => 'player',
            ]);
        }

        // 2. Create tournament
        $tournament = Tournament::create([
            'name' => 'Summer Championship',
            'game' => 'League of Legends',
            'season' => 'Summer 2024',
            'status' => 'close',
            'max_participants' => 8,
            'organizer_id' => $organizer->id,
        ]);

        // 3. Add participants
        foreach ($players as $player) {
            $tournament->participants()->attach($player->id);
        }

        // 4. Generate simple bracket
        $this->generateBracket($tournament, $players);
    }

    private function generateBracket($tournament, $players)
    {
        // Quarter finals (round 1)
        $matches = [];
        for ($i = 0; $i < 8; $i += 2) {
            $match = Match::create([
                'tournament_id' => $tournament->id,
                'player1_id' => $players[$i]->id,
                'player2_id' => $players[$i+1]->id,
                'round' => 1,
                'position' => $i/2,
                'is_bye' => false,
            ]);
            $matches[] = $match;
        }

        // Semi finals (round 2)
        $semiMatches = [];
        for ($i = 0; $i < 4; $i += 2) {
            $semiMatch = Match::create([
                'tournament_id' => $tournament->id,
                'round' => 2,
                'position' => $i/2,
                'is_bye' => false,
            ]);
            
            $matches[$i]->next_match_id = $semiMatch->id;
            $matches[$i]->save();
            $matches[$i+1]->next_match_id = $semiMatch->id;
            $matches[$i+1]->save();
            
            $semiMatches[] = $semiMatch;
        }

        // Final (round 3)
        $finalMatch = Match::create([
            'tournament_id' => $tournament->id,
            'round' => 3,
            'position' => 0,
            'is_bye' => false,
        ]);
        
        $semiMatches[0]->next_match_id = $finalMatch->id;
        $semiMatches[0]->save();
        $semiMatches[1]->next_match_id = $finalMatch->id;
        $semiMatches[1]->save();
    }
}