<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tournament;
use App\Models\MatchGame;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Start transaction
        DB::beginTransaction();
        
        try {
            // 1. Create organizer
            $organizer = User::create([
                'name' => 'Organizer',
                'email' => 'organizer@test.com',
                'password' => Hash::make('password'),
                'role' => 'organizer',
            ]);

            // 2. Create 8 players
            $players = [];
            for ($i = 1; $i <= 8; $i++) {
                $players[] = User::create([
                    'name' => "Player $i",
                    'email' => "player$i@test.com",
                    'password' => Hash::make('password'),
                    'role' => 'player',
                ]);
            }

            // 3. Create tournament
            $tournament = Tournament::create([
                'name' => 'Summer Championship',
                'game' => 'League of Legends',
                'season' => 'Summer 2024',
                'status' => 'open',
                'max_participants' => 8,
                'organizer_id' => $organizer->id,
            ]);

            // 4. Add participants
            foreach ($players as $player) {
                $tournament->participants()->attach($player->id);
            }

            // 5. Close registrations
            $tournament->status = 'close';
            $tournament->save();
            
            // 6. Generate bracket
            $this->generateBracket($tournament, $players);
            
            DB::commit();
            
           
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Seeding failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateBracket($tournament, $players)
    {
        $matches = [];
        
        // Quarter finals (round 1)
        $quarterMatches = $this->createQuarterFinals($tournament, $players);
        
        // Semi finals (round 2)
        $semiMatches = $this->createSemiFinals($tournament, $quarterMatches);
        
        // Final (round 3)
        $this->createFinal($tournament, $semiMatches);
        
        return $matches;
    }
    
    private function createQuarterFinals($tournament, $players)
    {
        $matches = [];
        for ($i = 0; $i < 8; $i += 2) {
            $match = MatchGame::create([
                'tournament_id' => $tournament->id,
                'player1_id' => $players[$i]->id,
                'player2_id' => $players[$i+1]->id,
                'round' => 1,
                'position' => $i / 2,
                'is_bye' => false,
            ]);
            $matches[] = $match;
        }
        return $matches;
    }
    
    private function createSemiFinals($tournament, $quarterMatches)
    {
        $semiMatches = [];
        for ($i = 0; $i < 4; $i += 2) {
            $semiMatch = MatchGame::create([
                'tournament_id' => $tournament->id,
                'round' => 2,
                'position' => $i / 2,
                'is_bye' => false,
            ]);
            
            $quarterMatches[$i]->next_match_id = $semiMatch->id;
            $quarterMatches[$i]->save();
            $quarterMatches[$i+1]->next_match_id = $semiMatch->id;
            $quarterMatches[$i+1]->save();
            
            $semiMatches[] = $semiMatch;
        }
        return $semiMatches;
    }
    
    private function createFinal($tournament, $semiMatches)
    {
        $finalMatch = MatchGame::create([
            'tournament_id' => $tournament->id,
            'round' => 3,
            'position' => 0,
            'is_bye' => false,
        ]);
        
        $semiMatches[0]->next_match_id = $finalMatch->id;
        $semiMatches[0]->save();
        $semiMatches[1]->next_match_id = $finalMatch->id;
        $semiMatches[1]->save();
        
        return $finalMatch;
    }
}