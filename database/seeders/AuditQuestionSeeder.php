<?php

namespace Database\Seeders;

use App\Models\AuditQuestion;
use Illuminate\Database\Seeder;

class AuditQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'question' => 'Has a detailed inventory of all physical devices been created?',
                'description' => 'Checks if the organization maintains a comprehensive inventory of all physical devices, including servers, workstations, and peripherals, to ensure asset management and tracking.',
                'category' => 'Inventory Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Establish an Asset Inventory Framework.\nKey Components:\n- Align with ISO 27001:2022 Annex A.8 Asset Management\n- Develop a comprehensive asset management policy\n- Define asset categories (hardware, software, data, etc.)\n- Assign responsibility for asset management\n- Implement asset tracking tools and procedures",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Are model numbers, serial numbers, and locations for future reference recorded?',
                'description' => 'Evaluates whether device details such as model numbers, serial numbers, and physical locations are documented for future reference and tracking.',
                'category' => 'Inventory Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Establish an Asset Inventory Framework.\nKey Components:\n- Align with ISO 27001:2022 Annex A.8 Asset Management\n- Develop a comprehensive asset management policy\n- Define asset categories (hardware, software, data, etc.)\n- Assign responsibility for asset management\n- Implement asset tracking tools and procedures",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have the conditions of each device been assessed, and any physical damage or wear noted?',
                'description' => 'Assesses whether the organization regularly evaluates the physical and functional condition of devices, noting any damage, wear, or operational issues.',
                'category' => 'Inventory Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Align with ISO 27002:2022 Control 7.13.\nInclude: Physical condition (scratches, dents, wear), functional status, battery health, screen condition, port and connector integrity, and internal components (if accessible).",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Has the current network setup, including configurations for routers, switches, and firewalls, been documented for configuration management?',
                'description' => 'Checks if the organization maintains up-to-date documentation of network configurations for routers, switches, and firewalls to support configuration management.',
                'category' => 'Configuration Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Establish a Network Documentation Framework.\nKey Components:\n- Align with ISO 27001:2022 Annex A 8.9 Technological Controls\n- Develop a standardized documentation policy\n- Define documentation scope and objectives\n- Assign responsibility for documentation maintenance\n- Implement version control for all documents",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Are network device configurations regularly backed up?',
                'description' => 'Evaluates whether the organization has a policy and procedures for regularly backing up network device configurations and storing them securely.',
                'category' => 'Configuration Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Establish Configuration Backup Policy.\nKey Components:\n- Align with ISO 27001:2022 Annex A 8.13 Technological Controls\n- Define backup frequency and retention\n- Specify backup storage locations\n- Establish verification procedures\n- Document recovery processes",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Do network configurations adhere to industry best practices for security and performance?',
                'description' => 'Assesses whether network configurations are regularly reviewed and updated to align with industry best practices for security and performance.',
                'category' => 'Configuration Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Network Configuration Management Framework.\nEstablish: Documented configuration standards, change management procedures, regular configuration reviews, and performance monitoring systems.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Has the current data load on the network been assessed to ensure there are no bottlenecks?',
                'description' => 'Checks if the organization conducts regular assessments of network data load to identify and resolve bottlenecks.',
                'category' => 'Configuration Management',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Baseline Performance Assessment.\nImplement: Establish baseline network performance metrics, conduct initial network load assessment, document normal traffic patterns, identify peak usage periods, and set performance benchmarks.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Has the effectiveness of network security measures, such as firewalls, intrusion detection systems, and encryption protocols, been reviewed and validated?',
                'description' => 'Assesses whether the organization regularly reviews and validates the effectiveness of network security measures, including firewalls, IDS, and encryption protocols.',
                'category' => 'Security Protocols',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Comprehensive Security Measure Review.\n- Align in ISO 27001:2022 Annex A.8.20\n- Firewall configuration audits\n- IDS/IPS effectiveness testing\n- Encryption protocol strength assessment\n- Network segmentation validation\n- Access control effectiveness",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have penetration tests been conducted to evaluate the strength of the network against potential attacks?',
                'description' => 'Evaluate penetration testing practices',
                'category' => 'Security Protocols',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Penetration Testing Program.\n- Engage certified third-party experts\n- Conduct tests quarterly\n- Focus on network, application, and physical security\n- Remediate identified vulnerabilities\n- Retest to ensure issues are resolved",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have security protocols been updated in accordance with new threats and vulnerabilities as they emerge?',
                'description' => 'Assess updates to security protocols',
                'category' => 'Security Protocols',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Threat Intelligence and Response Plan.\n- Subscribe to threat intelligence feeds\n- Review and update protocols monthly\n- Conduct bi-annual security training for staff\n- Implement an incident response plan",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have access controls been checked to ensure only authorized personnel can access sensitive data?',
                'description' => 'Evaluate access control measures for sensitive data',
                'category' => 'Access Controls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Access Control Review and Enhancement.\n- Conduct access control audits bi-annually\n- Implement role-based access controls (RBAC)\n- Enforce least privilege access\n- Review and update access controls with every role change",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have user access rights been reviewed to align with job roles and responsibilities?',
                'description' => 'Assess alignment of access rights with roles',
                'category' => 'Access Controls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "User Access Review Policy.\n- Implement quarterly access rights reviews\n- Automate role-based access assignments\n- Require manager approval for access changes\n- Conduct immediate reviews after any security incident",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have accounts of offboarded users been cleared?',
                'description' => 'Evaluate account management for offboarded users',
                'category' => 'Access Controls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Offboarding Process Enhancement.\n- Automate account disabling and deletion for offboarded users\n- Conduct exit interviews to recover assets\n- Revoke access to all systems and data immediately upon termination",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'MFA is implemented for all remote network access originating from outside the entity’s network, including all remote access by vendors and other outside parties and other sensitive systems.',
                'description' => 'Evaluate MFA implementation for remote access',
                'category' => 'Access Controls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Multi-Factor Authentication (MFA) Implementation Plan.\n- Enable MFA for all remote access points\n- Include vendor and external party logins\n- Use time-based one-time passwords (TOTPs) or authenticator apps\n- Review and update MFA methods annually",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Are access levels modifiable, and are user privileges limited to job function?',
                'description' => 'Assess modifiability of access levels and privilege limitations',
                'category' => 'Access Controls',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Access Level Management Framework.\n- Implement adjustable access levels via admin tools\n- Regularly review user privileges\n- Limit access to job-specific functions only\n- Provide training on access management for administrators",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have security measures, including antivirus, antimalware, and firewalls, been confirmed to be activated and up-to-date?',
                'description' => 'Assess activation and currency of security measures',
                'category' => 'Security Measures',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Security Software Management Policy.\n- Activate and update antivirus and antimalware solutions\n- Ensure firewalls are properly configured and active\n- Conduct monthly reviews of security software status\n- Provide user training on security awareness",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have security settings been reviewed to ensure compliance with the organization\'s security policy?',
                'description' => 'Evaluate compliance of security settings',
                'category' => 'Security Measures',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Security Settings Compliance Review.\n- Conduct bi-annual reviews of security settings\n- Use automated tools for compliance checking\n- Remediate any deviations from the security policy\n- Document and report compliance status to management",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have vulnerability scans been conducted to detect potential software security weaknesses?',
                'description' => 'Assess vulnerability scanning practices',
                'category' => 'Security Measures',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Vulnerability Management Program.\n- Conduct automated vulnerability scans monthly\n- Use tools like Nessus or Qualys for scanning\n- Prioritize and remediate vulnerabilities based on risk\n- Retest to ensure vulnerabilities are effectively addressed",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Has a review confirmed that all required documentation, such as policies, procedures, and compliance reports, is complete, up-to-date, and stored securely?',
                'description' => 'Evaluate completeness and security of documentation',
                'category' => 'Documentation Review',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Documentation Management Policy.\n- Implement a centralized documentation repository\n- Ensure all documents are reviewed and updated quarterly\n- Conduct bi-annual audits of documentation completeness\n- Provide training on documentation standards and procedures",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is documentation easily accessible to authorized personnel, especially in the event of an audit?',
                'description' => 'Assess accessibility of documentation for audits',
                'category' => 'Documentation Review',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Documentation Accessibility Enhancement.\n- Implement role-based access controls for documentation\n- Provide secure, remote access to documentation for auditors\n- Regularly test the accessibility of critical documents\n- Review and update access permissions quarterly",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Is documentation being regularly updated to reflect any changes in regulations or business operations?',
                'description' => 'Evaluate currency of documentation',
                'category' => 'Documentation Review',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Regulatory Change Management Process.\n- Monitor regulatory changes impacting the organization\n- Update documentation within one month of regulatory changes\n- Conduct training for staff on updated procedures\n- Review and test the change management process annually",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Have checks been made to verify that IT policies, including those related to data protection, acceptable use, and security, are being actively enforced?',
                'description' => 'Assess enforcement of IT policies',
                'category' => 'Policy Enforcement',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "IT Policy Enforcement Framework.\n- Implement monitoring tools for policy compliance\n- Conduct regular policy enforcement audits\n- Provide training on policy importance and compliance\n- Review and update policies annually",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Are internal audits conducted to ensure adherence to these policies?',
                'description' => 'Evaluate internal audit practices for policy adherence',
                'category' => 'Policy Enforcement',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Internal Audit Program.\n- Schedule bi-annual internal audits\n- Use automated tools for audit trails and reporting\n- Ensure audits cover all critical areas of policy adherence\n- Provide management with audit findings and recommendations",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question' => 'Are regular policy training and updates being provided for the team?',
                'description' => 'Assess provision of policy training and updates',
                'category' => 'Policy Enforcement',
                'possible_answers' => ['Yes', 'No'],
                'risk_criteria' => [
                    'high' => ['No'],
                    'low' => ['Yes']
                ],
                'possible_recommendation' => "Policy Training and Awareness Program.\n- Provide annual policy training for all employees\n- Use interactive methods like workshops and e-learning\n- Test knowledge retention with follow-up assessments\n- Update training materials based on policy changes",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Clear existing questions first (optional - remove if you want to keep existing data)
        // AuditQuestion::truncate();

        foreach ($questions as $questionData) {
            // Remove 'suggestions' key if present
            unset($questionData['suggestions']);
            AuditQuestion::create($questionData);
        }
    }
}