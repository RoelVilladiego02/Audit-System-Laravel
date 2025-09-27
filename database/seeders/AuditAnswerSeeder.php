<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AuditAnswer;
use App\Models\AuditQuestion;
use App\Models\AuditSubmission;
use Illuminate\Database\Seeder;

class AuditAnswerSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $questions = AuditQuestion::all();
        $submissions = AuditSubmission::all();
        $riskLevels = ['low', 'medium', 'high'];

        foreach ($submissions as $submission) {
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

                // If submission is completed, add review details
                if ($submission->status === 'completed') {
                    $adminRiskLevel = $isCustomAnswer ? 'low' : $riskLevels[array_rand($riskLevels)];
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