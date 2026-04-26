<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditQuestionnaireSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Relationship: A set has many questions
     */
    public function questions(): HasMany
    {
        return $this->hasMany(AuditQuestion::class, 'questionnaire_set_id');
    }

    /**
     * Relationship: A set has many submissions
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AuditSubmission::class, 'questionnaire_set_id');
    }

    /**
     * Relationship: Who created this set
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Who last updated this set
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get active (non-archived) sets
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->whereNull('deleted_at');
    }

    /**
     * Get draft sets
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft')->whereNull('deleted_at');
    }

    /**
     * Get archived sets
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived')->whereNull('deleted_at');
    }

    /**
     * Get count of questions in this set
     */
    public function getQuestionCountAttribute(): int
    {
        return $this->questions()->count();
    }

    /**
     * Get count of submissions using this set
     */
    public function getSubmissionCountAttribute(): int
    {
        return $this->submissions()->count();
    }

    /**
     * Get categories used in this set
     */
    public function getCategoriesAttribute(): array
    {
        return $this->questions()
            ->distinct('category')
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get questions grouped by category
     */
    public function getQuestionsByCategory(): array
    {
        return $this->questions()
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Check if set can be deleted (no active submissions)
     */
    public function canBeDeleted(): bool
    {
        return $this->submissions()
            ->whereIn('status', ['draft', 'submitted', 'under_review'])
            ->count() === 0;
    }

    /**
     * Get statistics for this questionnaire set
     */
    public function getStatistics(): array
    {
        $submissions = $this->submissions()->get();
        $totalSubmissions = $submissions->count();
        
        if ($totalSubmissions === 0) {
            return [
                'total_submissions' => 0,
                'completed_submissions' => 0,
                'average_risk_level' => null,
                'high_risk_count' => 0,
                'medium_risk_count' => 0,
                'low_risk_count' => 0,
            ];
        }

        $riskCounts = [
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];

        foreach ($submissions as $submission) {
            if ($submission->admin_overall_risk) {
                $riskCounts[$submission->admin_overall_risk]++;
            } elseif ($submission->system_overall_risk) {
                $riskCounts[$submission->system_overall_risk]++;
            }
        }

        return [
            'total_submissions' => $totalSubmissions,
            'completed_submissions' => $submissions->where('status', 'completed')->count(),
            'average_risk_level' => $this->calculateAverageRisk($riskCounts, $totalSubmissions),
            'high_risk_count' => $riskCounts['high'],
            'medium_risk_count' => $riskCounts['medium'],
            'low_risk_count' => $riskCounts['low'],
        ];
    }

    /**
     * Calculate average risk level from counts
     */
    private function calculateAverageRisk(array $counts, int $total): ?string
    {
        $highPercentage = ($counts['high'] / $total) * 100;
        $mediumPercentage = ($counts['medium'] / $total) * 100;

        if ($highPercentage >= 40) {
            return 'high';
        } elseif ($mediumPercentage >= 40) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Duplicate this questionnaire set
     */
    public function duplicate(string $newName, int $userId): self
    {
        $newSet = self::create([
            'name' => $newName,
            'description' => $this->description . ' (Copy)',
            'status' => 'draft',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Duplicate all questions
        $this->questions()->each(function (AuditQuestion $question) use ($newSet, $userId) {
            AuditQuestion::create(array_merge(
                $question->toArray(),
                [
                    'questionnaire_set_id' => $newSet->id,
                ]
            ));
        });

        return $newSet;
    }
}
