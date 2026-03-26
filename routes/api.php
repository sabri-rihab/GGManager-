<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\BracketController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==================== PUBLIC ROUTES ====================
Route::prefix('v1')->group(function () {
    
    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Public tournament endpoints
    Route::get('/tournaments', [TournamentController::class, 'index']);
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
    Route::get('/tournaments/{tournament}/bracket', [BracketController::class, 'show']);
    Route::get('/tournaments/{tournament}/matches', [MatchController::class, 'index']);
});

// ==================== PROTECTED ROUTES ====================
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // User profile
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // ========== TOURNAMENT MANAGEMENT ==========
    // Organizer only
    Route::post('/tournaments', [TournamentController::class, 'store'])
        ->middleware('role:organizer');
    
    Route::put('/tournaments/{tournament}', [TournamentController::class, 'update'])
        ->middleware('role:organizer');
    
    Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy'])
        ->middleware('role:organizer');
    
    Route::post('/tournaments/{tournament}/close-registrations', [TournamentController::class, 'closeRegistrations'])
        ->middleware('role:organizer');
    
    // ========== PARTICIPATION ==========
    // Player only
    Route::post('/tournaments/{tournament}/register', [TournamentController::class, 'register'])
        ->middleware('role:player');
    
    Route::delete('/tournaments/{tournament}/unregister', [TournamentController::class, 'unregister'])
        ->middleware('role:player');
    
    Route::get('/tournaments/{tournament}/participants', [TournamentController::class, 'participants']);
    
    // ========== MATCH MANAGEMENT ==========
    // Organizer only - Update scores
    Route::put('/tournaments/{tournament}/matches/{match}/score', [ScoreController::class, 'update'])
        ->middleware('role:organizer');
    
    // Public match viewing
    Route::get('/tournaments/{tournament}/matches/{match}', [MatchController::class, 'show']);
    
    // ========== BRACKET ==========
    Route::get('/tournaments/{tournament}/bracket/full', [BracketController::class, 'full']);
    Route::get('/tournaments/{tournament}/bracket/simple', [BracketController::class, 'simple']);
    
    // ========== USER DASHBOARD ==========
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/my-matches', [MatchController::class, 'myMatches']);
    Route::get('/my-tournaments', [TournamentController::class, 'myTournaments']);
    Route::get('/my-participations', [TournamentController::class, 'myParticipations']);
    
    // ========== ADMIN / ORGANIZER DASHBOARD ==========
    Route::middleware('role:organizer')->prefix('organizer')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'organizerDashboard']);
        Route::get('/tournaments', [TournamentController::class, 'organizerTournaments']);
        Route::get('/stats', [TournamentController::class, 'organizerStats']);
        Route::get('/matches/pending', [MatchController::class, 'pendingMatches']);
    });
    
    // ========== SEARCH & FILTERS ==========
    Route::get('/search/tournaments', [TournamentController::class, 'search']);
    Route::get('/search/players', [TournamentController::class, 'searchPlayers']);
});