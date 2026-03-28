# Structura Backend - Project Status Update

## Date: March 20, 2026

### 🎯 Summary of Changes

#### **🐛 Critical Bug Fix: Kanban Task Status Auto-Update**

**Problem:**
- Tasks marked with 100% work completion were NOT automatically transitioning to "Done" status
- Users had to manually update status despite work being complete
- Inconsistent project progress calculations

**Solution:**
- Added independently tracked `work_percentage` field to tasks (0-100 scale)
- Implemented automatic status transition: 100% work → "done" status
- Maintains separate work tracking from status tracking
- Backwards compatible with existing tasks

---

### 📋 Technical Implementation

#### **1. Database Migration**

File: `database/migrations/2026_03_20_110000_add_work_percentage_to_tasks_table.php`

```php
Schema::table('tasks', function (Blueprint $table) {
    $table->decimal('work_percentage', 5, 2)
        ->default(0)
        ->after('priority')
        ->comment('Work completion percentage (0-100)');
});
```

#### **2. Task Model Updates**

**New Method:**
```php
public function updateWorkPercentage(float $percentage): bool
{
    $percentage = max(0, min(100, $percentage)); // Clamp between 0-100
    
    $data = ['work_percentage' => $percentage];
    
    // Auto-mark as done if work reaches 100%
    if ($percentage >= 100 && $this->status !== 'done') {
        $data['status'] = 'done';
    }
    
    return $this->update($data);
}
```

**Updated Field Casts:**
```php
protected $casts = [
    'work_percentage' => 'decimal:2',  // NEW
    // ... other casts
];
```

**Updated Fillable:**
```php
protected $fillable = [
    // ... existing fields
    'work_percentage',  // NEW
];
```

**Updated Method:**
```php
public function getProgressPercentage(): float
{
    // Returns work_percentage if set, otherwise calculated from status
    if ($this->work_percentage > 0) {
        return floatval($this->work_percentage);
    }
    
    return match ($this->status) {
        'done' => 100,
        'in-progress' => 50,
        'todo' => 0,
        default => 0,
    };
}
```

#### **3. Request Validation**

**StoreTaskRequest:**
```php
public function rules(): array
{
    return [
        // ... existing rules
        'work_percentage' => 'nullable|numeric|min:0|max:100',  // NEW
    ];
}
```

**UpdateTaskRequest:**
```php
public function rules(): array
{
    return [
        // ... existing rules
        'work_percentage' => 'nullable|numeric|min:0|max:100',  // NEW
    ];
}
```

#### **4. Controller Logic**

**TaskController::update()**
```php
public function update(UpdateTaskRequest $request, Task $task)
{
    $data = $request->validated();
    
    // Handle work_percentage specially
    if (isset($data['work_percentage'])) {
        $workPercentage = $data['work_percentage'];
        unset($data['work_percentage']);
        $task->update($data);
        $task->updateWorkPercentage($workPercentage); // Auto-updates status if 100%
    } else {
        $task->update($data);
    }

    $task->project->updateProgressFromTasks();

    return $this->success(new TaskResource($task), 'Task updated');
}
```

#### **5. Project Resource - Invoice Integration**

**ProjectResource:**
```php
public function toArray($request): array
{
    return [
        // ... existing fields
        'invoices' => $this->whenLoaded('invoices', function () {
            return InvoiceResource::collection($this->invoices);  // NEW
        }),
        // ... rest of fields
    ];
}
```

#### **6. ProjectController - Eager Loading**

**Updated index() method:**
```php
$query = Project::query()
    ->with(['clients', 'invoices'])  // Added invoices
    ->withCount('team');
```

**Existing show() method:**
```php
$project->load(['clients', 'tasks', 'invoices', 'team'])
    ->loadCount('team');
```

---

### 🔄 Workflow Flow

```
User updates work_percentage to 100%
        ↓
API receives UPDATE request with work_percentage=100
        ↓
UpdateTaskRequest validates: numeric, min:0, max:100 ✓
        ↓
TaskController::update() extracts work_percentage
        ↓
task->updateWorkPercentage(100) called
        ↓
Task model clamps value: min(100, 100) = 100 ✓
        ↓
Since 100 >= 100 AND status !== 'done'
    → Sets status = 'done'
        ↓
Both fields saved to database atomically
        ↓
task->project->updateProgressFromTasks() recalculates
        ↓
TaskResource returned with updated data
        ↓
Frontend receives response with status='done'
```

---

### 📊 Database Schema

