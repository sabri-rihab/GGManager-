<?php

namespace App\Events;

use App\Models\Tournament;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TournamentFinished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tournament;

    public function __construct(Tournament $tournament)
    {
        $this->tournament = $tournament;
    }

    public function broadcastOn()
    {
        return new Channel('tournament.' . $this->tournament->id);
    }

    public function broadcastAs()
    {
        return 'tournament.finished';
    }

    public function broadcastWith()
    {
        $maxRound = $this->tournament->matches()->max('round');
        $finalMatch = $this->tournament->matches()
            ->where('round', $maxRound)
            ->first();

        return [
            'tournament_id' => $this->tournament->id,
            'tournament_name' => $this->tournament->name,
            'champion' => $finalMatch?->winner ? [
                'id' => $finalMatch->winner->id,
                'name' => $finalMatch->winner->name
            ] : null
        ];
    }
}