<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AuditQuestionController;
use App\Http\Controllers\AuditSubmissionController;
use App\Http\Controllers\VulnerabilitySubmissionController;
use App\Http\Controllers\VulnerabilityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AnalyticsController;

// Reduce header size by using shorter route names and grouping
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Handle favicon.ico requests to prevent 431 errors
Route::get('/favicon.ico', function () {
    return response()->file(public_path('favicon.ico'), [
        'Content-Type' => 'image/x-icon',
        'Cache-Control' => 'public, max-age=86400'
    ]);
})->withoutMiddleware(['web', 'auth:sanctum']);

// Protected routes with reduced middleware stack for performance
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Routes accessible by both admin and users
    Route::get('/audit-questions', [AuditQuestionController::class, 'index']);
    Route::get('/audit-questions/{auditQuestion}', [AuditQuestionController::class, 'show']);

    // Admin routes with optimized middleware
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('users', UserController::class);
        
        // Analytics route with query parameter validation
        Route::get('/analytics', [AnalyticsController::class, 'index']);

        // Admin-only audit question operations
        Route::post('/audit-questions', [AuditQuestionController::class, 'store']);
        Route::put('/audit-questions/{auditQuestion}', [AuditQuestionController::class, 'update']);
        Route::delete('/audit-questions/{auditQuestion}', [AuditQuestionController::class, 'destroy']);
        Route::get('/audit-questions-statistics', [AuditQuestionController::class, 'statistics']);
        Route::get('/audit-questions/archived', [AuditQuestionController::class, 'archived']);
        Route::post('/audit-questions/{id}/restore', [AuditQuestionController::class, 'restore']);

        // Admin-only audit submission operations - optimized routes
        Route::prefix('audit-submissions')->name('audit-submissions.')->group(function () {
            Route::put('/{submission}/answers/{answer}/review', [AuditSubmissionController::class, 'reviewAnswer'])
                ->where(['submission' => '[0-9]+', 'answer' => '[0-9]+'])
                ->name('review-answer');
            // Bulk review all answers in a submission
            Route::put('/{submission}/answers/bulk-review', [AuditSubmissionController::class, 'bulkReviewAnswers'])
                ->where(['submission' => '[0-9]+'])
                ->name('bulk-review-answers');
            Route::put('/{submission}/complete', [AuditSubmissionController::class, 'completeReview'])
                ->name('complete');
            Route::get('/admin/dashboard', [AuditSubmissionController::class, 'adminDashboard'])
                ->name('admin.dashboard');
            Route::get('/admin/analytics', [AuditSubmissionController::class, 'analytics'])
                ->name('admin.analytics');
        });

        // Admin vulnerability management - optimized
        Route::prefix('vulnerability-submissions')->name('vulnerability-submissions.')->group(function () {
            Route::get('/admin', [VulnerabilitySubmissionController::class, 'index'])
                ->name('admin.index');
            Route::put('/{submission}/assign', [VulnerabilitySubmissionController::class, 'assign'])
                ->name('assign');
            Route::put('/{submission}/status', [VulnerabilitySubmissionController::class, 'updateStatus'])
                ->name('update-status');
        });

        Route::get('/admin/vulnerabilities', [VulnerabilityController::class, 'index']);
    });

    // User routes
    Route::middleware(['role:user'])->group(function () {
        Route::post('/audit-submissions', [AuditSubmissionController::class, 'store']);
        Route::post('/vulnerability-submissions', [VulnerabilitySubmissionController::class, 'store']);
        Route::get('/my-vulnerability-submissions', [VulnerabilitySubmissionController::class, 'index']);
        Route::get('/my-vulnerabilities', [VulnerabilityController::class, 'index']);
    });

    // Common routes for both roles (with permission checks inside controllers)
    Route::prefix('audit-submissions')->name('audit-submissions.')->group(function () {
        Route::get('/', [AuditSubmissionController::class, 'index'])->name('index');
        Route::get('/{submission}', [AuditSubmissionController::class, 'show'])->name('show');
    });

    Route::prefix('vulnerability-submissions')->name('vulnerability-submissions.')->group(function () {
        Route::get('/status/{status}', [VulnerabilitySubmissionController::class, 'byStatus'])
            ->where('status', 'open|resolved')
            ->name('by-status');
        Route::get('/assigned/{userId}', [VulnerabilitySubmissionController::class, 'byAssignee'])
            ->where('userId', '[0-9]+')
            ->name('by-assignee');
        Route::get('/{submission}', [VulnerabilitySubmissionController::class, 'show'])->name('show');
        Route::put('/{submission}', [VulnerabilitySubmissionController::class, 'update'])->name('update');
        Route::delete('/{submission}', [VulnerabilitySubmissionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('vulnerabilities')->name('vulnerabilities.')->group(function () {
        Route::get('/', [VulnerabilityController::class, 'index'])->name('index');
        Route::get('/{vulnerability}', [VulnerabilityController::class, 'show'])->name('show');
        Route::put('/{vulnerability}', [VulnerabilityController::class, 'update'])->name('update');
        Route::delete('/{vulnerability}', [VulnerabilityController::class, 'destroy'])->name('destroy');
    });
});