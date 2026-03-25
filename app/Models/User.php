<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function organizedTournaments()
    {
        return $this->hasMany(Tournament::class, 'organizer_id');
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_participants')
                    ->withTimestamps();
    }

    public function matchesAsPlayer1()
    {
        return $this->hasMany(MatchGame::class, 'player1_id');
    }

    public function matchesAsPlayer2()
    {
        return $this->hasMany(MatchGame::class, 'player2_id');
    }

    public function wonMatches()
    {
        return $this->hasMany(MatchGame::class, 'winner_id');
    }

    public function isOrganizer()
    {
        return $this->role === 'organizer';
    }

    public function isPlayer()
    {
        return $this->role === 'player';
    }
}