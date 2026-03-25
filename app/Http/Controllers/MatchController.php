<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Models\Tournament;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function updateScore(Request $request, Tournament $tournament, MatchGame $match)
    {
        // Vérifier que l'utilisateur est authentifié via le token
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Verify user is tournament organizer
        if ($tournament->organizer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized - Only the organizer can update scores'], 403);
        }
        
        // Verify match belongs to tournament
        if ($match->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Match not found in this tournament'], 404);
        }
        
        $validated = $request->validate([
            'score_p1' => 'required|integer|min:0',
            'score_p2' => 'required|integer|min:0',
        ]);
        
        // Validate scores are not equal
        if ($validated['score_p1'] === $validated['score_p2']) {
            return response()->json(['message' => 'Scores cannot be equal'], 400);
        }
        
        $match->updateScore($validated['score_p1'], $validated['score_p2']);
        
        return response()->json($match->load(['player1', 'player2', 'winner']));
    }
    
    public function index(Tournament $tournament)
    {
        $matches = $tournament->matches()
            ->with(['player1', 'player2', 'winner'])
            ->orderBy('round')
            ->orderBy('position')
            ->get();
            
        return response()->json($matches);
    }
    
    public function show(Tournament $tournament, MatchGame $match)
    {
        if ($match->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Match not found in this tournament'], 404);
        }
        
        return response()->json($match->load(['player1', 'player2', 'winner', 'nextMatch']));
    }
}