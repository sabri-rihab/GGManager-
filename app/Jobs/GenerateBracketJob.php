<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Providers\BracketGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBracketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $tournament;
    
    public function __construct(Tournament $tournament)
    {
        $this->tournament = $tournament;
    }
    
    public function handle(BracketGenerator $generator)
    {
        $generator->generate($this->tournament);
    }
}