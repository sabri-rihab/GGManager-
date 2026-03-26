<?php

namespace App\Http\Controllers;

use App\Models\MatchGame;
use App\Models\Tournament;
use Illuminate\Http\Request;
use App\Events\MatchScoreUpdated;
use App\Events\TournamentFinished;
use Illuminate\Support\Facades\Log;

class MatchController extends Controller
{
    public function updateScore(Request $request, Tournament $tournament, MatchGame $match)
    {
        try {
           
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
          
            if ($tournament->organizer_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized - Only the organizer can update scores'], 403);
            }
            
          
            if ($match->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Match not found in this tournament'], 404);
            }
            
            $validated = $request->validate([
                'score_p1' => 'required|integer|min:0',
                'score_p2' => 'required|integer|min:0',
            ]);
            
          
            if ($validated['score_p1'] === $validated['score_p2']) {
                return response()->json(['message' => 'Scores cannot be equal'], 400);
            }
            
            $match->updateScore($validated['score_p1'], $validated['score_p2']);
            
          
            try {
                broadcast(new MatchScoreUpdated($match))->toOthers();
                Log::info('Broadcast sent for match: ' . $match->id);
            } catch (\Exception $e) {
                Log::error('Broadcast error: ' . $e->getMessage());
            }
            
           
            if ($tournament->status === 'finish') {
                try {
                    broadcast(new TournamentFinished($tournament))->toOthers();
                    Log::info('Tournament finished broadcast sent');
                } catch (\Exception $e) {
                    Log::error('Tournament finish broadcast error: ' . $e->getMessage());
                }
            }
            
            return response()->json([
                'message' => 'Score updated successfully',
                'match' => $match->load(['player1', 'player2', 'winner']),
                'tournament_status' => $tournament->status
            ]);
            
        } catch (\Exception $e) {
            Log::error('Update score error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating score',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function index(Tournament $tournament)
    {
        try {
            $matches = $tournament->matches()
                ->with(['player1', 'player2', 'winner'])
                ->orderBy('round')
                ->orderBy('position')
                ->get();
                
            return response()->json($matches);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching matches', 'error' => $e->getMessage()], 500);
        }
    }
    
    public function show(Tournament $tournament, MatchGame $match)
    {
        try {
            if ($match->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Match not found in this tournament'], 404);
            }
            
            return response()->json($match->load(['player1', 'player2', 'winner', 'nextMatch']));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching match', 'error' => $e->getMessage()], 500);
        }
    }

 
    public function myMatches(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
        
            $matches = MatchGame::where(function($query) use ($user) {
                    $query->where('player1_id', $user->id)
                          ->orWhere('player2_id', $user->id);
                })
                ->with(['tournament', 'player1', 'player2', 'winner'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        
            $matches->getCollection()->transform(function($match) use ($user) {
                $match->is_player1 = ($match->player1_id == $user->id);
                $match->is_player2 = ($match->player2_id == $user->id);
                $match->is_winner = ($match->winner_id == $user->id);
                $match->is_completed = !is_null($match->winner_id);
                $match->can_play = is_null($match->winner_id) && 
                                    ($match->player1_id == $user->id || $match->player2_id == $user->id);
                return $match;
            });
            
            return response()->json([
                'matches' => $matches,
                'total' => $matches->total(),
                'pending_matches' => $matches->where('can_play', true)->count(),
                'completed_matches' => $matches->where('is_completed', true)->count(),
                'won_matches' => $matches->where('is_winner', true)->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching matches', 'error' => $e->getMessage()], 500);
        }
    }

  
    public function pendingMatches(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            $matches = MatchGame::where(function($query) use ($user) {
                    $query->where('player1_id', $user->id)
                          ->orWhere('player2_id', $user->id);
                })
                ->whereNull('winner_id')
                ->whereNotNull('player1_id')
                ->whereNotNull('player2_id')
                ->where('is_bye', false)
                ->with(['tournament', 'player1', 'player2'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json($matches);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching pending matches', 'error' => $e->getMessage()], 500);
        }
    }

 
    public function myMatchesStats(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            $totalMatches = MatchGame::where(function($query) use ($user) {
                    $query->where('player1_id', $user->id)
                          ->orWhere('player2_id', $user->id);
                })->count();
            
            $wonMatches = MatchGame::where('winner_id', $user->id)->count();
            
            $pendingMatches = MatchGame::where(function($query) use ($user) {
                    $query->where('player1_id', $user->id)
                          ->orWhere('player2_id', $user->id);
                })
                ->whereNull('winner_id')
                ->whereNotNull('player1_id')
                ->whereNotNull('player2_id')
                ->count();
            
            $stats = [
                'total_matches' => $totalMatches,
                'won_matches' => $wonMatches,
                'pending_matches' => $pendingMatches,
                'win_rate' => $totalMatches > 0 ? round(($wonMatches / $totalMatches) * 100, 2) : 0,
                'tournaments_played' => MatchGame::where(function($query) use ($user) {
                    $query->where('player1_id', $user->id)
                          ->orWhere('player2_id', $user->id);
                })->distinct('tournament_id')->count('tournament_id')
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching stats', 'error' => $e->getMessage()], 500);
        }
    }
}