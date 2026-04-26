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
     * This seeder should run AFTER AuditQuestionSeeder to migrate existing questions to sets.
     */
    public function run(): void
    {
        // Get or create admin user for creator
        $admin = User::where('role', 'admin')->first() ?? User::first();
        
        if (!$admin) {
            $this->command->warn('No users found in database. Please run UserSeeder first.');
            return;
        }

        // Create default questionnaire set if not exists
        $defaultSet = AuditQuestionnaireSet::firstOrCreate(
            ['name' => 'Default Audit Set'],
            [
                'description' => 'Comprehensive audit questionnaire covering inventory management, configuration management, security protocols, and access controls.',
                'status' => 'active',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        // Migrate all existing questions without a set to the default set
        $migratedCount = AuditQuestion::whereNull('questionnaire_set_id')
            ->whereNull('deleted_at')
            ->update(['questionnaire_set_id' => $defaultSet->id]);

        if ($migratedCount > 0) {
            $this->command->info("Migrated {$migratedCount} existing questions to 'Default Audit Set'.");
        }

        $questionCount = $defaultSet->questions()->count();
        $this->command->info("Default Audit Set now contains {$questionCount} questions.");

        // Create additional questionnaire sets by category (draft status for admin to activate)
        $categories = AuditQuestion::where('questionnaire_set_id', $defaultSet->id)
            ->distinct('category')
            ->pluck('category');

        foreach ($categories as $category) {
            $categorySet = AuditQuestionnaireSet::firstOrCreate(
                ['name' => "{$category} Questionnaire"],
                [
                    'description' => "Focused audit questionnaire covering {$category} topics. Duplicate from Default Audit Set.",
                    'status' => 'draft',
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );

            // Don't actually associate questions - these are templates
            // Admins will manage these sets manually
            $this->command->info("Created '{$category} Questionnaire' template (draft status).");
        }

        $this->command->info('Questionnaire sets initialization complete!');
    }
}
