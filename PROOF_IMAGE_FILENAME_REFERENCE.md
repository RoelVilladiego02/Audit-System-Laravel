# Proof Image Filename Validation Reference

**File**: `config/proof_images.php`

This document serves as a quick reference for managing valid and invalid filenames for proof image uploads.

## Quick Configuration Summary

The validation system is configured in **`config/proof_images.php`** with three main approaches:

### Validation Modes

1. **`'blacklist'` (Default)**: Accept anything EXCEPT patterns in blacklist
2. **`'whitelist'`**: Only accept patterns in whitelist
3. **`'combined'`**: Try whitelist first, then apply blacklist to remaining

---

## ✅ VALID FILENAME PATTERNS (Whitelist)

These patterns are ALLOWED:

### Security Infrastructure
```
firewall_config.jpg
firewall_rules.png
proxy_settings.pdf
gateway_configuration.jpg
network_diagram.png
vpn_setup.jpg
```

### Access Control & Authentication
```
access_control_log.jpg
authentication_settings.png
mfa_configuration.pdf
permission_matrix.jpg
role_based_access.png
rbac_setup.jpg
```

### Configuration & Compliance
```
config_backup_2026.jpg
ssl_certificate.pdf
compliance_audit.jpg
backup_verification.png
```

### Security Measures
```
antivirus_status.jpg
endpoint_protection.png
vulnerability_scan.pdf
security_patch_log.jpg
encryption_certificate.pdf
```

### Inventory & Assets
```
inventory_list_2026.jpg
asset_tracking.pdf
device_registry.jpg
hardware_audit.png
```

### Monitoring & Logging
```
log_monitoring_dashboard.jpg
event_viewer_screenshot.png
alert_configuration.pdf
incident_report.jpg
```

### General Patterns (Word_Word or Word_Word_Date)
```
firewall_backup.jpg
access_audit.png
security_report_2026.jpg
compliance_check_05_2026.pdf
```

---

## ❌ INVALID FILENAME PATTERNS (Blacklist)

These patterns are REJECTED:

### Generic/Placeholder Names
```
image.jpg              ❌ Too generic
photo.jpg              ❌ Too generic
pic.jpg                ❌ Too generic
picture.png            ❌ Too generic
file.pdf               ❌ Too generic
document.jpg           ❌ Too generic
screenshot.png         ❌ Generic placeholder
screen.jpg             ❌ Generic placeholder
capture.png            ❌ Generic placeholder
```

### Temporary/Test Files
```
test.jpg               ❌ Placeholder
temp.jpg               ❌ Temporary
temporary.pdf          ❌ Temporary
tmp.png                ❌ Temporary
new.jpg                ❌ Placeholder
untitled.jpg           ❌ Placeholder
noname.pdf             ❌ Placeholder
```

### Copied/Archived Files
```
copy.jpg               ❌ Generic action
draft.jpg              ❌ Work in progress
backup.jpg             ❌ Generic
archive.pdf            ❌ Generic
unnamed.jpg            ❌ Placeholder
unknown.png            ❌ Unclear
blank.jpg              ❌ Placeholder
empty.pdf              ❌ Placeholder
```

### Random/Numerical Names
```
123456.jpg             ❌ Purely numeric
987654.png             ❌ Purely numeric
image123.jpg           ❌ Generic + number
photo456.jpg           ❌ Generic + number
file789.pdf            ❌ Generic + number
a1b2c3d4e5.jpg         ❌ Random hex
abcdef123456.png       ❌ Random hash-like
```

### Suspicious Names
```
xxx.jpg                ❌ Suspicious
zzz.pdf                ❌ Suspicious
___.jpg                ❌ Only special chars
---.png                ❌ Only special chars
```

### Too Short
```
a.jpg                  ❌ Too short (1 char)
ab.png                 ❌ Too short (2 chars)
xy.pdf                 ❌ Too short (2 chars)
```

---

## 📋 HOW TO CUSTOMIZE

### To Add a Valid Filename Pattern

1. Open **`config/proof_images.php`**
2. Locate the `'whitelist'` array (around line 30)
3. Add a new regex pattern:

```php
'whitelist' => [
    // ... existing patterns ...
    '/^my_new_pattern.*\.(jpg|png|pdf)$/i',  // Add your pattern here
],
```

### To Add an Invalid Filename Pattern

1. Open **`config/proof_images.php`**
2. Locate the `'blacklist'` array (around line 60)
3. Add a new regex pattern:

```php
'blacklist' => [
    // ... existing patterns ...
    '/^another_bad_pattern.*\.(jpg|png)$/i',  // Add your pattern here
],
```

### To Require Specific Keywords

1. Open **`config/proof_images.php`**
2. Change this line (around line 105):

```php
'use_required_keywords' => true,  // Change from false to true
```

