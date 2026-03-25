<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'game',
        'season',
        'status',
        'max_participants',
        'organizer_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'tournament_participants')
                    ->withTimestamps();
    }

    public function matches()
    {
        return $this->hasMany(Match::class);
    }

    public function canRegister()
    {
        return $this->status === 'open' && 
               $this->participants()->count() < $this->max_participants;
    }

    public function canBeModified()
    {
        return $this->status === 'open' && 
               !$this->matches()->exists();
    }

    public function canGenerateBracket()
    {
        return $this->status === 'close' && 
               $this->participants()->count() >= 2;
    }
}