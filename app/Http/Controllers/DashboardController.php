<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\MatchGame;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->role === 'organizer') {
            return $this->organizerDashboard($user);
        }
        
        return $this->playerDashboard($user);
    }
    
    private function playerDashboard($user)
    {
        $tournaments = Tournament::whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->get();
        
        $matches = MatchGame::where(function($q) use ($user) {
            $q->where('player1_id', $user->id)
              ->orWhere('player2_id', $user->id);
        })->with(['tournament', 'winner'])->get();
        
        return response()->json([
            'user' => $user,
            'tournaments_count' => $tournaments->count(),
            'matches_count' => $matches->count(),
            'wins_count' => $matches->where('winner_id', $user->id)->count(),
            'recent_tournaments' => $tournaments->take(5),
            'upcoming_matches' => $matches->whereNull('winner_id')->take(5)
        ]);
    }
    
    private function organizerDashboard($user)
    {
        $tournaments = Tournament::where('organizer_id', $user->id)->get();
        $activeTournaments = $tournaments->where('status', 'open');
        $finishedTournaments = $tournaments->where('status', 'finish');
        
        return response()->json([
            'user' => $user,
            'total_tournaments' => $tournaments->count(),
            'active_tournaments' => $activeTournaments->count(),
            'finished_tournaments' => $finishedTournaments->count(),
            'total_participants' => $tournaments->sum(function($t) {
                return $t->participants()->count();
            }),
            'recent_tournaments' => $tournaments->take(5),
            'pending_matches' => $this->getPendingMatches($tournaments)
        ]);
    }
    
    private function getPendingMatches($tournaments)
    {
        $pendingMatches = collect();
        
        foreach ($tournaments as $tournament) {
            $matches = $tournament->matches()
                ->whereNull('winner_id')
                ->whereNotNull('player1_id')
                ->whereNotNull('player2_id')
                ->take(5)
                ->get();
            
            $pendingMatches = $pendingMatches->merge($matches);
        }
        
        return $pendingMatches;
    }
}