<?php

namespace App\Providers;

use App\Models\Tournament;
use App\Models\MatchGame;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BracketGenerator
{
    public function generate(Tournament $tournament)
    {
        DB::transaction(function () use ($tournament) {
            $participants = $tournament->participants()->pluck('user_id')->toArray();
            shuffle($participants);
            
            $numParticipants = count($participants);
            $bracketSize = pow(2, ceil(log($numParticipants, 2)));
            $numByes = $bracketSize - $numParticipants;
            
            // Créer les matchs du premier round
            $firstRoundMatches = $this->createFirstRound($tournament, $participants, $numByes);
            
            // Créer les rounds suivants
            $this->createSubsequentRounds($tournament, $firstRoundMatches);
        });
        
        return true;
    }
    
    private function createFirstRound($tournament, $participants, $numByes)
    {
        $matches = [];
        $totalMatches = ceil(count($participants) / 2);
        
        for ($i = 0; $i < $totalMatches; $i++) {
            $player1 = $participants[$i * 2] ?? null;
            $player2 = $participants[$i * 2 + 1] ?? null;
            
            $match = MatchGame::create([
                'tournament_id' => $tournament->id,
                'player1_id' => $player1,
                'player2_id' => $player2,
                'round' => 1,
                'position' => $i,
                'is_bye' => is_null($player2),
            ]);
            
            if (is_null($player2) && $player1) {
                $match->winner_id = $player1;
                $match->save();
            }
            
            $matches[] = $match;
        }
        
        return $matches;
    }
    
    private function createSubsequentRounds($tournament, $matches)
    {
        $currentMatches = $matches;
        $round = 2;
        
        while (count($currentMatches) > 1) {
            $nextRoundMatches = [];
            $numMatches = count($currentMatches) / 2;
            
            for ($i = 0; $i < $numMatches; $i++) {
                $nextMatch = MatchGame::create([
                    'tournament_id' => $tournament->id,
                    'round' => $round,
                    'position' => $i,
                    'is_bye' => false,
                ]);
                
                $leftMatch = $currentMatches[$i * 2];
                $rightMatch = $currentMatches[$i * 2 + 1] ?? null;
                
                $leftMatch->next_match_id = $nextMatch->id;
                $leftMatch->save();
                
                if ($rightMatch) {
                    $rightMatch->next_match_id = $nextMatch->id;
                    $rightMatch->save();
                }
                
                $nextRoundMatches[] = $nextMatch;
            }
            
            $currentMatches = $nextRoundMatches;
            $round++;
        }
    }
}