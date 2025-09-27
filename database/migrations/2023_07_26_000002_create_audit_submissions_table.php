<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('system_overall_risk', ['low', 'medium', 'high'])->nullable();
            $table->enum('admin_overall_risk', ['low', 'medium', 'high'])->nullable();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'completed'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Add indexes for better performance
            $table->index('created_at');
            $table->index('deleted_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_submissions');
    }
};