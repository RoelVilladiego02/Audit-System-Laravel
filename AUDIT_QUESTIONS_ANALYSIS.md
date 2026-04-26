# Audit Questions System Analysis

## Overview
This document provides a comprehensive analysis of the Audit Questions system in the Audit System Laravel application, including its structure, functionality, and available options for managing questionnaire sets.

---

## 1. Current Audit Questions Structure

### Database Schema
**Table: `audit_questions`**
```
- id (Primary Key)
- question (Text)
- description (Text, nullable)
- category (String, default: 'General')
- possible_answers (JSON Array)
- risk_criteria (JSON Object)
- possible_recommendation (Text, nullable)
- timestamps (created_at, updated_at)
- softDeletes (deleted_at)
```

### Key Model Features
**File:** `app/Models/AuditQuestion.php`

**Relationships:**
- `hasMany(AuditSubmission)` - Questions can be answered in multiple submissions
- `hasMany(AuditAnswer)` - Multiple answers linked to each question
- `scopeActive()` - Filters out soft-deleted questions

**Key Methods:**
- `hasValidRiskCriteria()` - Validates risk criteria structure
- `getFormattedRiskCriteriaAttribute()` - Returns human-readable risk criteria
- `getPossibleAnswersStringAttribute()` - Formats answers as comma-separated string
- `isValidAnswer()` - Validates if an answer is in possible_answers array

---

## 2. Questionnaire Organization

### Current Organization Method: Categories
Questions are organized by **categories** stored in the `category` field:

**Existing Categories in Seeder:**
1. **Inventory Management** - Device and asset tracking
   - Device inventory creation
   - Serial number and location recording
   - Device condition assessment

2. **Configuration Management** - Network and system configuration
   - Network setup documentation
   - Configuration backups
   - Configuration adherence to best practices
   - Network load assessment

3. **Security Protocols** - Security measures and testing
   - Network security measure effectiveness
   - Penetration testing
   - Protocol updates for new threats

4. **Access Controls** - User and system access management
   - Access control verification
   - User access rights alignment
   - Offboarded user account clearance
   - MFA implementation
   - Privilege limitations

### Category Features
- All questions belong to a single category
- No hierarchical grouping or sub-categories
- Categories are text-based strings (no separate category table)
- Default category is "General" if not specified

---

## 3. Answers and Risk Criteria

### Answer Options
Each question has:
- **Possible Answers** (JSON Array) - e.g., `["Yes", "No", "Partially"]`
- **Others Option** - Special handling for custom answers when "Others" is available
- **Custom Answer Support** - Users can provide custom text when selecting "Others"

### Risk Criteria Structure
Risk criteria map possible answers to risk levels:

**Example:**
```json
{
  "high": ["No", "Not Implemented"],
  "medium": ["Partially", "In Progress"],
  "low": ["Yes", "Fully Implemented"]
}
```

**Risk Levels:**
- `high` - Critical security risk
- `medium` - Moderate security concern
- `low` - Minimal risk or compliant

---

## 4. Audit Submission and Answers Workflow

### Submission Process
**File:** `app/Http/Controllers/AuditSubmissionController.php`

**Key Steps:**
1. User creates submission with `title`
2. User submits answers for each question
3. System validates answers against possible_answers
4. System calculates risk levels based on risk_criteria
5. Submission status: `draft → submitted → under_review → completed`

### Answer Submission Structure
```json
{
  "title": "Q2 2024 Security Audit",
  "answers": [
    {
      "audit_question_id": 1,
      "answer": "Yes",
      "custom_answer": null,
      "is_custom_answer": false
    },
    {
      "audit_question_id": 5,
      "answer": "Others",
      "custom_answer": "Using our proprietary system",
      "is_custom_answer": true
    }
  ]
}
```

### Answer Model
**File:** `app/Models/AuditAnswer.php`

**Fields:**
- `audit_submission_id` - FK to submission
- `audit_question_id` - FK to question
- `answer` - Selected or custom answer
- `system_risk_level` - Auto-calculated risk
- `admin_risk_level` - Admin override risk
- `is_custom_answer` - Boolean flag for custom answers
- `status` - `pending` or `reviewed`
- `reviewed_by` - FK to admin reviewer
- `admin_notes` - Admin comments

**Methods:**
- `calculateSystemRiskLevel()` - Automatically determines risk based on answer and criteria
- `getEffectiveRiskLevelAttribute()` - Returns admin override or system calculated level

---

## 5. API Endpoints

### Public/User Endpoints
```
GET    /api/audit-questions              - List all active questions
GET    /api/audit-questions/{id}         - Get specific question
POST   /api/audit-submissions            - Submit audit answers
GET    /api/audit-submissions            - List user's submissions
GET    /api/audit-submissions/{id}       - Get submission details
DELETE /api/audit-submissions/{id}       - Delete submission
PATCH  /api/audit-submissions/{id}/title - Update submission title
```

