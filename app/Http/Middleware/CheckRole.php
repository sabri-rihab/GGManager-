<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'You must be logged in to access this resource'
            ], 401);
        }
        
        $userRole = $user->role;
        
        if (!in_array($userRole, $roles)) {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Insufficient permissions',
                'required_roles' => $roles,
                'your_role' => $userRole
            ], 403);
        }
        
        return $next($request);
    }
}