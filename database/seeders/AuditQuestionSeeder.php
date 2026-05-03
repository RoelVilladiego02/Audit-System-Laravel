<?php

namespace Database\Seeders;

use App\Models\AuditQuestion;
use App\Models\AuditQuestionnaireSet;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditQuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create admin user
        $admin = User::where('role', 'admin')->first() ?? User::first();
        
        if (!$admin) {
            $this->command->warn('No users found in database. Please run UserSeeder first.');
            return;
        }

        // Create the 3 questionnaire sets first
        $sets = [];
        $setConfigs = [
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

        foreach ($setConfigs as $config) {
            $set = AuditQuestionnaireSet::firstOrCreate(
                ['name' => $config['name']],
                [
                    'description' => $config['description'],
                    'status' => $config['status'],
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
            $sets[$config['name']] = $set->id;
        }

        // ISO 27001 Questions (10)
        $iso27001Questions = [
            [
                'question' => 'Has a detailed inventory of all physical devices been created?',
                'description' => 'Verify if the organization maintains a comprehensive inventory of all physical devices.',
                'category' => 'Inventory Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Establish comprehensive device inventory tracking with model numbers, serial numbers, and locations.',
            ],
            [
                'question' => 'Are network device configurations regularly backed up?',
                'description' => 'Verify that network device configurations are regularly backed up and stored securely.',
                'category' => 'Configuration Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Establish configuration backup policy with defined frequency and secure storage locations.',
            ],
            [
                'question' => 'Have access controls been checked to ensure only authorized personnel can access sensitive data?',
                'description' => 'Verify that access controls are properly implemented and limit access to sensitive data.',
                'category' => 'Access Controls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Conduct access control audits bi-annually and implement role-based access controls (RBAC).',
            ],
            [
                'question' => 'Is MFA implemented for all remote network access originating from outside the entity\'s network?',
                'description' => 'Verify MFA implementation for remote access including vendors and external parties.',
                'category' => 'Access Controls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Enable MFA for all remote access points using authenticator apps or hardware tokens.',
            ],
            [
                'question' => 'Have security measures, including antivirus, antimalware, and firewalls, been confirmed to be activated and up-to-date?',
                'description' => 'Verify that all security tools are activated and kept current with latest definitions.',
                'category' => 'Security Measures',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Implement automated deployment of security software updates and conduct monthly status reviews.',
            ],
            [
                'question' => 'Have vulnerability scans been conducted to detect potential software security weaknesses?',
                'description' => 'Verify that regular vulnerability scanning is performed to identify security gaps.',
                'category' => 'Security Measures',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Conduct automated vulnerability scans monthly using tools like Nessus or Qualys.',
            ],
            [
                'question' => 'Have penetration tests been conducted to evaluate the strength of the network against potential attacks?',
                'description' => 'Verify that penetration testing is performed to validate security posture.',
                'category' => 'Security Measures',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Engage certified third-party experts for quarterly penetration testing.',
            ],
            [
                'question' => 'Are access levels modifiable, and are user privileges limited to job function?',
                'description' => 'Verify that access levels can be adjusted and follow the principle of least privilege.',
                'category' => 'Access Controls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Implement adjustable role-based access controls and conduct quarterly privilege reviews.',
            ],
            [
                'question' => 'Are regular policy training and updates being provided for the team?',
                'description' => 'Verify that security policy training is conducted regularly for all staff.',
                'category' => 'Training',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Provide annual security policy training using interactive methods like workshops and e-learning.',
            ],
            [
                'question' => 'Has the current data load on the network been assessed to ensure there are no bottlenecks?',
                'description' => 'Verify that network performance is regularly assessed to identify and resolve bottlenecks.',
                'category' => 'Configuration Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Establish baseline performance metrics and conduct quarterly network load assessments.',
            ],
        ];

        // NIST Questions (10)
        $nistQuestions = [
            [
                'question' => 'Is access to individual resources granted on a per-session basis?',
                'description' => 'NIST SP 800-207: Verify zero trust architecture with per-session access grants.',
                'category' => 'NIST SP 800-207 Zero Trust Architecture',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Implement Identity-Aware Proxy (IAP) for per-session resource access with continuous re-authentication.',
            ],
            [
                'question' => 'Are all resources monitored and in a "known state" before access is granted?',
                'description' => 'NIST SP 800-207: Verify resource health monitoring before access.',
                'category' => 'NIST SP 800-207 Zero Trust Architecture',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Implement device health checks ensuring active EDR, encryption, and current patching before access.',
            ],
            [
                'question' => 'Is there a "Freeze" process during major network changes?',
                'description' => 'NIST SP 800-53: Verify configuration change control procedures.',
                'category' => 'NIST SP 800-53 Secure Configuration & Maintenance',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Establish Change Advisory Board (CAB) to approve and document all production configuration changes.',
            ],
            [
                'question' => 'Are information system components identified and documented with their "End-of-Life" (EOL) dates?',
                'description' => 'NIST SP 800-53: Verify supply chain risk management with EOL tracking.',
                'category' => 'NIST SP 800-53 Secure Configuration & Maintenance',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Proactively plan hardware replacements before security support ends.',
            ],
            [
                'question' => 'Is split-tunneling for VPNs prohibited or strictly monitored?',
                'description' => 'NIST SP 800-171: Verify VPN boundary protection controls.',
                'category' => 'NIST SP 800-171 Boundary & Communication Protection',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Force all traffic through corporate security stack or implement highly restrictive split-tunneling policies.',
            ],
            [
                'question' => 'Are "Deny-by-Default" rules enforced at all network boundaries?',
                'description' => 'NIST SP 800-171: Verify default-deny firewall configuration.',
                'category' => 'NIST SP 800-171 Boundary & Communication Protection',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Ensure firewall rules allow specific business traffic first, then deny all remaining traffic.',
            ],
            [
                'question' => 'Is DNS filtering used to prevent connections to known malicious domains?',
                'description' => 'NIST SP 800-171: Verify DNS protection controls.',
                'category' => 'NIST SP 800-171 Boundary & Communication Protection',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Implement Protective DNS (PDNS) to block botnet C2 traffic at the infrastructure level.',
            ],
            [
                'question' => 'Have you performed a "Ruleset Review" for all Access Control Lists (ACLs)?',
                'description' => 'NIST SP 800-115: Verify ACL review procedures.',
                'category' => 'NIST SP 800-115 Technical Assessment',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Manually review router ACLs quarterly to identify shadowed rules and unnecessary access permissions.',
            ],
            [
                'question' => 'Is Multi-Factor Authentication (MFA) required for all administrative access to network devices?',
                'description' => 'Identification & Authentication: Verify MFA for admin access.',
                'category' => 'Identification & Authentication (Access Control)',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Integrate network hardware with TACACS+ or RADIUS server supporting MFA.',
            ],
            [
                'question' => 'Is access to the physical network room or data center restricted and logged?',
                'description' => 'Identification & Authentication: Verify physical access controls.',
                'category' => 'Identification & Authentication (Access Control)',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Implement badge access with CCTV and review physical access logs monthly.',
            ],
        ];

        // PCI Questions (10)
        $pciQuestions = [
            [
                'question' => 'Is there a formal process for testing and approving all network connections?',
                'description' => 'PCI DSS: Verify network connection testing and approval procedures.',
                'category' => 'Network Security & Firewalls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Maintain documented Network Diagram showing all cardholder data flows and review firewall rules bi-annually.',
            ],
            [
                'question' => 'Are "any-any" inbound/outbound rules prohibited?',
                'description' => 'PCI DSS: Verify least privilege network rules.',
                'category' => 'Network Security & Firewalls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Implement Least Privilege: Deny all by default and only allow specifically required protocols.',
            ],
            [
                'question' => 'Is the Primary Account Number (PAN) rendered unreadable wherever it is stored?',
                'description' => 'PCI DSS: Verify data protection for cardholder information.',
                'category' => 'Protection of Stored Account Data',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Use AES-256 encryption, hashing, or tokenization. Never store sensitive authentication data after authorization.',
            ],
            [
                'question' => 'Is there a documented data retention policy?',
                'description' => 'PCI DSS: Verify data retention and purging procedures.',
                'category' => 'Protection of Stored Account Data',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Purge unnecessary data using automated secure deletion tools for data exceeding retention period.',
            ],
            [
                'question' => 'Are critical security patches installed within 30 days of release?',
                'description' => 'PCI DSS: Verify patch management timelines.',
                'category' => 'Vulnerability Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Establish patch management hierarchy prioritizing Critical and High vulnerabilities with automated scanning.',
            ],
            [
                'question' => 'How are custom software applications protected against common attacks (e.g., SQL injection)?',
                'description' => 'PCI DSS: Verify application security controls.',
                'category' => 'Vulnerability Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Integrate security into SDLC, implement Web Application Firewalls (WAF), and conduct annual penetration testing.',
            ],
            [
                'question' => 'Are unique IDs assigned to every person with computer access?',
                'description' => 'PCI DSS: Verify unique user account requirements.',
                'category' => 'Access Control & Identity Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Never use shared accounts. Disable accounts immediately upon employee termination.',
            ],
            [
                'question' => 'Is Multi-Factor Authentication (MFA) implemented for all access into the Cardholder Data Environment (CDE)?',
                'description' => 'PCI DSS v4.0: Mandatory MFA for CDE access.',
                'category' => 'Monitoring and Testing',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Implement phishing-resistant MFA for all CDE access, not just remote access.',
            ],
            [
                'question' => 'Are logs reviewed daily for security events?',
                'description' => 'PCI DSS: Verify logging and monitoring procedures.',
                'category' => 'Monitoring and Testing',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Use SIEM tools for automated log collection and alerting instead of manual review.',
            ],
            [
                'question' => 'Are internal and external vulnerability scans performed quarterly?',
                'description' => 'PCI DSS: Verify regular vulnerability assessment practices.',
                'category' => 'Monitoring and Testing',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => ['high' => ['No'], 'low' => ['Yes']],
                'possible_recommendation' => 'Use ASV (Approved Scanning Vendor) for external scans and ensure Passing scan every 90 days.',
            ],
        ];

        // Store all question sets
        $questionSets = [
            'ISO 27001' => $iso27001Questions,
            'NIST' => $nistQuestions,
            'PCI' => $pciQuestions,
        ];

        // Seed questions and assign to their respective sets
        foreach ($questionSets as $setName => $questions) {
            $setId = $sets[$setName];
            
            foreach ($questions as $questionData) {
                // Set the questionnaire_set_id for this question
                $questionData['questionnaire_set_id'] = $setId;
                
                AuditQuestion::updateOrCreate(
                    ['question' => $questionData['question']],
                    $questionData
                );
            }
        }

        $this->command->info('30 audit questions seeded successfully across 3 questionnaire sets.');
    }
}