### Admin-Only Endpoints
```
POST   /api/audit-questions              - Create new question
PUT    /api/audit-questions/{id}         - Update question
DELETE /api/audit-questions/{id}         - Delete (soft delete) question
GET    /api/audit-questions-statistics   - Question usage statistics
GET    /api/audit-questions/archived     - List archived questions
POST   /api/audit-questions/{id}/restore - Restore archived question

PUT    /api/audit-submissions/{id}/answers/{id}/review    - Review single answer
PUT    /api/audit-submissions/{id}/answers/bulk-review    - Bulk review answers
PUT    /api/audit-submissions/{id}/complete               - Mark submission as complete
```

---

## 6. Options to Switch Questionnaire Sets

### ❌ Current Limitations
**There is NO built-in feature to switch between multiple questionnaire sets.**

Current observations:
- Only one active set of questions per system
- All users answer the SAME questions in the same order
- Soft deletes allow archiving questions but don't create alternate sets
- No questionnaire version history or templates
- No branching or conditional questions

---

## 7. Possible Approaches to Implement Multiple Questionnaire Sets

### Option A: Category-Based Filtering (Minimal Change)
**Implementation:** Frontend filters questions by category
- Users can toggle category checkboxes
- Submit only answers for selected categories
- **Pros:** Minimal database changes, easy to implement
- **Cons:** Not true "sets", all questions remain available

### Option B: Add Questionnaire Set Table (Recommended)
**Database Changes:**
```sql
CREATE TABLE audit_questionnaire_sets (
  id BIGINT PRIMARY KEY,
  name VARCHAR(255),
  description TEXT,
  status ENUM('draft', 'active', 'archived'),
  created_by BIGINT,
  created_at, updated_at, deleted_at
);

ALTER TABLE audit_questions ADD COLUMN questionnaire_set_id BIGINT;
ALTER TABLE audit_submissions ADD COLUMN questionnaire_set_id BIGINT;
```

**Implementation Steps:**
1. Create `AuditQuestionnaireSet` model
2. Add migration to create sets table
3. Link questions to specific sets
4. Link submissions to specific sets
5. Update API endpoints to accept set selection
6. Update submission controller to validate questions belong to selected set

### Option C: Version-Based Sets
**Implementation:** Track question versions
```sql
ALTER TABLE audit_questions ADD COLUMN version INT DEFAULT 1;
ALTER TABLE audit_questions ADD COLUMN set_identifier VARCHAR(255);
```

**Benefits:**
- Maintain question history
- Different versions for different departments/periods
- Easy rollback to previous sets

### Option D: Conditional/Branching Questionnaires
**Advanced Option:** Implement skip logic
- Questions shown/hidden based on previous answers
- Different questionnaire paths for different company types
- **Complexity:** High, requires significant redesign

---

## 8. Risk Level Calculation

### System Risk Level Algorithm
```
1. Get the answer provided by user
2. Look up risk_criteria for that question
3. Check which risk level category contains the answer
4. Return that level (high, medium, or low)

Special Cases:
- Custom answers (Others) default to "low" risk
- Invalid answers are handled during validation
- Admin can override with admin_risk_level
```

### Overall Submission Risk
```
Calculate from all answers:
- High Risk % = (count of high answers / total answers) * 100
- Medium Risk % = (count of medium answers / total answers) * 100
- Low Risk % = (count of low answers / total answers) * 100

Final Level:
- If High % >= 40% → "high"
- Else if Medium % >= 40% → "medium"
- Else → "low"
```

---

## 9. Questions Analytics

### Available Statistics
- Total questions created
- Questions by category
- Question usage in submissions
- Question modification history
- Questions with most/least answers

### Admin Dashboard Features
- View all submissions
- Filter by status, user, or date range
- Review and override risk levels
- Generate compliance reports
- Track review progress

---

## 10. Soft Delete Management

### Archive/Restore Functionality
- Questions use Laravel SoftDeletes trait
- Deleted questions remain in database with `deleted_at` timestamp
- `scopeActive()` automatically filters out deleted questions
- **Restore Endpoint:** `POST /api/audit-questions/{id}/restore`
- Archived questions cannot be used in new submissions

### Impact on Existing Submissions
- If a question is deleted, existing answers remain intact
- Can still view completed audits with deleted questions
- New submissions cannot use archived questions

---

## Summary

### Current State
✅ Single unified questionnaire set with category organization
✅ Flexible answer options with custom answer support
✅ Automatic risk assessment based on criteria
✅ Admin review and override capabilities
✅ Soft delete for archiving questions

### Missing Features
❌ Multiple questionnaire sets/versions
❌ Set switching/selection mechanism
❌ Conditional/branching logic
❌ Department/role-specific questionnaires
❌ Question versioning system

### Recommendations
1. **For immediate needs:** Use categories to organize related questions
2. **For multiple sets:** Implement Option B (Questionnaire Set Table)
3. **For future scalability:** Plan for versioning system (Option C)
4. **For complex scenarios:** Consider conditional questionnaires (Option D)
