# Proof Image Upload Feature Implementation

## Overview
This document describes the implementation of proof image upload and validation for "yes" answers in audit questionnaires. Images are validated based on their filenames to ensure they are descriptive and meaningful.

## Features

### 1. Proof Image Requirements
- **For "Yes" Answers**: An image upload is REQUIRED
  - Image must be uploaded before the answer can be considered valid
  - Image filename must be descriptive (not generic/placeholder names)
  - Valid image passes validation: answer is marked as low risk
  - Invalid or missing image: answer is marked as high risk

- **For "No" Answers**: No image is required
  - These answers continue to be marked as high risk (existing behavior)
  - Users can optionally upload images as additional documentation

### 2. Image Validation Rules

Images are validated based on their **filename** (staged AI analysis approach):

#### Valid Filenames:
- ✅ `firewall_config.jpg`
- ✅ `access_control_screenshot.png`
- ✅ `security_certificate_2026.pdf`
- ✅ `compliance_audit_results.jpg`
- ✅ `firewall_rules_configuration.png`

#### Invalid Filenames:
- ❌ `image.jpg` (too generic)
- ❌ `photo123.jpg` (random/placeholder)
- ❌ `test.jpg` (placeholder)
- ❌ `1234567.jpg` (purely numeric)
- ❌ `screenshot.png` (generic placeholder)
- ❌ `unnamed.pdf` (unclear purpose)

### 3. Supported File Formats
- jpg, jpeg, png, gif, bmp, webp, pdf

### 4. File Size Limit
- Maximum: 10 MB per image

## Database Schema

### New Migration: `2026_05_05_000001_add_proof_images_to_audit_answers_table.php`

Added columns to `audit_answers` table:
```
- proof_image_path (nullable string) - Storage path to the uploaded image
- proof_image_name (nullable string) - Original filename provided by user
- proof_image_validated (boolean) - Whether image passed validation (default: false)
- proof_image_validation_error (nullable text) - Validation error message if applicable
```

## Model Updates

### AuditAnswer Model

#### New Methods:

**Core Methods:**
- `requiresProofImage(): bool` - Check if answer is "yes"
- `hasProofImage(): bool` - Check if image has been uploaded
- `isProofImageMissing(): bool` - Check if required image is missing
- `isAnswerValid(): bool` - Check if answer is valid considering image requirements

**Image Validation:**
- `validateProofImageName(string $filename): array` - Validate filename
  - Returns: `['valid' => bool, 'message' => string]`
- `storeProofImage(string $imagePath, string $imageName): bool` - Store image with validation
- `getProofImageValidationStatus(): string` - Get human-readable validation status

#### New Scopes (for querying):
- `requiresProofImage()` - Filter "yes" answers
- `withProofImage()` - Filter answers with uploaded images
- `withValidatedProofImage()` - Filter answers with validated images
- `withInvalidProofImage()` - Filter answers with invalid/missing images
- `withoutProofImage()` - Filter answers without images

#### Updated Method:
- `calculateSystemRiskLevel(): string` - Now considers proof image validation
  - "Yes" with valid image → low risk
  - "Yes" without valid image → high risk
  - "No" answers → unchanged (high risk)

## Service: ProofImageService

Located at: `app/Services/ProofImageService.php`

### Key Methods:

```php
// Upload and validate an image
$result = $imageService->uploadProofImage($answer, $uploadedFile);
// Returns: ['success' => bool, 'message' => string, 'data' => array|null]

// Delete an image
$imageService->deleteProofImage($answer);

// Get public URL for an image
$url = $imageService->getProofImageUrl($answer);

// Check if image exists in storage
$exists = $imageService->proofImageExists($answer);

// Re-validate all images in a submission
$stats = $imageService->revalidateSubmissionImages($submissionId);

// Get statistics about images in a submission
$stats = $imageService->getSubmissionImageStats($submissionId);
```

## Usage Examples

### Example 1: Upload Image in Controller

```php
<?php

use App\Models\AuditAnswer;
use App\Services\ProofImageService;

class AuditAnswerController
{
    protected ProofImageService $imageService;

    public function __construct(ProofImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function uploadProof(Request $request, $answerId)
    {
        $request->validate([
            'proof_image' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240'
        ]);

        $answer = AuditAnswer::findOrFail($answerId);

        $result = $this->imageService->uploadProofImage(
            $answer,
            $request->file('proof_image')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }
}
```

### Example 2: Validate Before Saving

```php
<?php

$answer = AuditAnswer::find($answerId);

// Check if image is required and valid
if ($answer->requiresProofImage() && !$answer->isAnswerValid()) {
    echo "This 'yes' answer requires a validated proof image.";
    echo "Error: " . $answer->getProofImageValidationStatus();
}
```

### Example 3: Query Answers by Image Status

```php
<?php

use App\Models\AuditAnswer;

// Get all yes answers that need images
$yesAnswersNeedingImages = AuditAnswer::requiresProofImage()->get();

// Get yes answers with validated images
$validAnswers = AuditAnswer::withValidatedProofImage()->get();

// Get yes answers without images
$missingImages = AuditAnswer::requiresProofImage()
    ->withoutProofImage()
    ->get();

// Get yes answers with invalid images
$invalidImages = AuditAnswer::withInvalidProofImage()->get();
```

