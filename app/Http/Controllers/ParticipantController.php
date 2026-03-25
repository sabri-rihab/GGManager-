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
    
    public function destroy(Request $request, Tournament $tournament, $userId)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        if ($user->id !== $tournament->organizer_id) {
            return response()->json(['message' => 'Unauthorized - Only the organizer can remove participants'], 403);
        }
        
        $tournament->participants()->detach($userId);
        
        return response()->json(['message' => 'Participant removed successfully']);
    }
}