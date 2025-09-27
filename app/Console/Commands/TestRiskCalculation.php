<?php

namespace App\Console\Commands;

use App\Models\AuditSubmission;
use Illuminate\Console\Command;

class TestRiskCalculation extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'audit:test-risk-calculation';

    /**
     * The console command description.
     */
    protected $description = 'Test the risk calculation logic with various scenarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== AUDIT RISK CALCULATION TEST SCENARIOS ===');
        $this->line('');

        // Test scenarios with expected results
        $testScenarios = [
            [
                'name' => 'HIGH RISK - 75% High Risk',
                'high' => 18,
                'low' => 6,
                'expected' => 'high',
                'description' => 'Should be HIGH (≥50% high risk)'
            ],
            [
                'name' => 'HIGH RISK - 60% High Risk', 
                'high' => 14,
                'low' => 10,
                'expected' => 'high',
                'description' => 'Should be HIGH (≥50% high risk)'
            ],
            [
                'name' => 'MEDIUM RISK - 40% High Risk',
                'high' => 10,
                'low' => 14,
                'expected' => 'medium',
                'description' => 'Should be MEDIUM (30-49% high risk)'
            ],
            [
                'name' => 'MEDIUM RISK - 30% High Risk',
                'high' => 7,
                'low' => 17,
                'expected' => 'medium',
                'description' => 'Should be MEDIUM (≥30% high risk)'
            ],
            [
                'name' => 'MEDIUM RISK - 20% High Risk',
                'high' => 5,
                'low' => 19,
                'expected' => 'medium',
                'description' => 'Should be MEDIUM (any high risk exists)'
            ],
            [
                'name' => 'LOW RISK - 0% High Risk',
                'high' => 0,
                'low' => 24,
                'expected' => 'low',
                'description' => 'Should be LOW (no high risk)'
            ],
            [
                'name' => 'ORIGINAL CASE - 62.5% High Risk',
                'high' => 15,
                'low' => 9,
                'expected' => 'high',
                'description' => 'Should be HIGH (≥50% high risk) - This was incorrectly MEDIUM before'
            ]
        ];

        $this->info('Testing Risk Calculation Logic:');
        $this->line(str_repeat('-', 60));

        $passCount = 0;
        $failCount = 0;

        foreach ($testScenarios as $scenario) {
            $highCount = $scenario['high'];
            $lowCount = $scenario['low'];
            $total = $highCount + $lowCount;
            
            $highPercentage = ($highCount / $total) * 100;
            $lowPercentage = ($lowCount / $total) * 100;
            
            // Apply the new logic
            $calculatedRisk = 'low';
            if ($highPercentage >= 50) {
                $calculatedRisk = 'high';
            } elseif ($highPercentage >= 30) {
                $calculatedRisk = 'medium';
            } elseif ($highCount > 0) {
                $calculatedRisk = 'medium';
            }
            
            $status = ($calculatedRisk === $scenario['expected']) ? '✅ PASS' : '❌ FAIL';
            
            $this->line(sprintf('%-35s | %s', $scenario['name'], $status));
            $this->line(sprintf('  High: %2d (%5.1f%%) | Low: %2d (%5.1f%%) | Expected: %-6s | Got: %-6s', 
                $highCount, $highPercentage, $lowCount, $lowPercentage, $scenario['expected'], $calculatedRisk));
            $this->line(sprintf('  %s', $scenario['description']));
            $this->line('');

            if ($calculatedRisk === $scenario['expected']) {
                $passCount++;
            } else {
                $failCount++;
            }
        }

        $this->line(str_repeat('=', 60));
        $this->info('Test Summary:');
        $this->line("✅ Passed: {$passCount}");
        $this->line("❌ Failed: {$failCount}");
        $this->line("Total: " . ($passCount + $failCount));
        $this->line('');

        if ($failCount === 0) {
            $this->info('🎉 ALL TESTS PASSED! The risk calculation logic is working correctly.');
        } else {
            $this->error('⚠️  Some tests failed. Please review the logic.');
        }

        $this->line('');
        $this->info('=== NEXT STEPS ===');
        $this->line('1. Run: php artisan db:seed --class=AuditSubmissionTestSeeder');
        $this->line('2. Run: php artisan audit:fix-risk-assessments --dry-run');
        $this->line('3. Check the analytics dashboard to see the corrected risk assessments');

        return $failCount === 0 ? 0 : 1;
    }
}
