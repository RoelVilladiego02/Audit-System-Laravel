<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AuditSubmission;
use App\Models\AuditQuestion;
use App\Models\AuditAnswer;
use Illuminate\Database\Seeder;

class AuditSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        // Get all regular users and admin user
        $users = User::where('role', 'user')->get();
        $admin = User::where('role', 'admin')->first();
        $questions = AuditQuestion::all();
        $riskLevels = ['low', 'medium', 'high'];
        $statuses = ['draft', 'submitted', 'under_review', 'completed'];

        $auditTitles = [
            'Quarterly Security Assessment',
            'Annual Compliance Review',
            'Network Infrastructure Audit',
            'Application Security Review',
            'Data Protection Audit',
            'Cloud Security Assessment',
            'Access Control Review',
            'Security Policy Compliance Check',
            'Incident Response Assessment',
            'Third-Party Vendor Review',
            'Employee Security Training Audit',
            'Physical Security Assessment',
            'Business Continuity Plan Review'
        ];

        // Create 100 sample audit submissions
        for ($i = 0; $i < 100; $i++) {
            $createdAt = fake()->dateTimeBetween('-1 year', 'now');
            // Weight the statuses to have more completed ones
            $status = fake()->randomElement([
                'completed', 'completed', 'completed', // Higher chance for completed
                'under_review', 'under_review',        // Medium chance for under_review
                'submitted', 'draft'                   // Lower chance for others
            ]);
            
            // Only set risk levels and review data for non-draft submissions
            $systemRisk = $status !== 'draft' ? $riskLevels[array_rand($riskLevels)] : null;
            $reviewedAt = $status === 'completed' ? fake()->dateTimeBetween($createdAt, 'now') : null;
            $adminRisk = $status === 'completed' ? $riskLevels[array_rand($riskLevels)] : null;

            $submission = AuditSubmission::create([
                'user_id' => (int)$users->random()->id,
                'title' => $auditTitles[array_rand($auditTitles)] . ' - ' . fake()->date('Y-m'),
                'system_overall_risk' => $systemRisk,
                'admin_overall_risk' => $adminRisk,
                'status' => $status,
                'reviewed_by' => $status === 'completed' ? (int)$admin->id : null,
                'reviewed_at' => $reviewedAt,
                'admin_summary' => $status === 'completed' ? fake()->paragraph() : null,
                'created_at' => $createdAt,
                'updated_at' => $reviewedAt ?? $createdAt,
            ]);

            // Create answers for each of the 24 questions
            foreach ($questions as $question) {
                $possibleAnswers = is_string($question->possible_answers) 
                    ? json_decode($question->possible_answers, true) 
                    : $question->possible_answers;
                $answer = $possibleAnswers[array_rand($possibleAnswers)];
                $systemRiskLevel = $riskLevels[array_rand($riskLevels)];
                $answerStatus = $status === 'completed' ? 'reviewed' : 'pending';
                $adminRiskLevel = $status === 'completed' ? $riskLevels[array_rand($riskLevels)] : null;

                // Determine recommendation based on risk level and question
                $recommendation = ($systemRiskLevel === 'high' && !empty($question->possible_recommendation))
                    ? $question->possible_recommendation
                    : fake()->paragraph();

                AuditAnswer::create([
                    'audit_submission_id' => $submission->id,
                    'audit_question_id' => $question->id,
                    'answer' => $answer,
                    'system_risk_level' => $systemRiskLevel,
                    'admin_risk_level' => $adminRiskLevel,
                    'reviewed_by' => $status === 'completed' ? (int)$admin->id : null,
                    'reviewed_at' => $reviewedAt,
                    'admin_notes' => $status === 'completed' ? fake()->paragraph() : null,
                    'recommendation' => $recommendation,
                    'status' => $answerStatus,
                    'created_at' => $createdAt,
                    'updated_at' => $reviewedAt ?? $createdAt,
                ]);
            }
        }
    }
}