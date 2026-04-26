# Questionnaire Sets Implementation Guide

## Overview
The Audit System has been upgraded to support **multiple questionnaire sets**. Users can now create different sets of questions for different audit scenarios, and submit audits using a specific questionnaire set.

---

## What Has Been Implemented

### 1. Database Changes

#### New Table: `audit_questionnaire_sets`
```sql
Column Name         | Type              | Description
--------------------|-------------------|------------------------------------------
id                 | BIGINT (PK)       | Primary key
name               | VARCHAR(255)      | Unique name of the questionnaire set
description        | TEXT              | Detailed description
status             | ENUM              | draft, active, or archived
created_by         | BIGINT (FK)       | User who created the set
updated_by         | BIGINT (FK)       | User who last updated the set
created_at         | TIMESTAMP         | Creation timestamp
updated_at         | TIMESTAMP         | Update timestamp
deleted_at         | TIMESTAMP         | Soft delete timestamp
```

#### Updated Tables
**`audit_questions`** - Added column:
- `questionnaire_set_id` (BIGINT FK, nullable) - Links question to a set

**`audit_submissions`** - Added column:
- `questionnaire_set_id` (BIGINT FK, nullable) - Tracks which set was used for submission

### 2. New Model: AuditQuestionnaireSet

**File:** `app/Models/AuditQuestionnaireSet.php`

**Key Features:**
- Relationships to questions, submissions, creator, and updater
- Scopes: `active()`, `draft()`, `archived()`
- Methods:
  - `getStatistics()` - Returns submission stats and risk distribution
  - `getQuestionsByCategory()` - Groups questions by category
  - `canBeDeleted()` - Checks if set has no active submissions
  - `duplicate()` - Creates a copy of the set with all questions

**Attributes:**
- `question_count` - Auto-loaded count of questions
- `submission_count` - Auto-loaded count of submissions
- `categories` - Array of unique categories in the set

### 3. Updated Models

#### AuditQuestion
- Added `questionnaire_set_id` to fillable and casts
- New relationship: `questionnaireSet()` - BelongsTo relationship

#### AuditSubmission
- Added `questionnaire_set_id` to fillable and casts
- New relationship: `questionnaireSet()` - BelongsTo relationship

### 4. New Controller: QuestionnaireSetController

**File:** `app/Http/Controllers/QuestionnaireSetController.php`

**Public Methods (Available to all authenticated users):**
- `index()` - List all questionnaire sets (admin only)
- `activeOnly()` - Get only active sets for users to choose from
- `show()` - Display a specific set with its questions

**Admin Methods:**
- `store()` - Create a new questionnaire set
- `update()` - Modify set properties or questions
- `destroy()` - Delete a set (checks for active submissions)
- `statistics()` - Get detailed statistics for a set
- `duplicate()` - Create a copy of an existing set
- `addQuestions()` - Add questions to a set
- `removeQuestions()` - Remove questions from a set
- `archive()` - Archive a set (prevents new submissions)
- `restore()` - Restore an archived set

### 5. Updated Controller: AuditSubmissionController

**Modified `store()` method:**
- Now accepts optional `questionnaire_set_id` parameter
- Validates that all submitted questions belong to the specified set
- Links submission to the questionnaire set
- If no set is specified, accepts questions from any set

### 6. New API Routes

**For All Authenticated Users:**
```
GET    /api/questionnaire-sets/active           - Get active questionnaire sets
GET    /api/questionnaire-sets/{set}            - View specific set
```

**For Admin Only:**
```
GET    /api/questionnaire-sets                  - List all sets
POST   /api/questionnaire-sets                  - Create new set
PUT    /api/questionnaire-sets/{set}            - Update set
DELETE /api/questionnaire-sets/{set}            - Delete set
GET    /api/questionnaire-sets/{set}/statistics - Get set statistics
POST   /api/questionnaire-sets/{set}/duplicate  - Duplicate set
POST   /api/questionnaire-sets/{set}/add-questions       - Add questions
POST   /api/questionnaire-sets/{set}/remove-questions    - Remove questions
PATCH  /api/questionnaire-sets/{set}/archive   - Archive set
PATCH  /api/questionnaire-sets/{set}/restore   - Restore set
```

### 7. New Seeder: QuestionnaireSetSeeder

**File:** `database/seeders/QuestionnaireSetSeeder.php`

**Functionality:**
- Creates "Default Audit Set" (active) with all existing questions
- Creates category-based sets (draft status) for each category
- Automatically runs after AuditQuestionSeeder during seeding

---

## How to Use

### For End Users

