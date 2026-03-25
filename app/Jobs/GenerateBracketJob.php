<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Models\MatchGame;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateBracketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tournament;

    public function __construct(Tournament $tournament)
    {
        $this->tournament = $tournament;
    }

    public function handle()
    {
        $participants = $this->tournament->participants()->pluck('user_id')->toArray();
        shuffle($participants);
        
        $numParticipants = count($participants);
        $nextPowerOfTwo = pow(2, ceil(log($numParticipants, 2)));
        $numByes = $nextPowerOfTwo - $numParticipants;
        
        DB::transaction(function () use ($participants, $numByes) {
            $this->generateMatches($participants, $numByes);
        });
    }

    protected function generateMatches($participants, $numByes)
    {
        $round = 1;
        $matches = [];
        $totalMatches = count($participants) / 2;
        
        // Generate first round matches
        for ($i = 0; $i < count($participants); $i += 2) {
            $player1 = $participants[$i] ?? null;
            $player2 = $participants[$i + 1] ?? null;
            
            $match = MatchGame::create([
                'tournament_id' => $this->tournament->id,
                'player1_id' => $player1,
                'player2_id' => $player2,
                'round' => $round,
                'position' => $i / 2,
                'is_bye' => is_null($player2),
            ]);
            
            $matches[] = $match;
            
            // Handle bye matches (auto-advance)
            if (is_null($player2) && $player1) {
                $match->winner_id = $player1;
                $match->save();
            }
        }
        
        // Generate subsequent rounds
        $currentMatches = $matches;
        $round = 2;
        
        while (count($currentMatches) > 1) {
            $nextRoundMatches = [];
            $nextMatchesCount = count($currentMatches) / 2;
            
            for ($i = 0; $i < count($currentMatches); $i += 2) {
                $nextMatch = MatchGame::create([
                    'tournament_id' => $this->tournament->id,
                    'round' => $round,
                    'position' => $i / 2,
                    'is_bye' => false,
                ]);
                
                // Link current matches to next match
                $currentMatches[$i]->next_match_id = $nextMatch->id;
                $currentMatches[$i]->save();
                
                if (isset($currentMatches[$i + 1])) {
                    $currentMatches[$i + 1]->next_match_id = $nextMatch->id;
                    $currentMatches[$i + 1]->save();
                }
                
                $nextRoundMatches[] = $nextMatch;
            }
            
            $currentMatches = $nextRoundMatches;
            $round++;
        }
    }
}