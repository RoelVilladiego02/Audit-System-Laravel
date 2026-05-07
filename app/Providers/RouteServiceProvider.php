<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Models\AuditAnswer;
use App\Models\AuditSubmission;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Bind submission parameter to AuditSubmission model
        Route::bind('submission', function ($value) {
            try {
                return AuditSubmission::findOrFail((int) $value);
            } catch (\Exception $e) {
                abort(404, 'Audit submission not found');
            }
        });

        // Custom route binding that ensures the answer belongs to the submission (if submission parameter exists)
        Route::bind('answer', function ($value, $route) {
            try {
                // Get the submission from the route if it exists (for routes like /submissions/{submission}/answers/{answer})
                $submission = $route->parameter('submission');
                
                $query = AuditAnswer::where('id', (int) $value);
                
                // If submission parameter exists, verify answer belongs to it
                if ($submission) {
                    $query->where('audit_submission_id', $submission->id);
                }
                
                $answer = $query->first();
                
                if (!$answer) {
                    // If submission parameter was expected but answer doesn't belong to it
                    if ($route->parameter('submission')) {
                        abort(404, 'Audit answer not found or does not belong to this submission');
                    }
                    // Otherwise just answer not found
                    abort(404, 'Audit answer not found');
                }
                
                return $answer;
            } catch (\Throwable $e) {
                // Log but don't rethrow - let Laravel handle it
                Log::error('Route binding error for answer parameter', [
                    'answer_id' => $value,
                    'error' => $e->getMessage(),
                    'route' => $route->getName()
                ]);
                abort(404, 'Could not process request');
            }
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
