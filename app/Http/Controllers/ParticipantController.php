<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function index(Tournament $tournament)
    {
        return response()->json($tournament->participants()->paginate(20));
    }
    
    public function destroy(Tournament $tournament, $userId)
    {
        if (auth()->id() !== $tournament->organizer_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $tournament->participants()->detach($userId);
        
        return response()->json(['message' => 'Participant removed']);
    }
}