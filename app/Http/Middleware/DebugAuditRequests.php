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

        // Log response details only in non-production and truncate content
        if (app()->environment('local', 'testing') || config('app.debug')) {
            $content = '';
            try {
                $content = mb_substr($response->getContent(), 0, 1000);
            } catch (\Exception $e) {
                $content = '[unreadable response]';
            }

            Log::debug('Audit Response', [
                'status' => $response->status(),
                'content' => $content
            ]);
        }

        return $response;
    }
}
