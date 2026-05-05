<?php

/**
 * Proof Image Filename Whitelist/Blacklist Configuration
 * 
 * This file controls which filenames are considered valid or invalid
 * for proof images uploaded with "Yes" answers in audit questionnaires.
 * 
 * The validation system uses this configuration to determine if an image
 * filename is acceptable based on whitelist/blacklist patterns.
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Validation Mode
    |--------------------------------------------------------------------------
    |
    | Set the validation mode:
    | - 'whitelist': Only allow filenames that match whitelist patterns
    | - 'blacklist': Allow any filename except those matching blacklist patterns
    | - 'combined': Use whitelist first, then apply blacklist to remaining
    |
    */
    'validation_mode' => env('PROOF_IMAGE_VALIDATION_MODE', 'blacklist'),

    /*
    |--------------------------------------------------------------------------
    | Whitelist: Valid Filename Patterns
    |--------------------------------------------------------------------------
    |
    | Array of regex patterns that match VALID filenames.
    | Used when validation_mode is 'whitelist' or 'combined'.
    |
    | Examples:
    |   - '/^firewall_.*\.(jpg|png|pdf)$/i' - Firewall related files
    |   - '/^(access|audit)_[a-z_]+\.(jpg|png)$/i' - Access or audit files
    |   - '/^[a-z]+_config_\d{4}.*\.(jpg|png)$/i' - Config files with date
    |
    */
    'whitelist' => [
        // Security infrastructure
        '/^firewall.*\.(jpg|jpeg|png|pdf)$/i',
        '/^(proxy|gateway).*\.(jpg|jpeg|png|pdf)$/i',
        '/^(network|vpn).*\.(jpg|jpeg|png|pdf)$/i',
        
        // Access control
        '/^(access|auth|authentication|mfa).*\.(jpg|jpeg|png|pdf)$/i',
        '/^(permission|role|rbac).*\.(jpg|jpeg|png|pdf)$/i',
        
        // Configuration & Compliance
        '/^(config|configuration|setup).*\.(jpg|jpeg|png|pdf)$/i',
        '/^(compliance|audit|certificate).*\.(jpg|jpeg|png|pdf)$/i',
        '/^(backup|restore).*\.(jpg|jpeg|png|pdf)$/i',
        
        // Security measures
        '/^(antivirus|antimalware|endpoint|protection).*\.(jpg|jpeg|png|pdf)$/i',
        '/^(scan|vulnerability|patch).*\.(jpg|jpeg|png|pdf)$/i',
        '/^(ssl|certificate|encryption).*\.(jpg|jpeg|png|pdf)$/i',
        
        // Inventory & Assets
        '/^(inventory|asset|device|hardware).*\.(jpg|jpeg|png|pdf)$/i',
        '/^(equipment|machine|server|workstation).*\.(jpg|jpeg|png|pdf)$/i',
        
        // Monitoring & Logging
        '/^(log|logging|monitor|monitoring|alert).*\.(jpg|jpeg|png|pdf)$/i',
        '/^(event|incident|report).*\.(jpg|jpeg|png|pdf)$/i',
        
        // General descriptive patterns
        '/^[a-z]{3,}[_\-][a-z]{2,}.*\.(jpg|jpeg|png|gif|bmp|webp|pdf)$/i', // word_word format
        '/^[a-z]{3,}_[a-z]{3,}_\d{2,4}.*\.(jpg|jpeg|png|pdf)$/i', // word_word_date format
    ],

    /*
    |--------------------------------------------------------------------------
    | Blacklist: Invalid Filename Patterns
    |--------------------------------------------------------------------------
    |
    | Array of regex patterns that match INVALID filenames.
    | Used when validation_mode is 'blacklist' or 'combined'.
    |
    | These patterns represent filenames that are too generic,
    | randomly generated, or placeholder-like.
    |
    */
    'blacklist' => [
        // Generic/placeholder names
        '/^(image|photo|pic|picture|file|document)[\d_\-]*\.(jpg|jpeg|png|gif|bmp|webp|pdf)$/i',
        '/^(screenshot|screen|capture|snap)[\d_\-]*\.(jpg|jpeg|png|pdf)$/i',
        '/^(test|temp|temporary|tmp|new|untitled|noname)[\d_\-]*\.(jpg|jpeg|png|pdf)$/i',
        '/^(copy|draft|backup|archive)[\d_\-]*\.(jpg|jpeg|png|pdf)$/i',
        '/^(unnamed|unknown|blank|empty).*\.(jpg|jpeg|png|pdf)$/i',
        
        // Purely numeric or random
        '/^\d+[\d_\-]*\.(jpg|jpeg|png|gif|bmp|webp|pdf)$/i',
        '/^[a-f0-9]{8,}[\d_\-]*\.(jpg|jpeg|png|pdf)$/i', // UUID-like
        '/^[a-z0-9]{16,}[\d_\-]*\.(jpg|jpeg|png|pdf)$/i', // Random hash-like
        
        // Suspicious patterns
        '/^[_\-]{2,}.*\.(jpg|jpeg|png|gif|bmp|webp|pdf)$/i',
        '/^xxx.*\.(jpg|jpeg|png|pdf)$/i',
        '/^zzz.*\.(jpg|jpeg|png|pdf)$/i',
        
        // Too short
        '/^[a-z]{1,2}\.(jpg|jpeg|png|gif|bmp|webp|pdf)$/i',
        
        // Special characters only
        '/^[_\-\.\s]+\.(jpg|jpeg|png|gif|bmp|webp|pdf)$/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum Filename Length
    |--------------------------------------------------------------------------
    |
    | Minimum length (without extension) for a valid filename.
    | Default: 3 characters
    |
    */
    'min_filename_length' => 3,

    /*
    |--------------------------------------------------------------------------
    | Required Keywords (Optional)
    |--------------------------------------------------------------------------
    |
    | If set, filenames must contain at least ONE of these keywords.
    | Set to empty array to disable.
    |
    | Examples: ['firewall', 'access', 'config', 'security', 'audit']
    |
    */
    'required_keywords' => [
        // Security infrastructure
        'firewall', 'proxy', 'gateway', 'network', 'vpn',
        
        // Access & Authentication
        'access', 'auth', 'authentication', 'mfa', 'permission', 'role', 'rbac',
        
        // Configuration
        'config', 'configuration', 'setup', 'certificate', 'ssl',
        
        // Compliance
        'compliance', 'audit', 'certificate', 'scan', 'patch',
        
        // Protection
        'antivirus', 'antimalware', 'endpoint', 'protection', 'encryption',
        
        // Monitoring
        'log', 'logging', 'monitor', 'monitoring', 'alert', 'event',
        
        // Inventory
        'inventory', 'asset', 'device', 'hardware', 'equipment',
    ],

    /*
    |--------------------------------------------------------------------------
    | Use Required Keywords
    |--------------------------------------------------------------------------
    |
    | Enable/disable keyword validation.
    | When enabled, filenames must contain at least one keyword from the list.
    |
    */
    'use_required_keywords' => false, // Set to true to require keywords

    /*
    |--------------------------------------------------------------------------
    | Valid File Extensions
    |--------------------------------------------------------------------------
    |
    | Array of allowed file extensions (without dot).
    |
    */
    'allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'bmp',
        'webp',
        'pdf',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size (in KB)
    |--------------------------------------------------------------------------
    |
    */
    'max_file_size_kb' => 10240, // 10 MB

    /*
    |--------------------------------------------------------------------------
    | Custom Error Messages
    |--------------------------------------------------------------------------
    |
    | Customize the validation error messages returned to users.
    |
    */
    'messages' => [
        'empty_filename' => 'Image filename cannot be empty.',
        'too_short' => 'Image filename is too short. Use descriptive names (e.g., "firewall_config", "access_log_screenshot").',
        'generic_name' => 'Image filename appears to be too generic or a placeholder. Please use a descriptive name that relates to the audit answer.',
        'random_name' => 'Image filename appears to be randomly generated. Use descriptive names instead.',
        'invalid_extension' => 'Invalid file extension. Allowed formats: :extensions',
        'file_too_large' => 'File size exceeds maximum of :max_size KB.',
        'whitelist_fail' => 'Image filename does not match required naming patterns. Use descriptive names like "firewall_config", "access_control_screenshot", or "security_audit_2026".',
        'success' => 'Image filename is valid and descriptive.',
    ],

];
