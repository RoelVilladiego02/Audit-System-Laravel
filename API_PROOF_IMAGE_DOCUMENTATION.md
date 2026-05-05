# Proof Image Upload API Documentation

## Overview

The Proof Image API allows users to upload, validate, and manage proof images for audit answers. Images are validated based on their filenames to ensure they are descriptive and meaningful.

**Base URL**: `/api`

**Authentication**: All endpoints require Bearer token authentication via `Authorization: Bearer {token}`

---

## Endpoints

### 1. Upload Proof Image

**Endpoint**: `POST /audit-answers/{answer}/proof-image`

**Description**: Upload a proof image for an audit answer. The image will be validated based on filename rules.

**Parameters**:
- `answer` (integer, required) - The ID of the audit answer

**Request Body** (multipart/form-data):
```
proof_image: <binary_file>
```

**Validation Rules**:
- File is required
- File size: max 10 MB
- Allowed formats: jpeg, png, jpg, gif, bmp, webp, pdf

**Example Request** (cURL):
```bash
curl -X POST "http://localhost/api/audit-answers/1/proof-image" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "proof_image=@firewall_config.jpg"
```

**Success Response** (200):
```json
{
    "success": true,
    "message": "Image uploaded and validated successfully",
    "data": {
        "path": "proof-images/2026/05/05/1/firewall_config.jpg",
        "filename": "firewall_config.jpg",
        "validated": true
    }
}
```

**Validation Failed Response** (422):
```json
{
    "success": false,
    "message": "Image filename \"image.jpg\" appears to be generic or placeholder. Please use a descriptive name that relates to the audit answer."
}
```

**Error Responses**:
- 403 Forbidden - User doesn't have permission to upload for this answer
- 404 Not Found - Answer not found
- 422 Unprocessable Entity - File validation failed
- 500 Internal Server Error - Server error

---

### 2. Delete Proof Image

**Endpoint**: `DELETE /audit-answers/{answer}/proof-image`

**Description**: Delete a proof image for an audit answer.

**Parameters**:
- `answer` (integer, required) - The ID of the audit answer

**Example Request** (cURL):
```bash
curl -X DELETE "http://localhost/api/audit-answers/1/proof-image" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response** (200):
```json
{
    "success": true,
    "message": "Proof image deleted successfully."
}
```

**Error Responses**:
- 403 Forbidden - User doesn't have permission
- 404 Not Found - Answer not found
- 500 Internal Server Error - Deletion failed

---

### 3. Get Proof Image URL

**Endpoint**: `GET /audit-answers/{answer}/proof-image/url`

**Description**: Get the public URL of a proof image and its validation status.

**Parameters**:
- `answer` (integer, required) - The ID of the audit answer

**Example Request** (cURL):
```bash
curl -X GET "http://localhost/api/audit-answers/1/proof-image/url" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response** (200):
```json
{
    "success": true,
    "url": "http://localhost/storage/proof-images/2026/05/05/1/firewall_config.jpg",
    "has_image": true,
    "image_data": {
        "filename": "firewall_config.jpg",
        "validated": true,
        "validation_error": null,
        "is_valid_answer": true
    }
}
```

**Response When No Image**:
```json
{
    "success": true,
    "url": null,
    "has_image": false,
    "image_data": {
        "filename": null,
        "validated": false,
        "validation_error": null,
        "is_valid_answer": false
    }
}
```

**Error Responses**:
- 403 Forbidden - User doesn't have permission
- 404 Not Found - Answer not found

---

### 4. Get Answers Needing Images

**Endpoint**: `GET /audit-submissions/{submission}/answers-needing-images`

**Description**: Get all "yes" answers in a submission that require proof images, with their current status.

**Parameters**:
- `submission` (integer, required) - The ID of the audit submission

**Query Parameters**:
None

**Example Request** (cURL):
```bash
curl -X GET "http://localhost/api/audit-submissions/1/answers-needing-images" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response** (200):
```json
{
    "success": true,
    "data": {
        "total_yes_answers": 5,
        "answers_with_valid_images": 3,
        "answers_needing_images": 2,
        "answers_with_invalid_images": 0,
        "answers": [
            {
                "id": 1,
                "question": "Has a detailed inventory of all physical devices been created?",
                "answer": "Yes",
                "has_image": true,
                "image_validated": true,
                "validation_error": null,
                "status": "valid"
            },
            {
                "id": 2,
                "question": "Are network device configurations regularly backed up?",
                "answer": "Yes",
                "has_image": false,
                "image_validated": false,
                "validation_error": null,
                "status": "invalid"
            }
        ]
    }
}
```

**Error Responses**:
- 403 Forbidden - User doesn't have permission to view submission
- 404 Not Found - Submission not found

---

### 5. Get Submission Image Statistics

**Endpoint**: `GET /audit-submissions/{submission}/image-stats`

**Description**: Get statistics about proof images in a submission.

**Parameters**:
- `submission` (integer, required) - The ID of the audit submission

**Example Request** (cURL):
```bash
curl -X GET "http://localhost/api/audit-submissions/1/image-stats" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response** (200):
```json
{
    "success": true,
    "data": {
        "total_answers": 10,
        "yes_answers": 5,
        "answers_with_images": 4,
        "validated_images": 3,
        "missing_images": 1,
        "invalid_images": 1,
        "completion_percentage": 60.0
    }
}
```