3. Update keywords array if needed (around line 97):

```php
'required_keywords' => [
    'firewall', 'access', 'config', // Add/remove keywords
    'audit', 'security',
],
```

---

## 🔧 REGEX PATTERN GUIDE

Common regex patterns for filename validation:

### Basic Patterns
```php
'/^firewall.*\.(jpg|png)$/i'           // Starts with "firewall", .jpg or .png
'/^[a-z]+_[a-z]+\.(jpg|png)$/i'        // word_word format only
'/^[a-z]{3,}[_\-][a-z]{2,}.*\.(jpg|pdf)$/i'  // 3+ chars, separator, 2+ chars
```

### With Date
```php
'/^[a-z_]+_\d{4}\.(jpg|png)$/i'        // word_year.ext
'/^[a-z_]+_\d{2}_\d{4}\.(jpg|png)$/i'  // word_month_year.ext
```

### Reject Patterns
```php
'/^[\d_]+\.(jpg|png)$/i'               // Only numbers/underscores
'/^[_\-]{2,}.*\.(jpg)$/i'              // Multiple special chars at start
'/^(image|photo|file)[\d]*\.(jpg)$/i'  // Generic names with numbers
```

---

## 🚀 SWITCHING VALIDATION MODES

In `config/proof_images.php`, update:

```php
'validation_mode' => env('PROOF_IMAGE_VALIDATION_MODE', 'blacklist'),
```

### Option 1: More Restrictive (Whitelist)
Set in `.env`:
```
PROOF_IMAGE_VALIDATION_MODE=whitelist
```
**Effect**: Only filenames matching whitelist patterns are accepted

### Option 2: Permissive (Blacklist - Default)
Set in `.env`:
```
PROOF_IMAGE_VALIDATION_MODE=blacklist
```
**Effect**: Any filename except blacklisted patterns are accepted

### Option 3: Balanced (Combined)
Set in `.env`:
```
PROOF_IMAGE_VALIDATION_MODE=combined
```
**Effect**: Check whitelist first, then blacklist on remaining

---

## 📝 EXAMPLES OF ADDING NEW PATTERNS

### Example 1: Add Support for GDPR Audit Files
```php
'whitelist' => [
    // ... existing ...
    '/^(gdpr|privacy|data_protection)_.*\.(jpg|png|pdf)$/i',
],
```

### Example 2: Add Support for Specific Department
```php
'whitelist' => [
    // ... existing ...
    '/^(accounting|finance|hr)_.*\.(jpg|png|pdf)$/i',
],
```

### Example 3: Reject Files with Special Names
```php
'blacklist' => [
    // ... existing ...
    '/^confidential.*\.(jpg|png)$/i',  // Too sensitive
    '/^admin_only.*\.(jpg|pdf)$/i',     // Restricted access
],
```

---

## ✨ TESTING YOUR PATTERNS

Test regex patterns online:
- https://regex101.com
- https://regexper.com

Example test strings:
```
✅ firewall_config.jpg
✅ access_control_2026.png
✅ network_audit_report.pdf
❌ image.jpg
❌ photo123.png
❌ test_file.pdf
```

---

## 🔐 SECURITY NOTES

1. **Regex Patterns**: Patterns use case-insensitive matching (`/i` flag)
2. **File Extensions**: Always validate in addition to filenames
3. **File Size**: Configured to max 10 MB (see `max_file_size_kb`)
4. **Storage**: Files stored in `storage/app/public/proof-images/{year}/{month}/{day}/{answer_id}/`

---

## 📊 VALIDATION FLOW

```
File Upload
    ↓
[File Size Check] ← max_file_size_kb (10 MB)
    ↓
[Extension Check] ← allowed_extensions (jpg, png, pdf, etc.)
    ↓
[Filename Length] ← min_filename_length (3 chars)
    ↓
[Validation Mode]
    ├─ whitelist: Check if matches whitelist patterns
    ├─ blacklist: Check if matches blacklist patterns
    └─ combined: Try whitelist, then blacklist
    ↓
[Keywords Check] ← use_required_keywords (optional)
    ↓
✅ VALID or ❌ INVALID
```

---

## 🆘 TROUBLESHOOTING

### All files are being rejected
1. Check `validation_mode` is set correctly
2. Verify regex patterns are valid (test on regex101.com)
3. Check file extensions are in `allowed_extensions`
4. Review logs in `storage/logs/laravel.log`

### Files I want to reject are passing
1. Ensure patterns are in `blacklist` array
2. Check if using `whitelist` mode (only whitelist is checked)
3. Verify pattern syntax with `i` flag for case-insensitive

### Can't add new patterns
1. Ensure regex is properly escaped
2. Use `/i` flag for case-insensitive matching
3. Test pattern before adding: https://regex101.com

