<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $allowedRoles = explode('|', $roles); // Changed from comma to pipe separator

        // Log the middleware execution for debugging
        Log::info('CheckRole middleware executed', [
            'required_roles' => $allowedRoles,
            'user_authenticated' => $request->user() !== null,
            'user_id' => $request->user()?->id,
            'user_role' => $request->user()?->role,
            'route' => $request->route()?->getName() ?? $request->path(),
            'method' => $request->method()
        ]);

        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated. Please log in.',
                'error' => 'USER_NOT_AUTHENTICATED'
            ], 401);
        }

        // Check if user has any of the required roles
        $userRole = $request->user()->role;
        
        // Allow access if user role matches any of the allowed roles
        if (!in_array($userRole, $allowedRoles, true)) {
            Log::warning('Role access denied', [
                'user_role' => $userRole,
                'required_roles' => $allowedRoles,
                'user_id' => $request->user()->id
            ]);
            
            return response()->json([
                'message' => 'Unauthorized. Insufficient permissions.',
                'error' => 'INSUFFICIENT_ROLE',
                'required_roles' => $allowedRoles,
                'user_role' => $userRole
            ], 403);
        }

        return $next($request);
    }
}