#### 1. View Available Questionnaire Sets
```bash
GET /api/questionnaire-sets/active
```

**Response:**
```json
[
  {
    "id": 1,
    "name": "Default Audit Set",
    "description": "Comprehensive audit questionnaire...",
    "questions_count": 42
  },
  {
    "id": 2,
    "name": "Security Protocols Questionnaire",
    "description": "Focused audit questionnaire covering...",
    "questions_count": 8
  }
]
```

#### 2. Submit Audit with Specific Questionnaire Set
```bash
POST /api/audit-submissions
```

**Request Body:**
```json
{
  "title": "Q2 2024 Security Audit",
  "questionnaire_set_id": 1,
  "answers": [
    {
      "audit_question_id": 1,
      "answer": "Yes"
    },
    {
      "audit_question_id": 5,
      "answer": "Others",
      "custom_answer": "Using our proprietary system"
    }
  ]
}
```

**Response:**
```json
{
  "submission": {
    "id": 123,
    "title": "Q2 2024 Security Audit",
    "questionnaire_set_id": 1,
    "status": "submitted",
    "system_overall_risk": "medium"
  },
  "message": "Audit submitted successfully. Pending admin review."
}
```

#### 3. Submit Audit Without Specific Set (Legacy)
```json
{
  "title": "General Audit",
  "answers": [...]
}
```

This still works for backward compatibility.

### For Administrators

#### 1. Create New Questionnaire Set
```bash
POST /api/questionnaire-sets
```

**Request Body:**
```json
{
  "name": "Q3 2024 Assessment",
  "description": "Quarterly security assessment questionnaire",
  "status": "draft",
  "question_ids": [1, 2, 3, 5, 7, 9, 12]
}
```

#### 2. Modify Questionnaire Set
```bash
PUT /api/questionnaire-sets/{id}
```

**Request Body:**
```json
{
  "name": "Q3 2024 Assessment",
  "description": "Updated description",
  "status": "active",
  "question_ids": [1, 2, 3, 5, 7]
}
```

**Note:** If set has active submissions, only status and description can be modified.

#### 3. Add Questions to Set
```bash
POST /api/questionnaire-sets/{id}/add-questions
```

**Request Body:**
```json
{
  "question_ids": [15, 16, 20]
}
```

#### 4. Remove Questions from Set
```bash
POST /api/questionnaire-sets/{id}/remove-questions
```

**Request Body:**
```json
{
  "question_ids": [15, 20]
}
```

#### 5. Duplicate Questionnaire Set
```bash
POST /api/questionnaire-sets/{id}/duplicate
```

**Request Body:**
```json
{
  "name": "Q3 2024 Assessment (Copy)"
}
```

This creates a new draft set with all the same questions.

#### 6. Archive Questionnaire Set
```bash
PATCH /api/questionnaire-sets/{id}/archive
```

Archived sets cannot be used for new submissions but existing submissions remain accessible.

#### 7. Get Set Statistics
```bash
GET /api/questionnaire-sets/{id}/statistics
```

**Response:**
```json
{
  "name": "Default Audit Set",
  "status": "active",
  "question_count": 42,
  "statistics": {
    "total_submissions": 15,
    "completed_submissions": 12,
    "average_risk_level": "medium",
    "high_risk_count": 3,
    "medium_risk_count": 8,
    "low_risk_count": 4
  },
  "categories": ["Inventory Management", "Configuration Management", "Security Protocols", "Access Controls"]
}
```

---

## Migration and Deployment

### Step 1: Run Migrations
```bash
php artisan migrate
```

This will:
- Create `audit_questionnaire_sets` table
- Add `questionnaire_set_id` column to `audit_questions`
- Add `questionnaire_set_id` column to `audit_submissions`

### Step 2: Run Seeders
```bash
php artisan db:seed --class=QuestionnaireSetSeeder
```

This will:
- Create "Default Audit Set" with all existing questions
- Create category-based draft sets for organization

Or run all seeders:
```bash
php artisan db:seed
```

### Step 3: Clear Cache (if using caching)
```bash
php artisan cache:clear
php artisan config:cache
```

---

## Database Relationships

### Entity Relationship Diagram

