<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchGame extends Model
{
    use HasFactory;

    protected $table = 'match_games';

    protected $fillable = [
        'tournament_id',
        'player1_id',
        'player2_id',
        'score_p1',
        'score_p2',
        'winner_id',
        'next_match_id',
        'is_bye',
        'round',
        'position',
    ];

    protected $casts = [
        'is_bye' => 'boolean',
        'score_p1' => 'integer',
        'score_p2' => 'integer',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function nextMatch()
    {
        return $this->belongsTo(MatchGame::class, 'next_match_id');
    }

    public function isCompleted()
    {
        return !is_null($this->winner_id);
    }

    public function updateScore($scoreP1, $scoreP2)
    {
        $this->score_p1 = $scoreP1;
        $this->score_p2 = $scoreP2;
        
        $winnerId = $scoreP1 > $scoreP2 ? $this->player1_id : $this->player2_id;
        $this->winner_id = $winnerId;
        $this->save();
        
        if ($this->next_match_id && $winnerId) {
            $this->updateNextMatch($winnerId);
        }
        
        $this->checkTournamentCompletion();
        
        return $this;
    }
    
    protected function updateNextMatch($winnerId)
    {
        $nextMatch = $this->nextMatch;
        
        if ($nextMatch) {
            if (is_null($nextMatch->player1_id)) {
                $nextMatch->player1_id = $winnerId;
            } elseif (is_null($nextMatch->player2_id)) {
                $nextMatch->player2_id = $winnerId;
            }
            $nextMatch->save();
        }
    }
    
    protected function checkTournamentCompletion()
    {
        $tournament = $this->tournament;
        $maxRound = $tournament->matches()->max('round');
        
        if ($this->round === $maxRound && $this->winner_id) {
            $tournament->status = 'finish';
            $tournament->save();
        }
    }
}