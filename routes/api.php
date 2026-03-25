<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\MatchController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Tournament routes
    Route::apiResource('tournaments', TournamentController::class);
    Route::post('/tournaments/{tournament}/register', [TournamentController::class, 'register']);
    Route::get('/tournaments/{tournament}/participants', [TournamentController::class, 'participants']);
    Route::post('/tournaments/{tournament}/close-registrations', [TournamentController::class, 'closeRegistrations']);
    Route::get('/tournaments/{tournament}/bracket', [TournamentController::class, 'bracket']);
    
    // Match routes
    Route::get('/tournaments/{tournament}/matches', [MatchController::class, 'index']);
    Route::get('/tournaments/{tournament}/matches/{match}', [MatchController::class, 'show']);
    Route::put('/tournaments/{tournament}/matches/{match}/score', [MatchController::class, 'updateScore']);
});