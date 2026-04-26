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
        Schema::table('audit_questions', function (Blueprint $table) {
            $table->foreignId('questionnaire_set_id')
                ->change()
                ->constrained('audit_questionnaire_sets')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_questions', function (Blueprint $table) {
            $table->foreignId('questionnaire_set_id')
                ->nullable()
                ->change()
                ->constrained('audit_questionnaire_sets')
                ->nullOnDelete();
        });
    }
};
