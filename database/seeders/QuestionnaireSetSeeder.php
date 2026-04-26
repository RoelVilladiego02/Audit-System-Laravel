<?php

namespace Database\Seeders;

use App\Models\AuditQuestionnaireSet;
use App\Models\AuditQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuestionnaireSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create admin user for creator
        $admin = User::where('role', 'admin')->first() ?? User::first();
        
        if (!$admin) {
            $this->command->warn('No users found in database. Please run UserSeeder first.');
            return;
        }

        // Create default questionnaire set with all existing questions
        $defaultSet = AuditQuestionnaireSet::create([
            'name' => 'Default Audit Set',
            'description' => 'Comprehensive audit questionnaire covering inventory management, configuration management, security protocols, and access controls.',
            'status' => 'active',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        // Associate all active questions with the default set
        $questionsUpdated = AuditQuestion::where('questionnaire_set_id', null)
            ->whereNull('deleted_at')
            ->update(['questionnaire_set_id' => $defaultSet->id]);

        $this->command->info("Created 'Default Audit Set' with {$questionsUpdated} questions.");

        // Create additional questionnaire sets by category
        $categories = AuditQuestion::where('questionnaire_set_id', $defaultSet->id)
            ->distinct('category')
            ->pluck('category');

        foreach ($categories as $category) {
            $categorySet = AuditQuestionnaireSet::create([
                'name' => "{$category} Questionnaire",
                'description' => "Focused audit questionnaire covering {$category} topics.",
                'status' => 'draft',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            $catQuestionCount = AuditQuestion::where('category', $category)
                ->where('questionnaire_set_id', $defaultSet->id)
                ->count();

            $this->command->info("Created '{$category} Questionnaire' with {$catQuestionCount} questions (in draft status).");
        }
    }
}
