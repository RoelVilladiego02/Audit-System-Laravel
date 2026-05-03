<?php

namespace Database\Seeders;

use App\Models\AuditQuestionnaireSet;
use App\Models\AuditQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class QuestionnaireSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder should run AFTER AuditQuestionSeeder to assign questions to sets.
     */
    public function run(): void
    {
        // Get or create admin user for creator
        $admin = User::where('role', 'admin')->first() ?? User::first();
        
        if (!$admin) {
            $this->command->warn('No users found in database. Please run UserSeeder first.');
            return;
        }

        // Get question sets from cache (populated by AuditQuestionSeeder)
        $questionSets = Cache::get('audit_question_sets', [
            'ISO 27001' => [],
            'NIST' => [],
            'PCI' => [],
        ]);

        // Create the 3 questionnaire sets
        $sets = [
            [
                'name' => 'ISO 27001',
                'description' => 'Comprehensive audit questionnaire covering ISO 27001 security controls including inventory management, configuration management, security measures, and access controls.',
                'status' => 'active',
            ],
            [
                'name' => 'NIST',
                'description' => 'Audit questionnaire based on NIST frameworks including NIST SP 800-207 (Zero Trust Architecture), NIST SP 800-53 (Security Controls), NIST SP 800-171 (Cybersecurity), and NIST SP 800-115 (Technical Assessment).',
                'status' => 'active',
            ],
            [
                'name' => 'PCI',
                'description' => 'Audit questionnaire for PCI DSS compliance covering network security, data protection, vulnerability management, access control, and monitoring requirements.',
                'status' => 'active',
            ],
        ];

        // Create/update each questionnaire set and assign questions
        foreach ($sets as $setData) {
            $set = AuditQuestionnaireSet::updateOrCreate(
                ['name' => $setData['name']],
                [
                    'description' => $setData['description'],
                    'status' => $setData['status'],
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );

            // Get questions for this set
            $questionsForSet = $questionSets[$setData['name']] ?? [];
            
            if (!empty($questionsForSet)) {
                // Find question IDs and associate with this set
                foreach ($questionsForSet as $questionData) {
                    $question = AuditQuestion::where('question', $questionData['question'])->first();
                    if ($question) {
                        $question->update(['questionnaire_set_id' => $set->id]);
                    }
                }
            } else {
                // If no cached questions, try to find by pattern or category
                $this->assignQuestionsByName($set, $setData['name']);
            }

            $questionCount = $set->questions()->count();
            $this->command->info("{$setData['name']} set created with {$questionCount} questions.");
        }

        $this->command->info('Questionnaire sets initialization complete!');
    }

    /**
     * Assign questions to sets by matching question text patterns
     */
    private function assignQuestionsByName(AuditQuestionnaireSet $set, string $setName): void
    {
        $iso27001Questions = [
            'Has a detailed inventory of all physical devices been created?',
            'Are network device configurations regularly backed up?',
            'Have access controls been checked to ensure only authorized personnel can access sensitive data?',
            'Is MFA implemented for all remote network access originating from outside the entity\'s network?',
            'Have security measures, including antivirus, antimalware, and firewalls, been confirmed to be activated and up-to-date?',
            'Have vulnerability scans been conducted to detect potential software security weaknesses?',
            'Have penetration tests been conducted to evaluate the strength of the network against potential attacks?',
            'Are access levels modifiable, and are user privileges limited to job function?',
            'Are regular policy training and updates being provided for the team?',
            'Has the current data load on the network been assessed to ensure there are no bottlenecks?',
        ];

        $nistQuestions = [
            'Is access to individual resources granted on a per-session basis?',
            'Are all resources monitored and in a "known state" before access is granted?',
            'Is there a "Freeze" process during major network changes?',
            'Are information system components identified and documented with their "End-of-Life" (EOL) dates?',
            'Is split-tunneling for VPNs prohibited or strictly monitored?',
            'Are "Deny-by-Default" rules enforced at all network boundaries?',
            'Is DNS filtering used to prevent connections to known malicious domains?',
            'Have you performed a "Ruleset Review" for all Access Control Lists (ACLs)?',
            'Is Multi-Factor Authentication (MFA) required for all administrative access to network devices?',
            'Is access to the physical network room or data center restricted and logged?',
        ];

        $pciQuestions = [
            'Is there a formal process for testing and approving all network connections?',
            'Are "any-any" inbound/outbound rules prohibited?',
            'Is the Primary Account Number (PAN) rendered unreadable wherever it is stored?',
            'Is there a documented data retention policy?',
            'Are critical security patches installed within 30 days of release?',
            'How are custom software applications protected against common attacks (e.g., SQL injection)?',
            'Are unique IDs assigned to every person with computer access?',
            'Is Multi-Factor Authentication (MFA) implemented for all access into the Cardholder Data Environment (CDE)?',
            'Are logs reviewed daily for security events?',
            'Are internal and external vulnerability scans performed quarterly?',
        ];

        $questionTexts = [];
        if ($setName === 'ISO 27001') {
            $questionTexts = $iso27001Questions;
        } elseif ($setName === 'NIST') {
            $questionTexts = $nistQuestions;
        } elseif ($setName === 'PCI') {
            $questionTexts = $pciQuestions;
        }

        // Update questions with this set ID
        AuditQuestion::whereIn('question', $questionTexts)
            ->update(['questionnaire_set_id' => $set->id]);
    }
}
