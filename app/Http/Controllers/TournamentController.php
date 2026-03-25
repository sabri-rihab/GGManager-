<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\User;
use App\Jobs\GenerateBracketJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Tournament::class, 'tournament');
    }

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
        $this->authorize('update', $tournament);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'game' => 'sometimes|string|max:255',
            'season' => 'sometimes|string|max:255',
            'max_participants' => 'sometimes|integer|min:2|max:64',
        ]);

        $tournament->update($validated);

        return response()->json($tournament);
    }

    public function destroy(Tournament $tournament)
    {
        $this->authorize('delete', $tournament);
        
        $tournament->delete();

        return response()->json(['message' => 'Tournament deleted successfully']);
    }

    public function register(Request $request, Tournament $tournament)
    {
        $this->authorize('register', $tournament);
        
        $user = $request->user();
        
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

    public function closeRegistrations(Tournament $tournament)
    {
        if ($tournament->organizer_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($tournament->status !== 'open') {
            return response()->json(['message' => 'Tournament is not open'], 400);
        }
        
        if ($tournament->participants()->count() < 2) {
            return response()->json(['message' => 'Need at least 2 participants'], 400);
        }
        
        $tournament->status = 'close';
        $tournament->save();
        
        // Dispatch job to generate bracket
        GenerateBracketJob::dispatch($tournament);
        
        return response()->json(['message' => 'Registrations closed, bracket generation started']);
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
}