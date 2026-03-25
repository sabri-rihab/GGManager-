<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'game' => $this->faker->randomElement(['LoL', 'Valorant', 'CS2']),
            'season' => $this->faker->randomElement(['Spring', 'Summer', 'Fall']),
            'status' => $this->faker->randomElement(['open', 'close', 'finish']),
            'max_participants' => $this->faker->randomElement([8, 16, 32]),
            'organizer_id' => User::factory(),
        ];
    }
}