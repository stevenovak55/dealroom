# Modular Task System Implementation Plan

## Overview
Transform the template system from embedded YAML tasks to individual reusable task entities that can be dragged-and-dropped into custom templates.

---

## Architecture Changes

### Current System (YAML-based):
```
Templates (YAML) → Contains embedded tasks → Instantiated to transaction
```

### New System (Modular):
```
Task Definitions (DB records) ← Template Tasks (Junction) → Templates
                ↓
         Transaction Tasks
```

---

## Database Schema

### New Tables Created:

1. **`ma_deal_task_definitions`** - Individual reusable tasks
   - `task_key` - Unique identifier
   - `category` - Task category
   - `title`, `description`
   - `owner_role`, `due_calculation`
   - `applies_if` - Conditional logic
   - `metadata` - JSON (documents, reminders, notes)
   - `is_system`, `is_milestone`, `is_required`
   - `account_id` - For custom tasks

2. **`ma_deal_template_tasks`** - Template-Task relationships
   - Links templates to task definitions
   - `sort_order` - Display order
   - Override fields (due_calculation, owner_role)
   - `is_optional` flag

3. **`ma_deal_transaction_custom_tasks`** - Ad-hoc tasks
   - Tasks added directly to transactions
   - Not part of any template

4. **`ma_deal_task_categories`** - Category definitions
   - 14 default categories
   - For UI organization

### Template Table Updates:
- `template_type` - standard, base, custom
- `extends_template_id` - Template inheritance
- `is_editable` - Can users modify

---

## Implementation Phases

### ✅ Phase 1: Database Migration (COMPLETED)
- [x] Create migration SQL file
- [x] New tables schema
- [x] Default categories seeded

### 🔄 Phase 2: Models & Repositories (IN PROGRESS)
Files to create:
```
src/Models/TaskDefinition.php
src/Models/TemplateTask.php
src/Repositories/TaskDefinitionRepository.php
src/Repositories/TemplateTas

kRepository.php
```

### ⏳ Phase 3: Data Migration
Script: `implement-modular-tasks.php`
- Extract tasks from existing YAML templates
- Create task_definition records
- Create template_tasks associations
- Preserve all existing functionality

### ⏳ Phase 4: Service Layer Updates
Files to update:
```
src/Services/TemplateEngine.php - Use task_definitions instead of YAML
src/Services/TemplateBuilder.php - NEW: Drag-drop builder
src/Services/TaskLibrary.php - NEW: Browse/search tasks
```

### ⏳ Phase 5: Add Complete Task Library
Add 200+ task definitions including:
- All existing template tasks (~150)
- TC workflow tasks from CSV (~73)
- Communication tasks (~20)
- Additional comprehensive tasks

### ⏳ Phase 6: REST API Endpoints
New endpoints needed:
```
GET    /task-definitions          - Browse task library
GET    /task-definitions/{id}     - Get single task
POST   /task-definitions          - Create custom task
PUT    /task-definitions/{id}     - Update task
DELETE /task-definitions/{id}     - Delete custom task
GET    /task-categories           - Get all categories

POST   /templates/{id}/tasks      - Add task to template
PUT    /templates/{id}/tasks/{id} - Update task in template
DELETE /templates/{id}/tasks/{id} - Remove task from template
POST   /templates/{id}/reorder    - Reorder tasks

POST   /transactions/{id}/add-task - Add individual task
```

### ⏳ Phase 7: Frontend Components
React components needed:
```
TaskLibrary.tsx          - Browse and search tasks
TaskCard.tsx             - Display individual task
TemplateBuilder.tsx      - Drag-drop template editor
TaskDefinitionEditor.tsx - Create/edit tasks
CategoryFilter.tsx       - Filter by category
```

---

## Task Definition Structure

```php
class TaskDefinition {
    public int $id;
    public string $task_key;           // unique_task_identifier
    public string $category;           // deal_setup, inspection, etc.
    public string $title;              // Task title
    public ?string $description;       // Detailed description
    public string $owner_role;         // agent, buyer, seller, attorney
    public string $priority;           // low, normal, high, critical
    public ?int $estimated_duration;   // Minutes
    public ?string $due_calculation;   // "Closing-21d", "PS+7d"
    public ?string $applies_if;        // Conditional logic
    public array $depends_on;          // Task dependencies
    public array $metadata;            // Citations, notes, documents
    public bool $is_system;
    public bool $is_milestone;
    public bool $is_required;
    public ?int $account_id;           // NULL for system tasks
}
```

---

## Comprehensive Task Categories

1. **Deal Setup** (15 tasks)
   - Deal room creation
   - Document uploads
   - Commission verification
   - Milestone setup

2. **Party Onboarding** (20 tasks)
   - Choose vendors/professionals
   - Add parties to system
   - Staff member additions
   - Welcome communications

3. **Communication** (15 tasks)
   - Welcome letters
   - Introduction emails
   - Status updates
   - Milestone notifications

4. **Earnest Money** (10 tasks)
   - Deposit collection
   - Check logistics
   - Escrow management
   - Receipt distribution

5. **Inspection** (15 tasks)
   - Schedule inspections
   - Coordinate access
   - Review reports
   - Negotiation support

