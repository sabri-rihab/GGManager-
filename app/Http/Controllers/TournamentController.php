<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\User;
use App\Jobs\GenerateBracketJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    public function index(Request $request)
    {
        $query = Tournament::with('organizer');
        
        if ($request->has('game')) {
            $query->where('game', $request->game);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        
        if ($request->user()->role !== 'organizer') {
            return response()->json(['message' => 'Only organizers can create tournaments'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'game' => 'required|string|max:255',
            'season' => 'required|string|max:255',
            'max_participants' => 'required|integer|min:2|max:64',
        ]);

        $tournament = Tournament::create([
            ...$validated,
            'organizer_id' => $request->user()->id,
            'status' => 'open'
        ]);

        return response()->json($tournament, 201);
    }

    public function show(Tournament $tournament)
    {
        return response()->json($tournament->load(['organizer', 'participants']));
    }

    public function update(Request $request, Tournament $tournament)
    {
       
        if ($request->user()->id !== $tournament->organizer_id) {
            return response()->json(['message' => 'Only the organizer can update this tournament'], 403);
        }
        
        if ($tournament->status !== 'open') {
            return response()->json(['message' => 'Cannot update tournament after it is closed'], 400);
        }
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'game' => 'sometimes|string|max:255',
            'season' => 'sometimes|string|max:255',
            'max_participants' => 'sometimes|integer|min:2|max:64',
        ]);

        $tournament->update($validated);

        return response()->json($tournament);
    }

    public function destroy(Request $request, Tournament $tournament)
    {
       
        if ($request->user()->id !== $tournament->organizer_id) {
            return response()->json(['message' => 'Only the organizer can delete this tournament'], 403);
        }
        
        if ($tournament->status !== 'open') {
            return response()->json(['message' => 'Cannot delete tournament after it is closed'], 400);
        }
        
        $tournament->delete();

        return response()->json(['message' => 'Tournament deleted successfully']);
    }

    public function register(Request $request, Tournament $tournament)
    {
        $user = $request->user();
        
       
        if ($user->role !== 'player') {
            return response()->json(['message' => 'Only players can register for tournaments'], 403);
        }
      
        if ($tournament->status !== 'open') {
            return response()->json(['message' => 'Tournament is not open for registration'], 400);
        }
        
      
        if ($tournament->participants()->count() >= $tournament->max_participants) {
            return response()->json(['message' => 'Tournament is full'], 400);
        }
     
        if ($tournament->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Already registered'], 400);
        }
        
        $tournament->participants()->attach($user->id);
        
        return response()->json(['message' => 'Registered successfully']);
    }

    public function participants(Tournament $tournament)
    {
        return response()->json($tournament->participants()->paginate(20));
    }

    public function closeRegistrations(Request $request, Tournament $tournament)
    {
        if ($request->user()->id !== $tournament->organizer_id) {
            return response()->json(['message' => 'Only the organizer can close registrations'], 403);
        }
        
        if ($tournament->status !== 'open') {
            return response()->json(['message' => 'Tournament is not open'], 400);
        }
        
        $participantsCount = $tournament->participants()->count();
        
        if ($participantsCount < 2) {
            return response()->json([
                'message' => 'Need at least 2 participants',
                'current_participants' => $participantsCount
            ], 400);
        }
        
        try {
            
            \App\Models\MatchGame::where('tournament_id', $tournament->id)->delete();
            
         
            $participants = $tournament->participants()->pluck('user_id')->toArray();
            shuffle($participants);
         
            $this->generateBracket($tournament, $participants);
         
            $tournament->status = 'close';
            $tournament->save();
            
            return response()->json([
                'message' => 'Registrations closed, bracket generated successfully',
                'tournament_id' => $tournament->id,
                'matches_count' => $tournament->matches()->count(),
                'participants_count' => $participantsCount
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error generating bracket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generateBracket($tournament, $participants)
    {
        $matches = [];
        $numParticipants = count($participants);
        
       
        for ($i = 0; $i < $numParticipants; $i += 2) {
            $p1 = $participants[$i];
            $p2 = $participants[$i+1] ?? null;
            
            $match = \App\Models\MatchGame::create([
                'tournament_id' => $tournament->id,
                'player1_id' => $p1,
                'player2_id' => $p2,
                'round' => 1,
                'position' => $i/2,
                'is_bye' => is_null($p2),
            ]);
            
            if (is_null($p2)) {
                $match->winner_id = $p1;
                $match->save();
            }
            
            $matches[] = $match;
        }
        
   
        $currentMatches = $matches;
        $round = 2;
        
        while (count($currentMatches) > 1) {
            $nextMatches = [];
            $numMatches = count($currentMatches) / 2;
            
            for ($i = 0; $i < $numMatches; $i++) {
                $nextMatch = \App\Models\MatchGame::create([
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
                
                $nextMatches[] = $nextMatch;
            }
            
            $currentMatches = $nextMatches;
            $round++;
        }
    }

    public function bracket(Tournament $tournament)
    {
        $bracket = $tournament->matches()
            ->with(['player1', 'player2', 'winner'])
            ->orderBy('round')
            ->orderBy('position')
            ->get()
            ->groupBy('round');
            
        return response()->json($bracket);
    }

 
    public function myTournaments(Request $request)
    {
        $user = $request->user();
        
        if ($user->role === 'organizer') {
          
            $tournaments = Tournament::where('organizer_id', $user->id)
                ->with('organizer')
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } else {
          
            $tournaments = $user->tournaments()
                ->with('organizer')
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }
        
        return response()->json($tournaments);
    }

    public function myParticipations(Request $request)
    {
        $user = $request->user();
        
        $participations = $user->tournaments()
            ->with(['organizer', 'participants'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json($participations);
    }

  
    public function organizerTournaments(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'organizer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $tournaments = Tournament::where('organizer_id', $user->id)
            ->with('organizer')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json($tournaments);
    }

    public function organizerStats(Request $request)
    {
        $user = $request->user();
        
        if ($user->role !== 'organizer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $tournaments = Tournament::where('organizer_id', $user->id)->get();
        
        $stats = [
            'total_tournaments' => $tournaments->count(),
            'open_tournaments' => $tournaments->where('status', 'open')->count(),
            'closed_tournaments' => $tournaments->where('status', 'close')->count(),
            'finished_tournaments' => $tournaments->where('status', 'finish')->count(),
            'total_participants' => $tournaments->sum(function($t) {
                return $t->participants()->count();
            }),
            'tournaments' => $tournaments->map(function($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'status' => $t->status,
                    'participants_count' => $t->participants()->count(),
                    'matches_count' => $t->matches()->count()
                ];
            })
        ];
        
        return response()->json($stats);
    }

    public function search(Request $request)
    {
        $query = Tournament::query();
        
        if ($request->has('q')) {
            $search = $request->q;
            $query->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('game', 'LIKE', "%{$search}%");
        }
        
        if ($request->has('game')) {
            $query->where('game', $request->game);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $tournaments = $query->with('organizer')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return response()->json($tournaments);
    }

  
    public function games()
    {
        $games = Tournament::select('game')
            ->distinct()
            ->pluck('game');
        
        return response()->json($games);
    }

 
    public function globalStats()
    {
        $stats = [
            'total_tournaments' => Tournament::count(),
            'total_players' => User::where('role', 'player')->count(),
            'total_organizers' => User::where('role', 'organizer')->count(),
            'open_tournaments' => Tournament::where('status', 'open')->count(),
            'finished_tournaments' => Tournament::where('status', 'finish')->count(),
            'games' => Tournament::select('game')
                ->selectRaw('count(*) as count')
                ->groupBy('game')
                ->get()
        ];
        
        return response()->json($stats);
    }

   
    public function unregister(Request $request, Tournament $tournament)
    {
        $user = $request->user();
        
        if ($user->role !== 'player') {
            return response()->json(['message' => 'Only players can unregister'], 403);
        }
        
        if ($tournament->status !== 'open') {
            return response()->json(['message' => 'Cannot unregister from closed tournament'], 400);
        }
        
        if (!$tournament->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are not registered'], 400);
        }
        
        $tournament->participants()->detach($user->id);
        
        return response()->json(['message' => 'Unregistered successfully']);
    }
}