```sql
-- tasks table
CREATE TABLE tasks (
    id UUID PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    project_id UUID NOT NULL,
    assigned_to UUID,
    status ENUM('todo', 'in-progress', 'done') DEFAULT 'todo',
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    work_percentage DECIMAL(5,2) DEFAULT 0 COMMENT 'Work completion percentage (0-100)',
    due_at DATETIME,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);

-- invoices table (existing, now included in project responses)
CREATE TABLE invoices (
    id UUID PRIMARY KEY,
    invoice_id VARCHAR(255) UNIQUE,
    project_id UUID NOT NULL,
    amount DECIMAL(15,2),
    status ENUM('paid', 'pending', 'overdue') DEFAULT 'pending',
    due_date DATETIME,
    paid_at DATETIME,
    contract_value DECIMAL(15,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id)
);
```

---

### ✅ API Endpoints

#### **Create Task**
```
POST /api/tasks
Content-Type: application/json

{
  "title": "Build database schema",
  "description": "Design and implement DB schema",
  "project_id": "550e8400-e29b-41d4-a716-446655440000",
  "priority": "high",
  "assigned_to": "660e8400-e29b-41d4-a716-446655440000",
  "work_percentage": 0
}
```

#### **Update Task with Work Progress**
```
PUT /api/tasks/550e8400-e29b-41d4-a716-446655440001
Content-Type: application/json

{
  "work_percentage": 100
}

RESPONSE:
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440001",
    "status": "done",  // AUTO-UPDATED
    "work_percentage": 100,
    // ... other fields
  }
}
```

#### **Get Project with Invoices**
```
GET /api/projects/550e8400-e29b-41d4-a716-446655440000

RESPONSE:
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Downtown Tower",
    "budget": 5000000,
    "invoices": [
      {
        "id": "inv-uuid-1",
        "invoice_id": "INV-2026-001",
        "amount": 1500000,
        "status": "pending",
        "due_date": "2026-04-20T00:00:00",
        "paid_at": null
      },
      {
        "id": "inv-uuid-2",
        "invoice_id": "INV-2026-002",
        "amount": 2000000,
        "status": "paid",
        "due_date": "2026-03-15T00:00:00",
        "paid_at": "2026-03-14T10:30:00"
      }
    ]
  }
}
```

---

### 🧪 Testing Scenarios

#### **Scenario 1: Auto-Status Update**
```
1. Create task with status='todo', work_percentage=0
2. PATCH /api/tasks/{id} with work_percentage=50
   Expected: status remains 'todo' ✓
3. PATCH /api/tasks/{id} with work_percentage=100
   Expected: status changed to 'done' ✓
```

#### **Scenario 2: Invoice Retrieval**
```
1. GET /api/projects/{id}
   Expected: invoices array included in response ✓
2. Create invoice for project
3. GET /api/projects/{id}
   Expected: new invoice visible in invoices array ✓
```

#### **Scenario 3: Progress Calculation**
```
1. Project with 3 tasks
   - Task 1: status='done', work_percentage=100
   - Task 2: status='in-progress', work_percentage=75
   - Task 3: status='todo', work_percentage=0
2. Calculated progress = (100 + 75 + 0) / 3 = ~58.33% ✓
```

---

### 🚀 Deployment Checklist

- [x] Migration file created
- [x] Task model updated (fillable, casts, methods)
- [x] Request validations updated
- [x] Controller updated
- [x] ProjectResource updated
- [x] ProjectController updated
- [x] No breaking changes to existing API
- [x] Backwards compatible (existing tasks unaffected)

**Deployment Steps:**
```bash
# 1. Pull latest code
git pull origin main

# 2. Run migrations
php artisan migrate

# 3. Clear caches
php artisan cache:clear
php artisan config:clear

# 4. Test endpoints
curl -X GET http://localhost/api/projects \
  -H "Authorization: Bearer {token}"

# 5. Verify invoices in response
# Should see "invoices" array in each project object

# 6. Test work percentage update
curl -X PUT http://localhost/api/tasks/{id} \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"work_percentage": 100}'

# Expected: status auto-changes to 'done'
```

---

### 📚 Migration File Reference

**File:** `database/migrations/2026_03_20_110000_add_work_percentage_to_tasks_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('work_percentage', 5, 2)
                ->default(0)
                ->after('priority')
                ->comment('Work completion percentage (0-100)');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('work_percentage');
        });
    }
};
```

---

### 🔗 Related Files

- `/app/Models/Task.php` - Updated fillable, casts, methods
- `/app/Http/Requests/StoreTaskRequest.php` - Added validation
- `/app/Http/Requests/UpdateTaskRequest.php` - Added validation
- `/app/Http/Controllers/Api/TaskController.php` - Updated update() method
- `/app/Http/Resources/ProjectResource.php` - Added invoices
- `/app/Http/Controllers/Api/ProjectController.php` - Updated with() calls

---

### 📞 Support

For questions or issues:
1. Check test scenarios above
2. Review API endpoint examples
3. Verify database migration ran: `php artisan migrate:status`
4. Check logs: `storage/logs/laravel.log`

---

**Status:** ✅ COMPLETE & DEPLOYED
**Version:** 1.0.0
**Last Updated:** March 20, 2026
**Test Coverage:** ✓ All scenarios tested
