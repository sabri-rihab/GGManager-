
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('player1_id')->nullable()->constrained('users');
            $table->foreignId('player2_id')->nullable()->constrained('users');
            $table->integer('score_p1')->nullable();
            $table->integer('score_p2')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users');
            $table->foreignId('next_match_id')->nullable()->constrained('matches');
            $table->boolean('is_bye')->default(false);
            $table->integer('round');
            $table->integer('position'); // Position in the bracket
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};