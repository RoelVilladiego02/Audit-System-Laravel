# Enforced Questionnaire Set Workflow

## Overview
The system now enforces a **strict workflow** where admins must:
1. **Create questionnaire set first**
2. **Then add questions to that set**

Questions **cannot exist without being assigned to a questionnaire set**.

---

## What Changed

### Database
- `audit_questions.questionnaire_set_id` is now **NOT NULL** (required)
- Migration `2025_01_15_000001_make_questionnaire_set_id_required.php` enforces this
- All existing questions are migrated to "Default Audit Set"

### Model Changes
- `AuditQuestion.questionnaire_set_id` is now required in fillable
- Cannot create questions without a set_id

### API Changes
**Old Way (No Longer Works):**
```bash
POST /api/audit-questions
{
  "question": "...",
  "category": "..."
}
```

**New Way (Required):**
```bash
POST /api/questionnaire-sets/{set}/questions
{
  "questionnaire_set_id": 1,
  "question": "...",
  "category": "..."
}
```

### Routes Updated

#### Creating Questions (Admin Only)
```bash
# Create question in specific questionnaire set
POST /api/questionnaire-sets/{set}/questions

# Update question in specific questionnaire set
PUT /api/questionnaire-sets/{set}/questions/{questionId}

# Delete question (still available at general endpoint)
DELETE /api/audit-questions/{questionId}
```

#### Managing Sets
```bash
# Create questionnaire set first
POST /api/questionnaire-sets

# Then add questions to it
POST /api/questionnaire-sets/{set}/questions
```

---

## New Admin Workflow

### Step 1: Create Questionnaire Set
```bash
POST /api/questionnaire-sets
Content-Type: application/json
Authorization: Bearer ADMIN_TOKEN

{
  "name": "Q3 2024 Security Audit",
  "description": "Quarterly security assessment questionnaire",
  "status": "draft"
}
```

**Response:**
```json
{
  "id": 5,
  "name": "Q3 2024 Security Audit",
  "description": "Quarterly security assessment questionnaire",
  "status": "draft",
  "created_by": 1
}
```

### Step 2: Add Questions to the Set
```bash
POST /api/questionnaire-sets/5/questions
Content-Type: application/json
Authorization: Bearer ADMIN_TOKEN

{
  "questionnaire_set_id": 5,
  "question": "Has a detailed inventory of all physical devices been created?",
  "description": "Checks if organization maintains comprehensive device inventory",
  "category": "Inventory Management",
  "possible_answers": ["Yes", "No"],
  "risk_criteria": {
    "high": ["No"],
    "low": ["Yes"]
  },
  "possible_recommendation": "Establish comprehensive device inventory..."
}
```

**Response:**
```json
{
  "message": "Question created successfully in questionnaire set.",
  "data": {
    "id": 42,
    "questionnaire_set_id": 5,
    "question": "Has a detailed inventory...",
    "category": "Inventory Management",
    ...
  }
}
```

### Step 3: Activate the Set
```bash
PUT /api/questionnaire-sets/5
Content-Type: application/json
Authorization: Bearer ADMIN_TOKEN

{
  "name": "Q3 2024 Security Audit",
  "description": "Quarterly security assessment questionnaire",
  "status": "active"
}
```

### Step 4: Users Can Now Submit with This Set
```bash
POST /api/audit-submissions
Content-Type: application/json
Authorization: Bearer USER_TOKEN

{
  "title": "Q3 2024 Audit Submission",
  "questionnaire_set_id": 5,
  "answers": [
    {
      "audit_question_id": 42,
      "answer": "Yes"
    }
  ]
}
```

---

## Key Features

### ✅ Questions Must Have a Set
- Cannot create orphaned questions
- Every question belongs to exactly one set
- Clear organization and ownership

### ✅ Set Status Controls
- **draft** - Questions can be added/modified freely
- **active** - Users can submit audits using this set
- **archived** - No new submissions allowed (old ones still accessible)

### ✅ Migration Handling
- Existing questions automatically assigned to "Default Audit Set"
- No data loss during migration
- Backward compatible workflow

### ✅ Set Templates
- Create category-based template sets
- Duplicate sets for reuse
- Archive old sets instead of deleting

### ✅ Admin Controls
- **`POST /api/questionnaire-sets`** - Create set
- **`PUT /api/questionnaire-sets/{set}`** - Update set
- **`DELETE /api/questionnaire-sets/{set}`** - Delete set
- **`POST /api/questionnaire-sets/{set}/duplicate`** - Copy set
- **`POST /api/questionnaire-sets/{set}/questions`** - Add question to set
- **`PUT /api/questionnaire-sets/{set}/questions/{id}`** - Update question
- **`PATCH /api/questionnaire-sets/{set}/archive`** - Archive set
- **`PATCH /api/questionnaire-sets/{set}/restore`** - Restore set

---

## Validation Rules

### Creating Question in Set
```
questionnaire_set_id: required, must exist in audit_questionnaire_sets
question: required, max 1000 chars
description: optional, max 2000 chars
category: required, max 255 chars
possible_answers: required, array of strings, min 1
risk_criteria: required, must map answers to high/medium/low
possible_recommendation: optional, max 2000 chars
```

### Set Must Exist
- Set must exist and not be archived
- Validation fails with message: "The selected questionnaire set does not exist or is archived."

### Question Requirements
- All questions require `questionnaire_set_id`
- Questions can only be created within a set
- Trying to create question without set_id will fail

---

## Constraints & Rules

### Cannot Delete Set If:
- ❌ It has submissions in draft status
- ❌ It has submissions in submitted status
- ❌ It has submissions in under_review status

