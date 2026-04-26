# Quick Reference - Questionnaire Sets Implementation

## What Was Added

### New Functionality
✅ **Multiple Questionnaire Sets** - Create different sets of questions
✅ **Set Management** - CRUD operations for questionnaire sets
✅ **Set Selection** - Users choose which set to use when submitting audits
✅ **Set Organization** - Organize questions by category or purpose
✅ **Statistics** - Analytics for each questionnaire set
✅ **Duplicating Sets** - Copy existing sets as templates
✅ **Archive/Restore** - Archive sets without deleting them

---

## Key Files

| File | Type | Purpose |
|------|------|---------|
| `database/migrations/2025_01_15_000000_create_audit_questionnaire_sets_table.php` | Migration | Creates tables and columns |
| `app/Models/AuditQuestionnaireSet.php` | Model | Questionnaire set model with relationships |
| `app/Http/Controllers/QuestionnaireSetController.php` | Controller | API endpoints for set management |
| `database/seeders/QuestionnaireSetSeeder.php` | Seeder | Seeds initial questionnaire sets |
| `app/Models/AuditQuestion.php` | Model | Updated with set relationship |
| `app/Models/AuditSubmission.php` | Model | Updated with set tracking |
| `app/Http/Controllers/AuditSubmissionController.php` | Controller | Updated to handle set selection |
| `routes/api.php` | Routes | Added questionnaire set endpoints |

---

## Database Schema

### New Table: audit_questionnaire_sets
```
id | name | description | status | created_by | updated_by | created_at | updated_at | deleted_at
```

### Updated Columns
- `audit_questions.questionnaire_set_id` - FK to set
- `audit_submissions.questionnaire_set_id` - FK to set

---

## API Endpoints

### User Endpoints
```
GET  /api/questionnaire-sets/active          - Get active sets to choose from
GET  /api/questionnaire-sets/{set}           - View specific set details
POST /api/audit-submissions                   - Submit audit with set selection
```

### Admin Endpoints
```
GET    /api/questionnaire-sets               - List all sets
POST   /api/questionnaire-sets               - Create new set
PUT    /api/questionnaire-sets/{set}         - Update set
DELETE /api/questionnaire-sets/{set}         - Delete set
GET    /api/questionnaire-sets/{set}/statistics  - Get statistics
POST   /api/questionnaire-sets/{set}/duplicate   - Copy set
POST   /api/questionnaire-sets/{set}/add-questions      - Add questions
POST   /api/questionnaire-sets/{set}/remove-questions   - Remove questions
PATCH  /api/questionnaire-sets/{set}/archive - Archive set
PATCH  /api/questionnaire-sets/{set}/restore - Restore set
```

---

## How to Use

### Step 1: Run Migration
```bash
php artisan migrate
```

### Step 2: Run Seeder
```bash
php artisan db:seed --class=QuestionnaireSetSeeder
```

This creates:
- ✅ "Default Audit Set" (active) with all questions
- ✅ Category-based sets (draft) for each category

### Step 3: API Usage

#### Get Available Sets
```bash
curl -H "Authorization: Bearer TOKEN" \
  https://yourapi.com/api/questionnaire-sets/active
```

#### Create New Set (Admin)
```bash
curl -X POST -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Q3 2024 Audit",
    "description": "Quarterly security audit",
    "status": "draft",
    "question_ids": [1,2,3,4,5]
  }' \
  https://yourapi.com/api/questionnaire-sets
```

#### Submit Audit with Set
```bash
curl -X POST -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Security Audit Q3 2024",
    "questionnaire_set_id": 1,
    "answers": [
      {"audit_question_id": 1, "answer": "Yes"},
      {"audit_question_id": 2, "answer": "No"}
    ]
  }' \
  https://yourapi.com/api/audit-submissions
```

---

## Important Notes

### Backward Compatibility
✅ Old submissions without set_id still work
✅ Questions without set_id are still accessible
✅ All existing API endpoints remain unchanged
✅ Gradual migration to new system possible

### Constraints
- ⚠️ Sets with active submissions cannot delete questions
- ⚠️ Sets with active submissions cannot be deleted (can be archived)
- ⚠️ Question can only belong to ONE set
- ⚠️ Set names must be unique

### Best Practices
1. Create sets for different audit types (e.g., Q1, Q2, Q3, Q4)
2. Archive old sets instead of deleting
3. Duplicate sets to create variations
4. Use descriptive names and descriptions
5. Test sets in draft status before activating

---

## Model Relationships

```
AuditQuestionnaireSet
  ├─ hasMany: AuditQuestion
  ├─ hasMany: AuditSubmission
  ├─ belongsTo: User (creator)
  └─ belongsTo: User (updater)

AuditQuestion
  └─ belongsTo: AuditQuestionnaireSet

AuditSubmission
  └─ belongsTo: AuditQuestionnaireSet
```

---

## Troubleshooting

### Migration Fails
```bash
# Rollback and retry
php artisan migrate:rollback
php artisan migrate
```

### Seeder Issues
```bash
# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Foreign Key Constraint
Ensure users table exists before seeding:
```bash
php artisan migrate
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=AuditQuestionSeeder
php artisan db:seed --class=QuestionnaireSetSeeder
```

---

## Set Status Values

| Status   | Meaning | New Submissions |
|----------|---------|-----------------|
| draft    | In development | ❌ No |
| active   | Ready to use | ✅ Yes |
| archived | No longer used | ❌ No |

---

## Testing

### Test Submission with Set
```php
$response = $this->actingAs($user)
    ->postJson('/api/audit-submissions', [
        'title' => 'Test',
        'questionnaire_set_id' => $set->id,
        'answers' => [...]
    ]);
$response->assertStatus(201);
```

### Test Set Creation
```php
$response = $this->actingAs($admin)
    ->postJson('/api/questionnaire-sets', [
        'name' => 'Test Set',
        'status' => 'draft',
        'question_ids' => [1,2,3]
    ]);
$response->assertStatus(201);
```

---

## Performance Tips

1. **Eager Load Relationships**
   ```php
   $set = AuditQuestionnaireSet::with('questions', 'submissions')->find($id);
   ```

2. **Use Pagination**
   ```php
   $sets = AuditQuestionnaireSet::paginate(15);
   ```

3. **Count Efficiently**
   ```php
   $sets = AuditQuestionnaireSet::withCount('questions')->get();
   ```

---

## Summary of Changes

**Total Files Created:** 4
**Total Files Modified:** 5
**New Database Table:** 1
**New Columns Added:** 2
**New API Endpoints:** 16
**New Models:** 1
**New Controllers:** 1
**New Seeders:** 1

---

## Next Actions

1. ✅ Code implementation complete
2. 📋 Run migrations
3. 🌱 Run seeders  
4. 🧪 Test API endpoints
5. 🎨 Update frontend UI
6. 📚 Update API documentation
7. 👥 Train team on new features

---

For detailed information, see: [QUESTIONNAIRE_SETS_IMPLEMENTATION.md](./QUESTIONNAIRE_SETS_IMPLEMENTATION.md)
