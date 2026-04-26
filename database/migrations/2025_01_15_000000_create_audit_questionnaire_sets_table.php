<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_questionnaire_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index('status');
            $table->index('created_at');
            $table->index('deleted_at');
        });

        // Add questionnaire_set_id to audit_questions table
        Schema::table('audit_questions', function (Blueprint $table) {
            $table->foreignId('questionnaire_set_id')->nullable()->constrained('audit_questionnaire_sets')->nullOnDelete();
            $table->index('questionnaire_set_id');
        });

        // Add questionnaire_set_id to audit_submissions table
        Schema::table('audit_submissions', function (Blueprint $table) {
            $table->foreignId('questionnaire_set_id')->nullable()->constrained('audit_questionnaire_sets')->nullOnDelete();
            $table->index('questionnaire_set_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_submissions', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['questionnaire_set_id']);
            $table->dropIndex(['questionnaire_set_id']);
            $table->dropColumn('questionnaire_set_id');
        });

        Schema::table('audit_questions', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['questionnaire_set_id']);
            $table->dropIndex(['questionnaire_set_id']);
            $table->dropColumn('questionnaire_set_id');
        });

        Schema::dropIfExists('audit_questionnaire_sets');
    }
};
