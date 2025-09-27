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
        $allowedOrigins = (array) config('cors.allowed_origins', []);

        // Choose the origin header to send back
        if ($origin && in_array($origin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
        } elseif (!empty($allowedOrigins) && is_array($allowedOrigins)) {
            // safe fallback - pick the first allowed origin
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigins[0]);
        } else {
            // Final fallback - allow all (development only)
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        // Keep headers minimal and remove dev-only ngrok header
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, X-XSRF-TOKEN, X-HTTP-Method-Override');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
