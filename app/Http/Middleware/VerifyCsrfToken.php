<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/auth/*', // Temporarily exclude auth routes due to cross-origin cookie issues
        'api/audit-submissions*', // Temporarily exclude all audit submission routes due to cross-origin cookie issues
        'api/vulnerability-submissions*', // Temporarily exclude all vulnerability submission routes due to cross-origin cookie issues
        'api/audit-questions*', // Temporarily exclude all audit question routes due to cross-origin cookie issues
        'api/vulnerabilities*', // Temporarily exclude all vulnerability routes due to cross-origin cookie issues
    ];
}
