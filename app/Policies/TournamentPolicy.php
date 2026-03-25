<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    public function update(User $user, Tournament $tournament)
    {
        return $user->id === $tournament->organizer_id && $tournament->canBeModified();
    }

    public function delete(User $user, Tournament $tournament)
    {
        return $user->id === $tournament->organizer_id && $tournament->canBeModified();
    }

    public function register(User $user, Tournament $tournament)
    {
        return $user->isPlayer() && $tournament->canRegister();
    }
}