6. **P&S Agreement** (15 tasks)
   - Draft review
   - Negotiation
   - Execution
   - Distribution

7. **Financing** (15 tasks)
   - Lender selection
   - Application tracking
   - Appraisal coordination
   - Commitment monitoring

8. **Title Work** (10 tasks)
   - Title search
   - Insurance ordering
   - Issue resolution
   - Recording

9. **HOA/Condo** (10 tasks)
   - Document collection
   - 6(d) certificate
   - Review and distribution
   - Compliance

10. **Pre-Closing** (15 tasks)
    - Final preparations
    - Document assembly
    - Walkthrough scheduling
    - Fund coordination

11. **Closing** (10 tasks)
    - Closing day tasks
    - Document execution
    - Key transfer
    - Recording

12. **Post-Closing** (15 tasks)
    - Follow-up
    - Document filing
    - Client gifts
    - Relationship management

13. **Property-Specific** (30 tasks)
    - Septic/well
    - HOA buildings
    - Multifamily
    - New construction

14. **Compliance** (15 tasks)
    - Lead paint
    - Smoke detectors
    - Disclosures
    - Regulatory

**Total: ~200 comprehensive task definitions**

---

## Benefits of Modular System

### For Users:
✅ **Reusable Tasks** - Create once, use everywhere
✅ **Custom Templates** - Build your own workflows
✅ **Drag-and-Drop** - Visual template builder
✅ **Task Library** - Browse and search all available tasks
✅ **Flexible Workflows** - Add individual tasks to any deal
✅ **Easy Updates** - Update task once, affects all templates
✅ **Account-Specific** - Create tasks for your organization

### For Development:
✅ **Better Organization** - Tasks organized by category
✅ **Easier Maintenance** - Update tasks independently
✅ **Performance** - Query only needed tasks
✅ **Versioning** - Track task changes
✅ **Analytics** - Report on task completion rates
✅ **Extensibility** - Easy to add new tasks

---

## Migration Strategy

### Phase 1: Create New System (Non-Breaking)
- New tables alongside old system
- Migrate existing data
- Both systems work in parallel

### Phase 2: Update Services (Feature Flag)
- Feature flag to switch between systems
- Test new system thoroughly
- Gradual rollout

### Phase 3: Deprecate Old System
- Remove YAML template processing
- Delete `template_yaml` column
- Clean up old code

---

## Files Created/Modified

### New Files:
```
database/migrations/006_create_modular_task_system.sql
src/Models/TaskDefinition.php
src/Models/TemplateTask.php
src/Repositories/TaskDefinitionRepository.php
src/Repositories/TemplateTaskRepository.php
src/Services/TemplateBuilder.php
src/Services/TaskLibrary.php
src/REST/Controllers/TaskDefinitionController.php
assets/admin/components/TaskLibrary.tsx
assets/admin/components/TemplateBuilder.tsx
```

### Modified Files:
```
src/Services/TemplateEngine.php
src/Core/Plugin.php (register new services)
src/Models/Template.php (add relationships)
```

---

## Next Steps

1. **Run Migration**: Execute database migration
2. **Create Models**: Build TaskDefinition and TemplateTask models
3. **Migrate Data**: Extract YAML tasks → task_definitions
4. **Update Engine**: Modify TemplateEngine to use new system
5. **Add TC Tasks**: Insert 73 tasks from CSV
6. **Build UI**: Create drag-drop template builder
7. **Test**: Verify all existing functionality works
8. **Deploy**: Roll out to production

---

## Estimated Timeline

- **Database & Models**: 2 hours
- **Data Migration**: 2 hours
- **Service Updates**: 3 hours
- **Add Complete Task Library**: 4 hours
- **REST API**: 2 hours
- **Frontend UI**: 6 hours
- **Testing**: 4 hours
- **Documentation**: 2 hours

**Total: ~25 hours for complete implementation**

---

## Current Status

✅ Database migration SQL created
✅ Implementation plan documented
✅ Architecture designed
⏳ Ready to execute migration
⏳ Models and repositories need creation
⏳ Data migration script ready
⏳ Service layer updates needed
⏳ UI components need building

---

## Commands to Execute

```bash
# 1. Copy migration to plugins directory
docker cp 006_create_modular_task_system.sql ma-dealroom-wp:/var/www/html/wp-content/plugins/ma-deal-room/database/migrations/

# 2. Run migration and data extraction
docker exec ma-dealroom-wp php /var/www/html/implement-modular-tasks.php

# 3. Verify task definitions created
docker exec ma-dealroom-wp php -r "
require_once('/var/www/html/wp-load.php');
global \$wpdb;
echo \$wpdb->get_var('SELECT COUNT(*) FROM wp_ma_deal_task_definitions') . ' task definitions created';
"
```

---

## Success Criteria

✅ All existing templates work with new system
✅ No functionality lost in migration
✅ Users can create custom templates
✅ Users can add individual tasks to deals
✅ Drag-and-drop template builder works
✅ Task library browseable and searchable
✅ Performance equal or better than old system
✅ All 200+ tasks available in library

---

This modular system will transform the MA Deal Room from a rigid template system to a flexible, user-customizable transaction management platform!