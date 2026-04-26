<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create a default questionnaire set if none exists
        $defaultSetExists = DB::table('audit_questionnaire_sets')
            ->where('name', 'Default Audit Set')
            ->exists();

        if (!$defaultSetExists) {
            // Get the first admin user
            $adminUser = DB::table('users')->where('role', 'admin')->first();
            if ($adminUser) {
                DB::table('audit_questionnaire_sets')->insert([
                    'name' => 'Default Audit Set',
                    'description' => 'Default questionnaire set',
                    'status' => 'active',
                    'created_by' => $adminUser->id,
                    'updated_by' => $adminUser->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Get the default set ID
        $defaultSetId = DB::table('audit_questionnaire_sets')
            ->where('name', 'Default Audit Set')
            ->value('id');

        // Assign all null questionnaire_set_id to default set
        if ($defaultSetId) {
            DB::table('audit_questions')
                ->whereNull('questionnaire_set_id')
                ->update(['questionnaire_set_id' => $defaultSetId]);
        }

        // Now make the column NOT NULL
        // Drop the existing nullable foreign key constraint
        DB::statement('ALTER TABLE audit_questions DROP FOREIGN KEY audit_questions_questionnaire_set_id_foreign');
        
        // Modify the column definition
        DB::statement('ALTER TABLE audit_questions MODIFY questionnaire_set_id BIGINT UNSIGNED NOT NULL');
        
        // Add the NOT NULL foreign key constraint
        DB::statement('ALTER TABLE audit_questions ADD CONSTRAINT audit_questions_questionnaire_set_id_foreign FOREIGN KEY (questionnaire_set_id) REFERENCES audit_questionnaire_sets(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the NOT NULL foreign key constraint
        DB::statement('ALTER TABLE audit_questions DROP FOREIGN KEY audit_questions_questionnaire_set_id_foreign');
        
        // Revert the column to nullable
        DB::statement('ALTER TABLE audit_questions MODIFY questionnaire_set_id BIGINT UNSIGNED NULL');
        
        // Add back the nullable foreign key constraint
        DB::statement('ALTER TABLE audit_questions ADD CONSTRAINT audit_questions_questionnaire_set_id_foreign FOREIGN KEY (questionnaire_set_id) REFERENCES audit_questionnaire_sets(id) ON DELETE SET NULL');
    }
};
