<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class AuditAnswer extends Model
{
    protected $fillable = [
        'audit_submission_id',
        'audit_question_id',
        'answer',
        'selected_answer', // Add this if you're using the enhanced approach
        'system_risk_level',
        'admin_risk_level',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'recommendation',
        'status',
        'is_custom_answer',
        'proof_image_path',
        'proof_image_name',
        'proof_image_validated',
        'proof_image_validation_error',
    ];

    protected $casts = [
        'id' => 'integer',
        'audit_submission_id' => 'integer',
        'audit_question_id' => 'integer',
        'reviewed_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'is_custom_answer' => 'boolean',
        'proof_image_validated' => 'boolean',
    ];

    public function auditSubmission(): BelongsTo
    {
        return $this->belongsTo(AuditSubmission::class, 'audit_submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(AuditQuestion::class, 'audit_question_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Alias for backward compatibility
    public function submission(): BelongsTo
    {
        return $this->auditSubmission();
    }

    // Get the effective risk level (admin override or system calculated)
    public function getEffectiveRiskLevelAttribute(): string
    {
        return $this->admin_risk_level ?? $this->system_risk_level ?? 'pending';
    }

    // Check if this answer has been reviewed by admin
    public function isReviewed(): bool
    {
        return !is_null($this->reviewed_by);
    }

    // Calculate system risk level based on question criteria
    public function calculateSystemRiskLevel(): string
    {
        $question = $this->question;
        if (!$question) {
            // Try to load the question if it's not loaded
            $question = AuditQuestion::find($this->audit_question_id);
        }
        
        if (!$question || !is_array($question->risk_criteria)) {
            return 'low';
        }

        $criteria = $question->risk_criteria;
        
        // If this is a custom answer, default to low risk
        if ($this->is_custom_answer) {
            Log::info('Custom answer detected, defaulting to low risk', [
                'answer_id' => $this->id,
                'answer' => $this->answer,
                'is_custom' => $this->is_custom_answer
            ]);
            return 'low';
        }

        // For regular answers, use the selected answer for risk assessment
        $answerToCheck = $this->selected_answer ?? $this->answer;
        
        // Special handling for "Yes" answers with proof image requirement
        if (strtolower(trim($answerToCheck)) === 'yes') {
            if ($this->requiresProofImage()) {
                if (!$this->isAnswerValid()) {
                    // "Yes" answer without valid proof image should not pass validation
                    Log::warning('Yes answer failed proof image validation', [
                        'answer_id' => $this->id,
                        'has_image' => $this->hasProofImage(),
                        'image_validated' => $this->proof_image_validated,
                        'validation_error' => $this->proof_image_validation_error
                    ]);
                    // Return high risk if image is required but not valid
                    return 'high';
                }
                // If "yes" with valid proof image, it's low risk
                Log::info('Yes answer with valid proof image - low risk', [
                    'answer_id' => $this->id,
                    'image_name' => $this->proof_image_name
                ]);
                return 'low';
            }
        }
        
        // Match answer against risk criteria
        foreach (['high', 'medium', 'low'] as $level) {
            if (isset($criteria[$level])) {
                // Handle both string and array criteria
                $levelCriteria = is_array($criteria[$level]) ? $criteria[$level] : [$criteria[$level]];
                if (in_array($answerToCheck, $levelCriteria, true)) {
                    Log::info('Risk level determined from criteria', [
                        'answer_id' => $this->id,
                        'answer_to_check' => $answerToCheck,
                        'determined_level' => $level,
                        'criteria_matched' => $levelCriteria
                    ]);
                    return $level;
                }
            }
        }

        Log::info('No risk criteria matched, defaulting to low', [
            'answer_id' => $this->id,
            'answer_to_check' => $answerToCheck,
            'available_criteria' => $criteria
        ]);
        
        return 'low'; // Default fallback
    }

    // Admin reviews and rates this answer
    public function reviewByAdmin(User $admin, string $riskLevel, ?string $notes = null, ?string $recommendation = null): bool
    {
        if (!$admin->isAdmin()) {
            throw new \Exception('Only admins can review audit answers');
        }
        if (!in_array($riskLevel, ['low', 'medium', 'high'])) {
            throw new \Exception('Invalid risk level');
        }

        // If high risk and no custom recommendation, use possible_recommendation from question
        if ($riskLevel === 'high' && !$recommendation) {
            $recommendation = $this->question->possible_recommendation ?? 'Review required to address potential security concerns.';
        } else {
            $recommendation = $recommendation ? trim($recommendation) : 'Review required to address potential security concerns.';
        }
        
        $updateData = [
            'admin_risk_level' => $riskLevel,
            'reviewed_by' => (int) $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes ? trim($notes) : '',
            'recommendation' => $recommendation,
            'status' => 'reviewed',
        ];

        Log::info('Updating audit answer', [
            'answer_id' => $this->id,
            'update_data' => $updateData
        ]);

        $result = $this->update($updateData);

        // Verify update
        $updated = self::find($this->id);
        foreach ($updateData as $key => $value) {
            if ($key === 'reviewed_at' && $value instanceof \Carbon\Carbon) {
                $expected = $value->toDateTimeString();
                $actual = $updated->$key ? $updated->$key->toDateTimeString() : null;
                if ($actual !== $expected) {
                    Log::error('Answer update verification failed for timestamp', [
                        'answer_id' => $this->id,
                        'field' => $key,
                        'expected' => $expected,
                        'actual' => $actual
                    ]);
                    throw new \Exception("Failed to verify answer update for field: {$key}");
                }
            } elseif ($updated->$key !== $value) {
                Log::error('Answer update verification failed', [
                    'answer_id' => $this->id,
                    'field' => $key,
                    'expected' => $value,
                    'actual' => $updated->$key
                ]);
                throw new \Exception("Failed to verify answer update for field: {$key}");
            }
        }

        // Update submission status
        $submission = $this->auditSubmission;
        if ($submission && $submission->status === 'submitted') {
            $submission->update(['status' => 'under_review']);
        }

        return $result;
    }

    /**
     * Check if this answer requires a proof image
     * Images are required for "Yes" answers only
     */
    public function requiresProofImage(): bool
    {
        // Normalize answer for comparison
        $answer = strtolower(trim($this->answer));
        return $answer === 'yes';
    }

    /**
     * Check if a proof image has been uploaded
     */
    public function hasProofImage(): bool
    {
        return !empty($this->proof_image_path) && !empty($this->proof_image_name);
    }

    /**
     * Check if proof image upload is missing when required
     */
    public function isProofImageMissing(): bool
    {
        return $this->requiresProofImage() && !$this->hasProofImage();
    }

    /**
     * Validate the proof image based on filename
     * The filename should be meaningful and match expected patterns
     * Validation is based on config/proof_images.php configuration
     * 
     * @param string $filename The original filename of the uploaded image
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateProofImageName(string $filename): array
    {
        $config = config('proof_images', []);
        $validationMode = $config['validation_mode'] ?? 'blacklist';
        $minLength = $config['min_filename_length'] ?? 3;
        $useKeywords = $config['use_required_keywords'] ?? false;
        $keywords = $config['required_keywords'] ?? [];

        if (empty(trim($filename))) {
            return [
                'valid' => false,
                'message' => $config['messages']['empty_filename'] ?? 'Image filename cannot be empty.'
            ];
        }

        // Extract filename without path
        $filename = basename($filename);
        
        // Get filename without extension
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        
        // Check if filename is too short
        if (strlen($nameWithoutExt) < $minLength) {
            return [
                'valid' => false,
                'message' => $config['messages']['too_short'] ?? "Image filename is too short. Use descriptive names (at least {$minLength} characters)."
            ];
        }

        // Check file extension
        $allowedExtensions = $config['allowed_extensions'] ?? ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            return [
                'valid' => false,
                'message' => str_replace(':extensions', implode(', ', $allowedExtensions), 
                    $config['messages']['invalid_extension'] ?? 'Invalid file extension.')
            ];
        }

        // Apply validation mode
        if ($validationMode === 'whitelist' || $validationMode === 'combined') {
            $whitelist = $config['whitelist'] ?? [];
            $whitelistMatch = false;

            foreach ($whitelist as $pattern) {
                if (preg_match($pattern, $filename)) {
                    $whitelistMatch = true;
                    break;
                }
            }

            if (!$whitelistMatch && $validationMode === 'whitelist') {
                Log::warning('Filename failed whitelist validation', [
                    'answer_id' => $this->id,
                    'filename' => $filename
                ]);
                return [
                    'valid' => false,
                    'message' => $config['messages']['whitelist_fail'] ?? 
                        'Image filename does not match required naming patterns.'
                ];
            }

            if ($validationMode === 'combined' && !$whitelistMatch) {
                // Continue to blacklist check
                $validationMode = 'blacklist';
            }
        }

        // Apply blacklist validation
        if ($validationMode === 'blacklist' || $validationMode === 'combined') {
            $blacklist = $config['blacklist'] ?? [];

            foreach ($blacklist as $pattern) {
                if (preg_match($pattern, $filename)) {
                    Log::warning('Filename matched blacklist pattern', [
                        'answer_id' => $this->id,
                        'filename' => $filename,
                        'pattern' => $pattern
                    ]);
                    return [
                        'valid' => false,
                        'message' => $config['messages']['generic_name'] ?? 
                            'Image filename appears to be generic or placeholder. Please use a descriptive name.'
                    ];
                }
            }
        }

        // Check if keywords are required
        if ($useKeywords && !empty($keywords)) {
            $keywordMatch = false;
            $lowerFilename = strtolower($nameWithoutExt);

            foreach ($keywords as $keyword) {
                if (stripos($lowerFilename, $keyword) !== false) {
                    $keywordMatch = true;
                    break;
                }
            }

            if (!$keywordMatch) {
                Log::warning('Filename missing required keywords', [
                    'answer_id' => $this->id,
                    'filename' => $filename,
                    'keywords' => $keywords
                ]);
                return [
                    'valid' => false,
                    'message' => 'Image filename must contain at least one relevant keyword (e.g., firewall, access, config, audit).'
                ];
            }
        }

        // If we reach here, filename is valid
        Log::info('Proof image filename validated successfully', [
            'answer_id' => $this->id,
            'filename' => $filename,
            'filename_without_ext' => $nameWithoutExt,
            'validation_mode' => $validationMode
        ]);

        return [
            'valid' => true,
            'message' => $config['messages']['success'] ?? 'Image filename is valid and descriptive.'
        ];
    }

    /**
     * Store proof image and validate it
     * 
     * @param string $imagePath Path where image is stored
     * @param string $imageName Original filename
     * @return bool Whether the image passed validation
     */
    public function storeProofImage(string $imagePath, string $imageName): bool
    {
        // If answer doesn't require image, accept it anyway
        if (!$this->requiresProofImage()) {
            Log::info('Proof image provided for non-yes answer', [
                'answer_id' => $this->id,
                'answer' => $this->answer
            ]);
            $this->update([
                'proof_image_path' => $imagePath,
                'proof_image_name' => $imageName,
                'proof_image_validated' => true,
                'proof_image_validation_error' => null
            ]);
            return true;
        }

        // Validate the image name
        $validation = $this->validateProofImageName($imageName);
        
        $this->update([
            'proof_image_path' => $imagePath,
            'proof_image_name' => $imageName,
            'proof_image_validated' => $validation['valid'],
            'proof_image_validation_error' => !$validation['valid'] ? $validation['message'] : null
        ]);

        Log::info('Proof image stored', [
            'answer_id' => $this->id,
            'image_path' => $imagePath,
            'image_name' => $imageName,
            'validated' => $validation['valid']
        ]);

        return $validation['valid'];
    }

    /**
     * Check if the answer is valid considering proof image requirements
     * 
     * For "yes" answers: Valid only if proof image is provided AND validated
     * For "no" answers: Always valid (no image required)
     * 
     * @return bool
     */
    public function isAnswerValid(): bool
    {
        // For "no" answers or other non-yes answers, always valid
        if (!$this->requiresProofImage()) {
            return true;
        }

        // For "yes" answers, must have a validated image
        if (!$this->hasProofImage()) {
            Log::warning('Yes answer missing proof image', [
                'answer_id' => $this->id
            ]);
            return false;
        }

        // Image must be validated
        if (!$this->proof_image_validated) {
            Log::warning('Yes answer has unvalidated proof image', [
                'answer_id' => $this->id,
                'validation_error' => $this->proof_image_validation_error
            ]);
            return false;
        }

        return true;
    }

    /**
     * Get validation status message
     */
    public function getProofImageValidationStatus(): string
    {
        if (!$this->requiresProofImage()) {
            return 'No image required for this answer.';
        }

        if (!$this->hasProofImage()) {
            return 'Proof image is missing. "Yes" answers require uploaded proof.';
        }

        if (!$this->proof_image_validated) {
            return $this->proof_image_validation_error ?? 'Proof image failed validation.';
        }

        return 'Proof image validated successfully.';
    }

    // Scopes

    public function scopePendingReview($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'pending')
              ->orWhereNull('status')
              ->orWhereNull('reviewed_by');
        });
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed')
                    ->whereNotNull('reviewed_by');
    }

    public function scopeHighRisk($query)
    {
        return $query->where(function($q) {
            $q->where('admin_risk_level', 'high')
              ->orWhere(function($subQ) {
                  $subQ->whereNull('admin_risk_level')
                       ->where('system_risk_level', 'high');
              });
        });
    }

    /**
     * Scope to get custom answers only
     */
    public function scopeCustomAnswers($query)
    {
        return $query->where('is_custom_answer', true);
    }

    /**
     * Scope to get non-custom answers only
     */
    public function scopeNonCustomAnswers($query)
    {
        return $query->where('is_custom_answer', false);
    }

    /**
     * Scope to get medium risk answers
     */
    public function scopeMediumRisk($query)
    {
        return $query->where(function($q) {
            $q->where('admin_risk_level', 'medium')
              ->orWhere(function($subQ) {
                  $subQ->whereNull('admin_risk_level')
                       ->where('system_risk_level', 'medium');
              });
        });
    }

    /**
     * Scope to get low risk answers
     */
    public function scopeLowRisk($query)
    {
        return $query->where(function($q) {
            $q->where('admin_risk_level', 'low')
              ->orWhere(function($subQ) {
                  $subQ->whereNull('admin_risk_level')
                       ->where('system_risk_level', 'low');
              });
        });
    }

    /**
     * Scope to filter answers requiring proof images (yes answers)
     */
    public function scopeRequiresProofImage($query)
    {
        return $query->whereRaw("LOWER(TRIM(answer)) = 'yes'")
                    ->orWhereRaw("LOWER(TRIM(selected_answer)) = 'yes'");
    }

    /**
     * Scope to filter answers with uploaded proof images
     */
    public function scopeWithProofImage($query)
    {
        return $query->whereNotNull('proof_image_path')
                    ->whereNotNull('proof_image_name');
    }

    /**
     * Scope to filter answers with validated proof images
     */
    public function scopeWithValidatedProofImage($query)
    {
        return $query->where('proof_image_validated', true);
    }

    /**
     * Scope to filter answers with invalid or missing proof images
     */
    public function scopeWithInvalidProofImage($query)
    {
        return $query->where(function($q) {
            $q->where(function($subQ) {
                    $subQ->whereRaw("LOWER(TRIM(answer)) = 'yes'")
                        ->orWhereRaw("LOWER(TRIM(selected_answer)) = 'yes'");
                })
                ->where(function($subQ) {
                    $subQ->whereNull('proof_image_path')
                        ->orWhere('proof_image_validated', false);
                });
        });
    }

    /**
     * Scope to filter answers without uploaded proof images
     */
    public function scopeWithoutProofImage($query)
    {
        return $query->whereNull('proof_image_path')
                    ->orWhereNull('proof_image_name');
    }
}