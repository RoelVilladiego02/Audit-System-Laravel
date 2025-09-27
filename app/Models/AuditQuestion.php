<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'question',
        'description',
        'category',
        'possible_answers',
        'risk_criteria',
        'possible_recommendation',
    ];

    protected $casts = [
        'id' => 'integer',
        'category' => 'string',
        'possible_answers' => 'array',
        'risk_criteria' => 'array',
        'possible_recommendation' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(AuditSubmission::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AuditAnswer::class, 'audit_question_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function hasValidRiskCriteria(): bool
    {
        $criteria = $this->risk_criteria;
        
        if (!is_array($criteria)) {
            return false;
        }

        return isset($criteria['high']) || isset($criteria['medium']) || isset($criteria['low']);
    }

    public function getFormattedRiskCriteriaAttribute(): string
    {
        if (!$this->hasValidRiskCriteria()) {
            return 'No criteria defined';
        }

        $criteria = $this->risk_criteria;
        $formatted = [];

        if (!empty($criteria['high'])) {
            $formatted[] = "High: " . (is_array($criteria['high']) ? implode(', ', $criteria['high']) : $criteria['high']);
        }
        if (!empty($criteria['medium'])) {
            $formatted[] = "Medium: " . (is_array($criteria['medium']) ? implode(', ', $criteria['medium']) : $criteria['medium']);
        }
        if (!empty($criteria['low'])) {
            $formatted[] = "Low: " . (is_array($criteria['low']) ? implode(', ', $criteria['low']) : $criteria['low']);
        }

        return implode(' | ', $formatted);
    }

    public function getPossibleAnswersStringAttribute(): string
    {
        if (!is_array($this->possible_answers)) {
            return '';
        }

        return implode(', ', $this->possible_answers);
    }

    public function isValidAnswer(string $answer): bool
    {
        if (!is_array($this->possible_answers)) {
            return false;
        }

        // Direct match with possible answers
        if (in_array($answer, $this->possible_answers, true)) {
            return true;
        }

        // If "Others" is a valid option, any non-empty string is valid
        // This handles the case where custom text is passed but "Others" is allowed
        if (in_array('Others', $this->possible_answers, true) && !empty(trim($answer))) {
            return true;
        }

        return false;
    }

    // Also add a helper method to check if custom answers are allowed
    public function allowsCustomAnswers(): bool
    {
        return is_array($this->possible_answers) && in_array('Others', $this->possible_answers, true);
    }

    public function getUsageStats(): array
    {
        $totalSubmissions = $this->answers()->count();
        $responseStats = [];

        if (is_array($this->possible_answers)) {
            foreach ($this->possible_answers as $answer) {
                $count = $this->answers()
                    ->where('answer', $answer)
                    ->count();
                
                $responseStats[$answer] = [
                    'count' => $count,
                    'percentage' => $totalSubmissions > 0 ? round(($count / $totalSubmissions) * 100, 2) : 0
                ];
            }
        }

        return [
            'total_responses' => $totalSubmissions,
            'answer_distribution' => $responseStats,
            'last_used' => $this->answers()->latest()->first()?->created_at,
        ];
    }
}