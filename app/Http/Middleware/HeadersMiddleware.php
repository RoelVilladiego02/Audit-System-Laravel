<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HeadersMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$response) {
            return $response;
        }

        // Get the origin from the request
        $origin = $request->headers->get('Origin');
        $allowedOrigins = config('cors.allowed_origins');
        
        // Check if the origin is in the allowed list
        if (in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } else {
            // Fallback to first allowed origin for development
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigins[0]);
        }
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, X-XSRF-TOKEN, X-HTTP-Method-Override, ngrok-skip-browser-warning');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        
        // Remove any duplicate cookies
        $cookies = array_unique($response->headers->getCookies(), SORT_REGULAR);
        $response->headers->setCookies($cookies);

        return $response;
    }
}
