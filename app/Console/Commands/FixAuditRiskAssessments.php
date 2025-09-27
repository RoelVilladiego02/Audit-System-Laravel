<?php

namespace App\Console\Commands;

use App\Models\AuditSubmission;
use Illuminate\Console\Command;

class FixAuditRiskAssessments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:fix-risk-assessments {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Fix audit submissions with incorrect system overall risk assessments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $this->info('Scanning audit submissions for incorrect risk assessments...');

        $submissions = AuditSubmission::whereNotNull('system_overall_risk')
            ->with('answers')
            ->get();

        $fixedCount = 0;
        $totalCount = $submissions->count();

        $this->info("Found {$totalCount} submissions to check");

        foreach ($submissions as $submission) {
            $currentRisk = $submission->system_overall_risk;
            $correctRisk = $submission->calculateSystemOverallRisk();

            if ($currentRisk !== $correctRisk) {
                $this->line("Submission #{$submission->id}: {$currentRisk} → {$correctRisk}");
                
                if (!$isDryRun) {
                    $submission->recalculateSystemOverallRisk();
                }
                
                $fixedCount++;
            }
        }

        if ($fixedCount > 0) {
            if ($isDryRun) {
                $this->info("Would fix {$fixedCount} submissions");
            } else {
                $this->info("Fixed {$fixedCount} submissions");
            }
        } else {
            $this->info('No incorrect risk assessments found');
        }

        return 0;
    }
}
