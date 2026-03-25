<?php

namespace App\Events;

use App\Models\MatchGame;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $match;

    public function __construct(MatchGame $match)
    {
        $this->match = $match;
    }

    public function broadcastOn()
    {
        return new Channel('tournament.' . $this->match->tournament_id);
    }
}