**Error Responses**:
- 403 Forbidden - User doesn't have permission
- 404 Not Found - Submission not found

---

### 6. Revalidate Submission Images (Admin Only)

**Endpoint**: `POST /audit-submissions/{submission}/revalidate-images`

**Description**: Revalidate all proof images in a submission. Useful when validation rules change.

**Parameters**:
- `submission` (integer, required) - The ID of the audit submission

**Requires**: Admin role

**Example Request** (cURL):
```bash
curl -X POST "http://localhost/api/audit-submissions/1/revalidate-images" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Success Response** (200):
```json
{
    "success": true,
    "message": "Images revalidated successfully.",
    "data": {
        "total": 5,
        "validated": 4,
        "failed": 1
    }
}
```

**Error Responses**:
- 403 Forbidden - Only admins can revalidate
- 404 Not Found - Submission not found

---

## Filename Validation Rules

### Valid Filenames ✅

Filenames should be descriptive and relate to the audit answer:

```
firewall_config.jpg
access_control_audit.png
mfa_configuration.pdf
network_backup.jpg
security_certificate.pdf
vulnerability_scan_report.jpg
antivirus_status_log.png
backup_verification.jpg
inventory_audit_2026.pdf
compliance_checklist.jpg
```

### Invalid Filenames ❌

These filenames will be rejected:

```
image.jpg                 # Too generic
photo.jpg                 # Too generic
file.pdf                  # Too generic
screenshot.png            # Generic placeholder
test.jpg                  # Placeholder
temp.jpg                  # Temporary
123456.jpg                # Purely numeric
image123.jpg              # Generic + number
```

---

## Authorization

All endpoints check authorization at the controller level:

- **Users**: Can only upload/view/delete images for their own submissions
- **Admins**: Can access/modify images for any submission
- **Revalidate**: Admin-only operation

---

## Error Handling

### Common Error Responses

**400 Bad Request**:
```json
{
    "success": false,
    "message": "Invalid request parameters."
}
```

**401 Unauthorized**:
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

**403 Forbidden**:
```json
{
    "success": false,
    "message": "Unauthorized to perform this action."
}
```

**404 Not Found**:
```json
{
    "success": false,
    "message": "Audit answer not found."
}
```

**422 Unprocessable Entity**:
```json
{
    "success": false,
    "message": "Image filename validation failed message here."
}
```

**500 Internal Server Error**:
```json
{
    "success": false,
    "message": "An error occurred while processing your request."
}
```

---

## Rate Limiting

No specific rate limiting is applied to these endpoints. Standard API rate limits apply.

---

## File Storage

Uploaded proof images are stored at:
```
storage/app/public/proof-images/{year}/{month}/{day}/{answer_id}/{filename}
```

**Example**:
```
storage/app/public/proof-images/2026/05/05/1/firewall_config.jpg
```

Public URLs are generated as:
```
{APP_URL}/storage/proof-images/2026/05/05/1/firewall_config.jpg
```

---

## Frontend Integration Examples

### Vue.js / JavaScript

```javascript
// Upload proof image
async function uploadProofImage(answerId, file) {
    const formData = new FormData();
    formData.append('proof_image', file);

    const response = await fetch(
        `/api/audit-answers/${answerId}/proof-image`,
        {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        }
    );

    return await response.json();
}

// Get image URL
async function getProofImageUrl(answerId) {
    const response = await fetch(
        `/api/audit-answers/${answerId}/proof-image/url`,
        {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        }
    );

    return await response.json();
}

// Get submission statistics
async function getImageStats(submissionId) {
    const response = await fetch(
        `/api/audit-submissions/${submissionId}/image-stats`,
        {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        }
    );

    return await response.json();
}

// Delete proof image
async function deleteProofImage(answerId) {
    const response = await fetch(
        `/api/audit-answers/${answerId}/proof-image`,
        {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        }
    );

    return await response.json();
}
```

### React Example

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: '/api',
    headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
    }
});

// Upload image
const uploadImage = async (answerId, file) => {
    const formData = new FormData();
    formData.append('proof_image', file);

    try {
        const { data } = await api.post(`/audit-answers/${answerId}/proof-image`, formData);
        return data;
    } catch (error) {
        console.error('Upload failed:', error.response.data);
        throw error;
    }
};
```

---

## Troubleshooting

### Image Upload Always Fails

1. Check file size is under 10 MB
2. Verify file format is supported
3. Check filename is descriptive (not generic like "image.jpg")
4. Ensure user is authenticated with valid token
5. Check logs in `storage/logs/laravel.log`

### Can't Delete Image

1. Verify user owns the submission (or is admin)
2. Check that image actually exists
3. Verify storage permissions

### Image URL Returns Null

1. Check that image was actually uploaded (has_image = false?)
2. Verify storage link is created: `php artisan storage:link`
3. Check `APP_URL` environment variable