### Example 4: Get Submission Statistics

```php
<?php

use App\Services\ProofImageService;

$imageService = app(ProofImageService::class);
$stats = $imageService->getSubmissionImageStats($submissionId);

echo "Total answers: " . $stats['total_answers'];
echo "Yes answers: " . $stats['yes_answers'];
echo "Images uploaded: " . $stats['answers_with_images'];
echo "Images validated: " . $stats['validated_images'];
echo "Completion: " . $stats['completion_percentage'] . "%";
```

## Risk Level Calculation Flow

```
┌─ Answer = "Yes"?
│  ├─ YES → Check for image
│  │   ├─ Image exists?
│  │   │   ├─ YES → Validate image name
│  │   │   │   ├─ Valid → LOW RISK ✓
│  │   │   │   └─ Invalid → HIGH RISK ✗
│  │   │   └─ NO → HIGH RISK ✗
│  │   
│  └─ NO → Use standard risk criteria → Can be Low/Medium/High
│
└─ Answer = "No"?
   └─ Uses standard risk criteria (typically High Risk)
```

## Validation Error Messages

The validation error messages guide users to provide appropriate filenames:

```
❌ "Image filename cannot be empty."

❌ "Image filename is too short. Use descriptive names (e.g., "firewall_config", "access_log_screenshot")."

❌ "Image filename appears to be randomly generated. Use descriptive names instead."

❌ "Image filename "screenshot.jpg" appears to be generic or placeholder. Please use a descriptive name that relates to the audit answer."

❌ "Invalid file extension. Allowed formats: jpg, jpeg, png, gif, bmp, webp, pdf"
```

## File Storage Structure

Images are organized by date and answer ID:

```
storage/app/public/proof-images/
├── 2026/
│   └── 05/
│       └── 05/
│           ├── 1/
│           │   ├── firewall_config.jpg
│           │   └── access_control.png
│           └── 2/
│               └── security_certificate.pdf
```

## Migration Steps

1. **Run Migration**:
   ```bash
   php artisan migrate
   ```

2. **Update Models**: The `AuditAnswer` model is already updated with:
   - New fillable fields
   - New methods
   - New scopes

3. **Set Up Storage**:
   ```bash
   # Create storage link if not already created
   php artisan storage:link
   ```

4. **Create Controllers/Endpoints** (if needed):
   - Add endpoint to upload proof images
   - Add endpoint to delete proof images
   - Add endpoint to retrieve image URLs

## Logging

The feature logs important events:

```php
// Validation
Log::info('Proof image filename validated successfully', [...]);

// Upload
Log::info('Proof image stored', [...]);

// Errors
Log::warning('Yes answer failed proof image validation', [...]);
Log::error('Error uploading proof image', [...]);
```

## Testing

Test cases to implement:

```php
// Test 1: Yes answer without image should be invalid
$answer->update(['answer' => 'Yes']);
$this->assertFalse($answer->isAnswerValid());

// Test 2: Yes answer with valid image should be valid
$answer->storeProofImage('path/to/image', 'firewall_config.jpg');
$this->assertTrue($answer->isAnswerValid());

// Test 3: Yes answer with invalid image name should be invalid
$answer->storeProofImage('path/to/image', 'image.jpg');
$this->assertFalse($answer->isAnswerValid());

// Test 4: No answer should always be valid
$answer->update(['answer' => 'No']);
$this->assertTrue($answer->isAnswerValid());

// Test 5: Risk calculation for yes with valid image = low
$this->assertEquals('low', $answer->calculateSystemRiskLevel());
```

## API Integration Example

```php
// POST /api/audit-answers/{id}/upload-proof
{
    "proof_image": <binary_file>
}

// Response on success:
{
    "success": true,
    "message": "Image uploaded and validated successfully",
    "data": {
        "path": "proof-images/2026/05/05/1/firewall_config.jpg",
        "filename": "firewall_config.jpg",
        "validated": true
    }
}

// Response on validation failure:
{
    "success": false,
    "message": "Image filename \"image.jpg\" appears to be generic or placeholder. Please use a descriptive name that relates to the audit answer."
}
```

## Future Enhancements

1. **AI Image Analysis**: Replace filename-based validation with actual image content analysis
2. **Image Encryption**: Encrypt stored images for enhanced security
3. **Batch Upload**: Allow uploading multiple proof images per answer
4. **Image Comparison**: Compare images against expected configurations
5. **Digital Signatures**: Require digitally signed proof images
6. **Automated Categorization**: Automatically detect image type from content

## Troubleshooting

### Images not saving to storage
- Ensure `storage/app/public` directory is writable
- Check `FILESYSTEM_DISK` environment variable is set correctly
- Run: `php artisan storage:link`

### Images not accessible via URL
- Verify storage symlink is created: `php artisan storage:link`
- Check `APP_URL` environment variable

### Validation always fails
- Check validation rules in `ProofImageService::validateFile()`
- Verify filename format matches expected patterns
- Check logs in `storage/logs/laravel.log`
