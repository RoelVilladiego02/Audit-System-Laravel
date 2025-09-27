<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    // Relationships
    public function vulnerabilitySubmissions()
    {
        return $this->hasMany(VulnerabilitySubmission::class);
    }

    public function auditSubmissions()
    {
        return $this->hasMany(AuditSubmission::class);
    }

    public function assignedVulnerabilities()
    {
        return $this->hasMany(VulnerabilitySubmission::class, 'assigned_to');
    }

    public function reviewedAudits()
    {
        return $this->hasMany(AuditSubmission::class, 'reviewed_by');
    }

    public function reviewedAnswers()
    {
        return $this->hasMany(AuditAnswer::class, 'reviewed_by');
    }

    // Admin dashboard methods
    public function getPendingAuditReviewsCount(): int
    {
        if (!$this->isAdmin()) return 0;
        
        return AuditAnswer::pendingReview()->count();
    }

    public function getPendingVulnerabilitiesCount(): int
    {
        if (!$this->isAdmin()) return 0;
        
        return VulnerabilitySubmission::open()->count();
    }

    public function getHighRiskItemsCount(): int
    {
        if (!$this->isAdmin()) return 0;
        
        $highRiskAudits = AuditAnswer::highRisk()->count();
        $highRiskVulns = VulnerabilitySubmission::highPriority()->count();
        
        return $highRiskAudits + $highRiskVulns;
    }
}