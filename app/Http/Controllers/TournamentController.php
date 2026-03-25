<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\User;
use App\Jobs\GenerateBracketJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TournamentController extends Controller
{
    // Supprimez le constructeur avec authorizeResource
    // public function __construct()
    // {
    //     $this->authorizeResource(Tournament::class, 'tournament');
    // }

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
        // Vérifier que l'utilisateur est organisateur
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
        // Vérifier que l'utilisateur est l'organisateur et que le tournoi est ouvert
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
        // Vérifier que l'utilisateur est l'organisateur et que le tournoi est ouvert
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
        
        // Vérifier que l'utilisateur est un joueur
        if ($user->role !== 'player') {
            return response()->json(['message' => 'Only players can register for tournaments'], 403);
        }
        
        // Vérifier que le tournoi est ouvert
        if ($tournament->status !== 'open') {
            return response()->json(['message' => 'Tournament is not open for registration'], 400);
        }
        
        // Vérifier que le tournoi n'est pas plein
        if ($tournament->participants()->count() >= $tournament->max_participants) {
            return response()->json(['message' => 'Tournament is full'], 400);
        }
        
        // Vérifier que l'utilisateur n'est pas déjà inscrit
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
        // Vérifier que l'utilisateur est l'organisateur
        if ($request->user()->id !== $tournament->organizer_id) {
            return response()->json(['message' => 'Only the organizer can close registrations'], 403);
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