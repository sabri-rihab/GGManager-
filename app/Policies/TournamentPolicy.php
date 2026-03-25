<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    /**
     * Determine if the user can update the tournament.
     */
    public function update(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->organizer_id && $tournament->status === 'open';
    }

    /**
     * Determine if the user can delete the tournament.
     */
    public function delete(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->organizer_id && $tournament->status === 'open';
    }

    /**
     * Determine if the user can register for the tournament.
     */
    public function register(User $user, Tournament $tournament): bool
    {
        return $user->role === 'player' && 
               $tournament->status === 'open' && 
               $tournament->participants()->count() < $tournament->max_participants &&
               !$tournament->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the user can view the tournament.
     */
    public function view(User $user, Tournament $tournament): bool
    {
        return true; // Tout le monde peut voir les tournois
    }

    /**
     * Determine if the user can create tournaments.
     */
    public function create(User $user): bool
    {
        return $user->role === 'organizer';
    }
}