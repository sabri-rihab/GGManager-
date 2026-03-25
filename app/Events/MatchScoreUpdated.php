<?php

namespace App\Events;

use App\Models\MatchGame;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $match;
    
    public function __construct(MatchGame $match)
    {
        $this->match = $match->load(['player1', 'player2', 'winner']);
    }
    
    public function broadcastOn()
    {
        return new Channel('tournament.' . $this->match->tournament_id);
    }
    
    public function broadcastAs()
    {
        return 'match.score.updated';
    }
    
    public function broadcastWith()
    {
        return [
            'match_id' => $this->match->id,
            'tournament_id' => $this->match->tournament_id,
            'round' => $this->match->round,
            'position' => $this->match->position,
            'player1' => [
                'id' => $this->match->player1?->id,
                'name' => $this->match->player1?->name,
                'score' => $this->match->score_p1
            ],
            'player2' => [
                'id' => $this->match->player2?->id,
                'name' => $this->match->player2?->name,
                'score' => $this->match->score_p2
            ],
            'winner' => [
                'id' => $this->match->winner?->id,
                'name' => $this->match->winner?->name
            ],
            'is_completed' => !is_null($this->match->winner_id)
        ];
    }
}