```
┌─────────────────────────────────┐
│ audit_questionnaire_sets        │
│─────────────────────────────────│
│ id (PK)                         │
│ name (UNIQUE)                   │
│ description                     │
│ status (draft/active/archived)  │
│ created_by (FK → users)         │
│ updated_by (FK → users)         │
└────────────┬────────────────────┘
             │
    ┌────────┼────────┐
    │        │        │
    ▼        ▼        ▼
┌─────────────────┐  ┌──────────────────┐
│ audit_questions │  │ audit_submissions│
│─────────────────│  │──────────────────│
│ id              │  │ id               │
│ question        │  │ user_id (FK)     │
│ category        │  │ title            │
│ ...             │  │ status           │
│ set_id (FK)  ─┐ │  │ set_id (FK)   ─┐│
└─────────────────┘  └──────────────────┘
     │                    │
     │                    ▼
     │              ┌──────────────────┐
     │              │ audit_answers    │
     └─────────────►│──────────────────│
                    │ id               │
                    │ submission_id(FK)│
                    │ question_id (FK) │
                    │ answer           │
                    │ risk_level       │
                    └──────────────────┘
```

### Relationship Descriptions

| From                      | To                          | Type        | FK Column         |
|---------------------------|-----------------------------|-------------|-------------------|
| AuditQuestionnaireSet     | AuditQuestion               | 1 to Many   | questionnaire_set_id |
| AuditQuestionnaireSet     | AuditSubmission             | 1 to Many   | questionnaire_set_id |
| AuditQuestionnaireSet     | User (creator)              | Many to 1   | created_by        |
| AuditQuestionnaireSet     | User (updater)              | Many to 1   | updated_by        |
| AuditQuestion             | AuditQuestionnaireSet       | Many to 1   | questionnaire_set_id |
| AuditSubmission           | AuditQuestionnaireSet       | Many to 1   | questionnaire_set_id |

---

## Validation Rules

### Creating a Questionnaire Set

| Field          | Rule                                          |
|----------------|-----------------------------------------------|
| name           | required, string, max 255, unique in table   |
| description    | nullable, string, max 2000                   |
| status         | required, must be: draft, active, or archived|
| question_ids   | nullable, array, each ID exists in questions|

### Updating a Questionnaire Set

| Field          | Rule                                          |
|----------------|-----------------------------------------------|
| name           | required, string, max 255, unique (ignoring self) |
| description    | nullable, string, max 2000                   |
| status         | required, must be: draft, active, or archived|
| question_ids   | nullable (blocked if set has active submissions) |

---

## Constraints and Business Rules

### 1. Set Status Progression
- New sets start as `draft`
- Draft sets can be modified freely
- Sets become `active` when ready for submissions
- Active sets can be `archived` to prevent new submissions
- Archived sets can be restored if needed

### 2. Question Management
- Questions can belong to only ONE questionnaire set
- Questions can be moved between sets
- Deleting a question doesn't affect existing answers
- Archived questions can't be used in new submissions

### 3. Submission Constraints
- Users must select a questionnaire set for submission
- All submitted questions must belong to the same set
- System validates question-set membership on submission
- Submissions preserve the set ID for audit trail

### 4. Deletion Rules
- Sets with active/under_review submissions cannot be deleted
- Sets with only draft submissions can be deleted
- Soft delete is used (sets can be restored)
- Archive is recommended instead of deletion for active sets

---

## Error Handling

### Common Error Responses

#### Set Not Found
```json
{
  "message": "Questionnaire set not found.",
  "error": "..."
}
```
**Status Code:** 404

#### Cannot Delete Active Set
```json
{
  "message": "Cannot delete a questionnaire set with active submissions.",
  "suggestion": "Archive the set or wait until all submissions are completed."
}
```
**Status Code:** 409

#### Cannot Modify Questions in Use
```json
{
  "message": "Cannot modify questions in a set that has active submissions.",
  "suggestion": "Archive this set and create a new one with the modified questions."
}
```
**Status Code:** 409

#### Validation Failed
```json
{
  "message": "Validation failed.",
  "errors": {
    "name": ["The name has already been taken."],
    "status": ["The selected status is invalid."]
  }
}
```
**Status Code:** 422

#### Questions Don't Belong to Set
```json
{
  "message": "Some questions do not belong to the selected questionnaire set."
}
```
**Status Code:** 422

---

## Performance Considerations

### Indexes
The following indexes were created for performance:
- `questionnaire_sets.status` - For filtering active/draft sets
- `questionnaire_sets.created_at` - For date-based queries
- `audit_questions.questionnaire_set_id` - For foreign key lookups
- `audit_submissions.questionnaire_set_id` - For foreign key lookups

### Query Optimization
- Use `with()` to eager-load relationships
- Use `withCount()` for aggregated counts
- Implement pagination for large result sets

**Example:**
```php
$sets = AuditQuestionnaireSet::withCount(['questions', 'submissions'])
    ->orderBy('created_at', 'desc')
    ->paginate(15);
```

---

## Backward Compatibility

