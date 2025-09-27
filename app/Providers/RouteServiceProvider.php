<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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

        // Custom route binding that ensures the answer belongs to the submission
        Route::bind('answer', function ($value, $route) {
            try {
                // Get the submission from the route
                $submission = $route->parameter('submission');
                if (!$submission) {
                    abort(404, 'Submission not found');
                }

                return AuditAnswer::where('id', (int) $value)
                    ->where('audit_submission_id', $submission->id)
                    ->firstOrFail();
            } catch (\Exception $e) {
                abort(404, 'Audit answer not found or does not belong to this submission');
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
