<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     * 
     * Override to return JSON for API routes instead of HTML error pages
     */
    public function render(Request $request, Throwable $exception): Response
    {
        // If request is for API endpoint, always return JSON
        if ($request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Server error',
                'error_type' => class_basename($exception),
                'debug' => [
                    'file' => basename($exception->getFile()),
                    'line' => $exception->getLine(),
                    'message' => $exception->getMessage(),
                ]
            ], $this->getStatusCode($exception));
        }

        return parent::render($request, $exception);
    }

    /**
     * Get appropriate HTTP status code for exception
     */
    private function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 404;
        }
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return 422;
        }
        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }
        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }
        return 500;
    }
}
