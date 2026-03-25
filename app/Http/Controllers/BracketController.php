<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class BracketController extends Controller
{
    public function show(Tournament $tournament)
    {
        // Get all matches with their relationships
        $matches = $tournament->matches()
            ->with(['player1', 'player2', 'winner', 'nextMatch'])
            ->get();
        
        // Build bracket structure
        $bracket = $this->buildBracketStructure($matches);
        
        return response()->json([
            'tournament' => $tournament,
            'bracket' => $bracket,
            'total_rounds' => $matches->max('round'),
            'current_round' => $this->getCurrentRound($matches)
        ]);
    }
    
    private function buildBracketStructure($matches)
    {
        $bracket = [];
        
        // Group matches by round
        $groupedMatches = $matches->groupBy('round');
        
        foreach ($groupedMatches as $round => $roundMatches) {
            $bracket[$round] = [];
            
            foreach ($roundMatches as $match) {
                $bracket[$round][] = [
                    'id' => $match->id,
                    'round' => $match->round,
                    'position' => $match->position,
                    'player1' => $match->player1 ? [
                        'id' => $match->player1->id,
                        'name' => $match->player1->name
                    ] : null,
                    'player2' => $match->player2 ? [
                        'id' => $match->player2->id,
                        'name' => $match->player2->name
                    ] : null,
                    'score_p1' => $match->score_p1,
                    'score_p2' => $match->score_p2,
                    'winner' => $match->winner ? [
                        'id' => $match->winner->id,
                        'name' => $match->winner->name
                    ] : null,
                    'is_bye' => $match->is_bye,
                    'is_completed' => !is_null($match->winner_id),
                    'next_match_id' => $match->next_match_id
                ];
            }
        }
        
        return $bracket;
    }
    
    private function getCurrentRound($matches)
    {
        $currentRound = 1;
        
        foreach ($matches as $match) {
            if (!$match->isCompleted() && $match->round > $currentRound) {
                $currentRound = $match->round;
            }
        }
        
        return $currentRound;
    }
}