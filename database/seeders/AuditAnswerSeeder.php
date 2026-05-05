<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AuditAnswer;
use App\Models\AuditQuestion;
use App\Models\AuditSubmission;
use Illuminate\Database\Seeder;

class AuditAnswerSeeder extends Seeder
{
    /**
     * Array of valid proof image filenames for seeding "Yes" answers
     * These follow the validation rules from config/proof_images.php
     */
    protected array $sampleProofImages = [
        'firewall_config_2026.jpg',
        'access_control_audit.png',
        'mfa_configuration.pdf',
        'network_backup.jpg',
        'security_certificate.pdf',
        'vulnerability_scan_report.jpg',
        'antivirus_status_log.png',
        'backup_verification.jpg',
        'inventory_audit_2026.pdf',
        'compliance_checklist.jpg',
        'ssl_certificate_config.jpg',
        'access_control_matrix.pdf',
        'patch_management_log.jpg',
        'encryption_audit.png',
        'firewall_rules_backup.pdf',
        'device_inventory_2026.jpg',
        'audit_trail_screenshot.png',
        'security_audit_report.pdf',
        'monitoring_dashboard.jpg',
        'incident_log_audit.jpg',
    ];

    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $submissions = AuditSubmission::with('questionnaireSet.questions')->get();
        $riskLevels = ['low', 'medium', 'high'];

        foreach ($submissions as $submission) {
            // Get only questions that belong to this submission's questionnaire set
            $questions = $submission->questionnaireSet->questions;
            
            if ($questions->isEmpty()) {
                continue;
            }

            foreach ($questions as $question) {
                $possibleAnswers = json_decode($question->possible_answers, true);
                $isCustomAnswer = false;
                $answer = $possibleAnswers[array_rand($possibleAnswers)];
                $customAnswerText = null;

                // Randomly select "Others" for questions that allow it (30% chance)
                if (in_array('Others', $possibleAnswers, true) && rand(1, 100) <= 30) {
                    $answer = 'Others';
                    $isCustomAnswer = true;
                    $customAnswerText = fake()->sentence(rand(5, 10)); // Generate random custom answer text
                }

                // Determine system risk level
                $systemRiskLevel = $isCustomAnswer ? 'low' : $riskLevels[array_rand($riskLevels)];
                // Use possible_recommendation for high risk answers
                $recommendation = ($systemRiskLevel === 'high' && !empty($question->possible_recommendation))
                    ? $question->possible_recommendation
                    : fake()->paragraph();

                // Create base answer
                $auditAnswer = AuditAnswer::create([
                    'audit_submission_id' => (int)$submission->id,
                    'audit_question_id' => (int)$question->id,
                    'answer' => $isCustomAnswer ? $customAnswerText : $answer,
                    'is_custom_answer' => $isCustomAnswer,
                    'system_risk_level' => $systemRiskLevel,
                    'status' => $submission->status === 'completed' ? 'reviewed' : 'pending',
                    'recommendation' => $recommendation,
                    'created_at' => $submission->created_at,
                    'updated_at' => $submission->created_at,
                ]);

                // Add proof image for "Yes" answers (70% chance)
                if (!$isCustomAnswer && strtolower(trim($answer)) === 'yes' && rand(1, 100) <= 70) {
                    $proofImage = $this->sampleProofImages[array_rand($this->sampleProofImages)];
                    $imagePath = 'proof-images/' . date('Y') . '/' . date('m') . '/' . date('d') . '/' . 
                                 $auditAnswer->id . '/' . $proofImage;
                    
                    $auditAnswer->update([
                        'proof_image_path' => $imagePath,
                        'proof_image_name' => $proofImage,
                        'proof_image_validated' => true, // Sample images are pre-validated
                        'proof_image_validation_error' => null,
                        'system_risk_level' => 'low' // Yes answer with valid image = low risk
                    ]);
                }

                // If submission is completed, add review details
                if ($submission->status === 'completed') {
                    $adminRiskLevel = $isCustomAnswer ? 'low' : $riskLevels[array_rand($riskLevels)];
                    
                    // Override admin risk if image was validated for "Yes" answers
                    if (!$isCustomAnswer && strtolower(trim($answer)) === 'yes' && $auditAnswer->proof_image_validated) {
                        $adminRiskLevel = 'low';
                    }
                    
                    $adminRecommendation = ($adminRiskLevel === 'high' && !empty($question->possible_recommendation))
                        ? $question->possible_recommendation
                        : fake()->paragraph();
                    $auditAnswer->update([
                        'admin_risk_level' => $adminRiskLevel,
                        'reviewed_by' => (int)$admin->id,
                        'reviewed_at' => $submission->reviewed_at,
                        'admin_notes' => fake()->paragraph(),
                        'recommendation' => $adminRecommendation,
                        'updated_at' => $submission->reviewed_at
                    ]);
                }
            }
        }
    }
}