**Can Delete Only If:**
- ✅ All submissions are completed or deleted

### Archive Instead of Delete
- Archive sets to prevent new submissions
- Archived sets cannot accept new audits
- Existing submissions remain accessible
- Can be restored later if needed

### Set Status Flow
```
draft → active → archived
            ↓
         restored back to active
```

---

## Error Responses

### Invalid Set
```json
{
  "message": "Validation failed.",
  "errors": {
    "questionnaire_set_id": ["The selected questionnaire set does not exist or is archived."]
  }
}
```
**Status:** 422

### Cannot Delete Active Set
```json
{
  "message": "Cannot delete a questionnaire set with active submissions.",
  "suggestion": "Archive the set or wait until all submissions are completed."
}
```
**Status:** 409

### Missing Required Set
```json
{
  "message": "Validation failed.",
  "errors": {
    "questionnaire_set_id": ["The questionnaire set id field is required."]
  }
}
```
**Status:** 422

---

## Migration Steps

### 1. Run Migration
```bash
php artisan migrate
```

This will:
- Create `audit_questionnaire_sets` table (if not exists)
- Add columns to questions and submissions (if not exists)
- Make `questionnaire_set_id` NOT NULL
- Migrate all existing questions to "Default Audit Set"

### 2. Verify Migration
```bash
php artisan tinker

# Check default set exists
AuditQuestionnaireSet::where('name', 'Default Audit Set')->first()

# Check questions are assigned
AuditQuestion::count() // should equal questions with set_id
```

### 3. Update API Clients
- Change question creation endpoint
- Update request payloads to include `questionnaire_set_id`
- Test with new endpoints

---

## Database Schema

### audit_questionnaire_sets
```
id (PK)
name (UNIQUE)
description
status (draft|active|archived)
created_by (FK → users)
updated_by (FK → users)
created_at, updated_at, deleted_at
```

### audit_questions (updated)
```
id (PK)
questionnaire_set_id (FK → audit_questionnaire_sets, NOT NULL)
question
category
possible_answers
risk_criteria
...
```

### audit_submissions (updated)
```
id (PK)
questionnaire_set_id (FK → audit_questionnaire_sets, nullable)
...
```

---

## Examples

### Complete Workflow Example

#### 1. Admin Creates Set
```bash
curl -X POST http://api.example.com/api/questionnaire-sets \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "ISO 27001 Compliance",
    "description": "ISO 27001 information security compliance questionnaire",
    "status": "draft"
  }'
```

#### 2. Admin Adds Questions
```bash
# Question 1
curl -X POST http://api.example.com/api/questionnaire-sets/1/questions \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{
    "questionnaire_set_id": 1,
    "question": "Is there a documented information security policy?",
    "category": "Policy",
    "possible_answers": ["Yes", "No", "Partial"],
    "risk_criteria": {
      "high": ["No"],
      "medium": ["Partial"],
      "low": ["Yes"]
    }
  }'

# Question 2
curl -X POST http://api.example.com/api/questionnaire-sets/1/questions \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{
    "questionnaire_set_id": 1,
    "question": "Are access controls implemented?",
    "category": "Access Control",
    "possible_answers": ["Yes", "No"],
    "risk_criteria": {
      "high": ["No"],
      "low": ["Yes"]
    }
  }'
```

#### 3. Admin Activates Set
```bash
curl -X PUT http://api.example.com/api/questionnaire-sets/1 \
  -H "Authorization: Bearer admin_token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "active"
  }'
```

#### 4. User Views Available Sets
```bash
curl http://api.example.com/api/questionnaire-sets/active \
  -H "Authorization: Bearer user_token"
```

#### 5. User Submits Audit
```bash
curl -X POST http://api.example.com/api/audit-submissions \
  -H "Authorization: Bearer user_token" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "ISO 27001 Compliance Assessment",
    "questionnaire_set_id": 1,
    "answers": [
      {
        "audit_question_id": 1,
        "answer": "Yes"
      },
      {
        "audit_question_id": 2,
        "answer": "Yes"
      }
    ]
  }'
```

---

## Summary of Changes

| Component | Change |
|-----------|--------|
| **Migration** | Added `2025_01_15_000001_make_questionnaire_set_id_required.php` |
| **Model** | `AuditQuestion.questionnaire_set_id` now required |
| **Controller** | `AuditQuestionController.store()` requires set_id |
| **Routes** | Questions can only be created in set context |
| **Seeder** | `QuestionnaireSetSeeder` migrates existing questions |
| **Validation** | Enforces set existence and active status |

---

## Benefits

✅ **Clearer Workflow** - Admins know exactly what to do  
✅ **Better Organization** - Questions are always grouped  
✅ **No Orphaned Data** - All questions belong to sets  
✅ **Enforced Structure** - System prevents invalid states  
✅ **Audit Trail** - Track who created/modified sets  
✅ **Template Reuse** - Duplicate sets for similar audits  
✅ **Version Control** - Different sets for different periods  

---

## Backward Compatibility

✅ **Existing Audits** - Continue to work unchanged  
✅ **Old Data** - All questions migrated to Default Set  
✅ **API Clients** - Must update to use new endpoints  
✅ **Database** - Migration handles data transformation  

---

## Next Steps

1. **Run Migration**
   ```bash
   php artisan migrate
   ```

2. **Test Workflow**
   - Create a test set
   - Add questions to it
   - Submit an audit

3. **Update Frontend**
   - Require set selection before question creation
   - Show set in question creation form
   - Update audit submission flow

4. **Documentation**
   - Update API docs
   - Train admins on workflow
   - Update user guides
