<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Models\Tournament;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function update(Request $request, Tournament $tournament, MatchGame $match)
    {
    
        $user = $request->user();
        
     
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
   
        if ($tournament->organizer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized - Only organizer can update scores'], 403);
        }
        
    
        if ($match->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Match not found'], 404);
        }
        
       
        $validated = $request->validate([
            'score_p1' => 'required|integer|min:0',
            'score_p2' => 'required|integer|min:0',
        ]);
        
    
        if ($validated['score_p1'] === $validated['score_p2']) {
            return response()->json(['message' => 'Scores cannot be equal'], 400);
        }
        
      
        $match->score_p1 = $validated['score_p1'];
        $match->score_p2 = $validated['score_p2'];
        
  
        $winnerId = $validated['score_p1'] > $validated['score_p2'] 
            ? $match->player1_id 
            : $match->player2_id;
        
        $match->winner_id = $winnerId;
        $match->save();
        
      
        if ($match->next_match_id) {
            $nextMatch = MatchGame::find($match->next_match_id);
            if ($nextMatch) {
                if (is_null($nextMatch->player1_id)) {
                    $nextMatch->player1_id = $winnerId;
                } elseif (is_null($nextMatch->player2_id)) {
                    $nextMatch->player2_id = $winnerId;
                }
                $nextMatch->save();
            }
        }
        
      
        $maxRound = $tournament->matches()->max('round');
        if ($match->round === $maxRound && $match->winner_id) {
            $tournament->status = 'finish';
            $tournament->save();
        }
        
        return response()->json([
            'message' => 'Score updated successfully',
            'match' => $match->load(['player1', 'player2', 'winner']),
            'tournament_status' => $tournament->status
        ]);
    }
}