<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugAuditRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Log request details
        Log::debug('Audit Request', [
            'path' => $request->path(),
            'method' => $request->method(),
            'parameters' => $request->all(),
            'user_id' => auth()->id(),
            'headers' => $request->headers->all()
        ]);

        $response = $next($request);

        // Log response details
        Log::debug('Audit Response', [
            'status' => $response->status(),
            'content' => $response->getContent()
        ]);

        return $response;
    }
}
