<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\MatchGame;
use Illuminate\Support\Facades\DB;

class BracketGenerator
{
    public function generate(Tournament $tournament)
    {
        DB::transaction(function () use ($tournament) {
            // Get participants
            $participants = $tournament->participants()->pluck('user_id')->toArray();
            
            // Shuffle participants
            shuffle($participants);
            
            // Calculate bracket size (next power of 2)
            $numParticipants = count($participants);
            $bracketSize = pow(2, ceil(log($numParticipants, 2)));
            $numByes = $bracketSize - $numParticipants;
            
            // Generate first round matches
            $firstRoundMatches = $this->createFirstRound($tournament, $participants, $numByes);
            
            // Generate subsequent rounds
            $this->createSubsequentRounds($tournament, $firstRoundMatches);
            
            // Update tournament status
            $tournament->status = 'close';
            $tournament->save();
        });
        
        return true;
    }
    
    private function createFirstRound($tournament, $participants, $numByes)
    {
        $matches = [];
        $matchIndex = 0;
        $participantIndex = 0;
        
        // Calculate number of matches in first round
        $totalMatches = count($participants) / 2;
        
        for ($i = 0; $i < $totalMatches; $i++) {
            $player1 = $participants[$participantIndex] ?? null;
            $participantIndex++;
            $player2 = $participants[$participantIndex] ?? null;
            $participantIndex++;
            
            $isBye = is_null($player2);
            
            $match = MatchGame::create([
                'tournament_id' => $tournament->id,
                'player1_id' => $player1,
                'player2_id' => $player2,
                'round' => 1,
                'position' => $i,
                'is_bye' => $isBye,
            ]);
            
            // If bye, auto-advance player
            if ($isBye && $player1) {
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
                // Create next round match
                $nextMatch = MatchGame::create([
                    'tournament_id' => $tournament->id,
                    'round' => $round,
                    'position' => $i,
                    'is_bye' => false,
                ]);
                
                // Link current matches to next match
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