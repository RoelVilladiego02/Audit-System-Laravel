<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_submission_id')->constrained('audit_submissions')->onDelete('cascade');
            $table->foreignId('audit_question_id')->constrained('audit_questions')->onDelete('cascade');
            $table->text('answer');
            $table->enum('system_risk_level', ['low', 'medium', 'high'])->nullable();
            $table->enum('admin_risk_level', ['low', 'medium', 'high'])->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            // TEXT columns cannot have a default value in MySQL. Make nullable and set defaults in seeders or application logic.
            $table->text('recommendation')->nullable();
            $table->enum('status', ['pending', 'reviewed'])->default('pending');
            $table->boolean('is_custom_answer')->default(false);
            $table->string('selected_answer')->nullable(); // Store what was actually selected (e.g., "Others")
            
            // Proof image fields for "yes" answer validation
            $table->string('proof_image_path')->nullable(); // Storage path to the uploaded image
            $table->string('proof_image_name')->nullable(); // Original filename provided by user
            $table->boolean('proof_image_validated')->default(false); // Whether image passed filename validation
            $table->text('proof_image_validation_error')->nullable(); // Validation error message if applicable
            
            $table->timestamps();

            // Add indexes
            $table->index('audit_submission_id'); // Index for better performance
            $table->index('is_custom_answer'); // Index for custom answer queries
            $table->index('proof_image_validated'); // Index for proof image validation queries
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_answers');
    }
};
