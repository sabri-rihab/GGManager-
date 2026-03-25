<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        if (!in_array(auth()->user()->role, $roles)) {
            return response()->json(['message' => 'Unauthorized - Insufficient permissions'], 403);
        }
        
        return $next($request);
    }
}