### Legacy Support
- Submissions without `questionnaire_set_id` are still accepted
- Questions without `questionnaire_set_id` are still retrievable
- Existing audits continue to work unchanged
- API is backward compatible - all old endpoints still function

### Migration Path
1. Existing questions remain in database
2. Run seeder to assign questions to "Default Audit Set"
3. Frontend gradually migrates to use questionnaire sets
4. Old submissions continue to work with null set_id

---

## Testing the Implementation

### Unit Test Example
```php
public function test_can_create_questionnaire_set()
{
    $admin = User::factory()->admin()->create();
    $questions = AuditQuestion::factory(5)->create();

    $response = $this->actingAs($admin)
        ->postJson('/api/questionnaire-sets', [
            'name' => 'Test Set',
            'description' => 'Test description',
            'status' => 'active',
            'question_ids' => $questions->pluck('id')->toArray()
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Test Set');
}
```

### Feature Test Example
```php
public function test_user_can_submit_audit_with_questionnaire_set()
{
    $user = User::factory()->user()->create();
    $set = AuditQuestionnaireSet::factory()->create();
    $questions = AuditQuestion::factory(3)->create(['questionnaire_set_id' => $set->id]);

    $response = $this->actingAs($user)
        ->postJson('/api/audit-submissions', [
            'title' => 'Test Audit',
            'questionnaire_set_id' => $set->id,
            'answers' => [
                ['audit_question_id' => $questions[0]->id, 'answer' => 'Yes'],
                ['audit_question_id' => $questions[1]->id, 'answer' => 'No'],
                ['audit_question_id' => $questions[2]->id, 'answer' => 'Partially']
            ]
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('submission.questionnaire_set_id', $set->id);
}
```

---

## Summary of Changes

### Files Created
✅ [database/migrations/2025_01_15_000000_create_audit_questionnaire_sets_table.php](../../database/migrations/2025_01_15_000000_create_audit_questionnaire_sets_table.php)
✅ [app/Models/AuditQuestionnaireSet.php](../../app/Models/AuditQuestionnaireSet.php)
✅ [app/Http/Controllers/QuestionnaireSetController.php](../../app/Http/Controllers/QuestionnaireSetController.php)
✅ [database/seeders/QuestionnaireSetSeeder.php](../../database/seeders/QuestionnaireSetSeeder.php)

### Files Modified
✅ [app/Models/AuditQuestion.php](../../app/Models/AuditQuestion.php) - Added questionnaire_set_id
✅ [app/Models/AuditSubmission.php](../../app/Models/AuditSubmission.php) - Added questionnaire_set_id
✅ [app/Http/Controllers/AuditSubmissionController.php](../../app/Http/Controllers/AuditSubmissionController.php) - Updated store() method
✅ [routes/api.php](../../routes/api.php) - Added questionnaire set routes
✅ [database/seeders/DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php) - Added QuestionnaireSetSeeder

### Key Features Implemented
✅ Create multiple questionnaire sets
✅ Organize questions into sets
✅ Users select set before submission
✅ Admin manage sets (CRUD operations)
✅ Duplicate sets for reuse
✅ Archive/restore functionality
✅ Statistics and analytics per set
✅ Soft delete support
✅ Full audit trail (created_by, updated_by)
✅ Backward compatibility
✅ API endpoints for all operations

---

## Next Steps

1. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

2. **Seed Database:**
   ```bash
   php artisan db:seed --class=QuestionnaireSetSeeder
   ```

3. **Update Frontend:**
   - Add questionnaire set selection UI
   - Update submission form to require set selection
   - Add set management dashboard for admins

4. **Testing:**
   - Test submission with different sets
   - Test admin management features
   - Verify backward compatibility

5. **Documentation:**
   - Update API documentation
   - Create user guides
   - Document admin workflows

---

## Questions & Troubleshooting

**Q: Can I move questions between sets?**
A: Yes, update the question's `questionnaire_set_id` or use the `/remove-questions` and `/add-questions` endpoints.

**Q: What happens if I delete a questionnaire set?**
A: If the set has only draft submissions, it's deleted. Otherwise, it cannot be deleted. Use archive instead.

**Q: Can users see all questionnaire sets?**
A: Only active sets are visible via `/api/questionnaire-sets/active`. Admins can see all sets via `/api/questionnaire-sets`.

**Q: Are old audits affected?**
A: No, existing submissions continue to work. The `questionnaire_set_id` is optional for backward compatibility.

**Q: Can I change a set's status?**
A: Yes, use the `PUT /api/questionnaire-sets/{id}` endpoint to update the status field.
