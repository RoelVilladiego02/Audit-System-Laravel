<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\VulnerabilitySubmission;
use App\Models\Vulnerability;

class AuditSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'system_overall_risk',
        'admin_overall_risk',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_summary',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'reviewed_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AuditAnswer::class, 'audit_submission_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Get answers pending admin review
    public function pendingAnswers()
    {
        return $this->answers()->pendingReview();
    }

    // Check if submission is fully reviewed
    public function isFullyReviewed(): bool
    {
        return $this->pendingAnswers()->count() === 0;
    }

    // Get review progress percentage
    public function getReviewProgressAttribute(): float
    {
        $total = $this->answers()->count();
        if ($total === 0) return 0.0;
        
        $reviewed = $this->answers()->whereNotNull('reviewed_by')->count();
        return round(($reviewed / $total) * 100, 2);
    }

    // Calculate system overall risk from all answers
    public function calculateSystemOverallRisk(): string
    {
        $answers = $this->answers()->with('question')->get();
        if ($answers->isEmpty()) return 'low';

        $highCount = 0;
        $mediumCount = 0;
        $lowCount = 0;

        foreach ($answers as $answer) {
            // Ensure we have a proper risk level
            $riskLevel = $answer->admin_risk_level ?? $answer->system_risk_level ?? 'low';
            switch ($riskLevel) {
                case 'high': $highCount++; break;
                case 'medium': $mediumCount++; break;
                case 'low': $lowCount++; break;
            }
        }

        $total = $highCount + $mediumCount + $lowCount;
        if ($total === 0) return 'low';

        $highPercentage = ($highCount / $total) * 100;
        $mediumPercentage = ($mediumCount / $total) * 100;

        // If more than 40% are high risk, overall is high (adjusted for binary system)
        if ($highPercentage >= 40) return 'high';
        
        // If more than 20% are high risk, overall is medium
        if ($highPercentage >= 20) return 'medium';
        
        // If more than 50% are medium risk, overall is medium
        if ($mediumPercentage >= 50) return 'medium';
        
        // If any high risk exists, minimum is medium
        if ($highCount > 0) return 'medium';
        
        return 'low';
    }

    // Get the effective overall risk (admin override or system calculated)
    public function getEffectiveOverallRiskAttribute(): string
    {
        return $this->admin_overall_risk ?? $this->system_overall_risk ?? 'pending';
    }

    // Recalculate and update system overall risk
    public function recalculateSystemOverallRisk(): string
    {
        $newRisk = $this->calculateSystemOverallRisk();
        $this->update(['system_overall_risk' => $newRisk]);
        return $newRisk;
    }

    // Scopes
    public function scopePendingReview($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Check if this submission has any high risk answers or overall risk
     */
    public function hasHighRisk(): bool
    {
        if ($this->admin_overall_risk === 'high') {
            return true;
        }

        return $this->answers()->where(function($query) {
            $query->where('admin_risk_level', 'high')
                ->orWhere('system_risk_level', 'high');
        })->exists();
    }

    /**
     * Create a vulnerability submission from this audit submission
     */
    /**
     * Override the update method to verify data persistence
     *
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        Log::info('Updating submission with attributes', [
            'submission_id' => $this->id,
            'current_state' => $this->toArray(),
            'update_attributes' => $attributes
        ]);

        $result = parent::update($attributes, $options);
        $updated = self::find($this->id);

        foreach ($attributes as $key => $value) {
            if ($key === 'reviewed_at' && $value instanceof \Carbon\Carbon) {
                $expected = $value->toDateTimeString();
                $actual = $updated->$key ? $updated->$key->toDateTimeString() : null;
                if ($actual !== $expected) {
                    Log::error('Submission update verification failed for timestamp', [
                        'submission_id' => $this->id,
                        'field' => $key,
                        'expected' => $expected,
                        'actual' => $actual
                    ]);
                    throw new \Exception("Failed to verify submission update for field: {$key}");
                }
            } elseif ($updated->$key !== $value) {
                Log::error('Submission update verification failed', [
                    'submission_id' => $this->id,
                    'field' => $key,
                    'expected' => $value,
                    'actual' => $updated->$key
                ]);
                throw new \Exception("Failed to verify submission update for field: {$key}");
            }
        }

        Log::info('Submission update verified successfully', [
            'submission_id' => $this->id,
            'verified_attributes' => $attributes
        ]);
        return $result;
    }

    public function createVulnerabilitySubmission(): ?VulnerabilitySubmission
    {
        if (!$this->hasHighRisk()) {
            Log::info('Skipping vulnerability creation - no high risks found', [
                'audit_submission_id' => $this->id
            ]);
            return null;
        }

        return DB::transaction(function () {
            $highRiskAnswers = $this->answers()
                ->with('question')
                ->where(function($query) {
                    $query->where('admin_risk_level', 'high')
                        ->orWhere('system_risk_level', 'high');
                })
                ->get();

            // Create vulnerabilities for high-risk answers OR if admin marked overall as high
            if ($highRiskAnswers->isEmpty() && $this->admin_overall_risk !== 'high') {
                Log::info('No high risk conditions met for vulnerability creation', [
                    'audit_submission_id' => $this->id,
                    'admin_overall_risk' => $this->admin_overall_risk,
                    'high_risk_answers_count' => 0
                ]);
                return null;
            }

            // If admin overall risk is high but no specific high-risk answers, create a general vulnerability
            if ($highRiskAnswers->isEmpty() && $this->admin_overall_risk === 'high') {
                $highRiskAnswers = collect([
                    (object)[
                        'question' => (object)['question' => 'Overall Security Assessment'],
                        'admin_risk_level' => 'high',
                        'system_risk_level' => null,
                        'recommendation' => 'Address overall security concerns identified during audit review.'
                    ]
                ]);
            }

            // Calculate risk level based on audit's overall risk
            $riskLevel = $this->admin_overall_risk ?? $this->system_overall_risk ?? 'high';
            
            $vulnSubmission = VulnerabilitySubmission::create([
                'user_id' => $this->user_id,
                'title' => "High Risk Audit - {$this->title}",
                'status' => 'open',
                'risk_score' => $riskLevel === 'high' ? 75 : ($riskLevel === 'medium' ? 50 : 25),
                'risk_level' => $riskLevel,
            ]);

            Log::info('Created vulnerability submission', [
                'audit_submission_id' => $this->id,
                'vulnerability_submission_id' => $vulnSubmission->id
            ]);

            foreach ($highRiskAnswers as $answer) {
                $vulnerability = $vulnSubmission->vulnerabilities()->create([
                    'category' => $answer->question->category ?? 'General',
                    'title' => $answer->question->question ?? 'High Risk Audit Finding',
                    'severity' => $answer->admin_risk_level ?? $answer->system_risk_level ?? 'high',
                    'remediation_steps' => $answer->recommendation ?? 'Review and address the high-risk audit finding.',
                    'is_resolved' => false
                ]);

                Log::info('Created vulnerability from audit answer', [
                    'vulnerability_id' => $vulnerability->id,
                    'audit_answer_id' => $answer->id
                ]);
            }

            return $vulnSubmission;
        });
    }
}