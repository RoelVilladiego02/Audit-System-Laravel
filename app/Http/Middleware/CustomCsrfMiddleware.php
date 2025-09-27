<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CustomCsrfMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip CSRF for excluded routes
        $excludedRoutes = [
            'api/auth/*',
            'api/audit-submissions*',
            'api/vulnerability-submissions*',
            'api/audit-questions*',
            'api/vulnerabilities*',
        ];

        foreach ($excludedRoutes as $route) {
            if ($request->is($route)) {
                return $next($request);
            }
        }

        // For other routes, check if we have a valid CSRF token
        if ($request->isMethod('POST', 'PUT', 'PATCH', 'DELETE')) {
            $token = $request->header('X-XSRF-TOKEN') ?? $request->input('_token');
            
            if (!$token || !$this->tokensMatch($request, $token)) {
                return response()->json([
                    'message' => 'CSRF token mismatch.',
                    'error' => 'The CSRF token is missing or invalid.'
                ], 419);
            }
        }

        return $next($request);
    }

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $token
     * @return bool
     */
    protected function tokensMatch(Request $request, $token)
    {
        $sessionToken = Session::token();
        
        if (!$sessionToken) {
            // Generate a new token if none exists
            Session::regenerateToken();
            $sessionToken = Session::token();
        }

        return hash_equals($sessionToken, $token);
    }
}
