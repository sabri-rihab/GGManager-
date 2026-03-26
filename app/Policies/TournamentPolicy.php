<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    
    public function update(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->organizer_id && $tournament->status === 'open';
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->organizer_id && $tournament->status === 'open';
    }

   
    public function register(User $user, Tournament $tournament): bool
    {
        return $user->role === 'player' && 
               $tournament->status === 'open' && 
               $tournament->participants()->count() < $tournament->max_participants &&
               !$tournament->participants()->where('user_id', $user->id)->exists();
    }

   
    public function view(User $user, Tournament $tournament): bool
    {
        return true; 
    }

  
    public function create(User $user): bool
    {
        return $user->role === 'organizer';
    }
}