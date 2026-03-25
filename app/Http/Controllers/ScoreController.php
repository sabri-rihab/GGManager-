<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Models\Tournament;
use App\Events\MatchScoreUpdated;
use Illuminate\Http\Request;
use App\Events\MatchUpdated; 
use App\Events\TournamentFinished;

class ScoreController extends Controller
{
    public function update(Request $request, Tournament $tournament, MatchGame $match)
    {
        // Vérifier autorisation
        if ($tournament->organizer_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Vérifier match appartient au tournoi
        if ($match->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Match not found'], 404);
        }
        
        // Valider scores
        $validated = $request->validate([
            'score_p1' => 'required|integer|min:0',
            'score_p2' => 'required|integer|min:0',
        ]);
        
        // Vérifier scores différents
        if ($validated['score_p1'] === $validated['score_p2']) {
            return response()->json(['message' => 'Scores cannot be equal'], 400);
        }
        
        // Mettre à jour le score
        $match->score_p1 = $validated['score_p1'];
        $match->score_p2 = $validated['score_p2'];
        
        // Déterminer le gagnant
        $winnerId = $validated['score_p1'] > $validated['score_p2'] 
            ? $match->player1_id 
            : $match->player2_id;
        
        $match->winner_id = $winnerId;
        $match->save();
        
        // Qualifier le gagnant pour le prochain match
        $this->qualifyWinner($match, $winnerId);
        
        // Vérifier si le tournoi est terminé
        $this->checkTournamentCompletion($tournament);
        
        // Broadcast l'événement
        broadcast(new MatchScoreUpdated($match))->toOthers();
        
        return response()->json([
            'message' => 'Score updated successfully',
            'match' => $match->load(['player1', 'player2', 'winner']),
            'tournament_status' => $tournament->status
        ]);
    }
    
    private function qualifyWinner($match, $winnerId)
    {
        if (!$match->next_match_id) {
            return;
        }
        
        $nextMatch = MatchGame::find($match->next_match_id);
        
        if (!$nextMatch) {
            return;
        }
        
        // Placer le gagnant dans le prochain match
        if (is_null($nextMatch->player1_id)) {
            $nextMatch->player1_id = $winnerId;
        } elseif (is_null($nextMatch->player2_id)) {
            $nextMatch->player2_id = $winnerId;
        }
        
        $nextMatch->save();
        
        // Si c'était un bye match, le désactiver
        if ($nextMatch->is_bye && $nextMatch->player1_id && $nextMatch->player2_id) {
            $nextMatch->is_bye = false;
            $nextMatch->save();
        }
    }
    
    private function checkTournamentCompletion($tournament)
    {
        $maxRound = $tournament->matches()->max('round');
        $finalMatches = $tournament->matches()
            ->where('round', $maxRound)
            ->get();
        
        $allCompleted = true;
        foreach ($finalMatches as $match) {
            if (is_null($match->winner_id)) {
                $allCompleted = false;
                break;
            }
        }
        
        if ($allCompleted && $finalMatches->count() > 0) {
            $tournament->status = 'finish';
            $tournament->save();
            
            broadcast(new TournamentFinished($tournament))->toOthers();
        }
    }
}