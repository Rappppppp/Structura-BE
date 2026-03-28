<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'title',
        'description',
        'project_id',
        'assigned_to',
        'status',
        'priority',
        'due_at',
        'work_percentage',
        'category',
        'subCategory',
        'finishingType',
    ];

    protected $casts = [
        'est_hours' => 'decimal:2',
        'spent_hours' => 'decimal:2',
        'work_percentage' => 'decimal:2',
        'due_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'category' => 'string',
        'subCategory' => 'string',
        'finishingType' => 'string',
    ];

    // ==================== RELATIONSHIPS ====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    // ==================== QUERY SCOPES ====================

    public function scopeTodo($query)
    {
        return $query->where('status', 'todo');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in-progress');
    }

    public function scopeDone($query)
    {
        return $query->where('status', 'done');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeMediumPriority($query)
    {
        return $query->where('priority', 'medium');
    }

    public function scopeLowPriority($query)
    {
        return $query->where('priority', 'low');
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['todo', 'in-progress']);
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Update work percentage and auto-mark as done if 100%
     */
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

    /**
     * Mark task as done
     */
    public function markAsDone(): bool
    {
        return $this->update(['status' => 'done']);
    }

    /**
     * Mark task as in progress
     */
    public function markAsInProgress(): bool
    {
        return $this->update(['status' => 'in-progress']);
    }

    /**
     * Mark task as todo
     */
    public function markAsTodo(): bool
    {
        return $this->update(['status' => 'todo']);
    }

    /**
     * Assign task to user
     */
    public function assignToUser(User $user): bool
    {
        return $this->update(['assigned_to' => $user->id]);
    }

    /**
     * Unassign task
     */
    public function unassign(): bool
    {
        return $this->update(['assigned_to' => null]);
    }

    /**
     * Get assignee name
     */
    public function getAssigneeName(): ?string
    {
        return $this->assignee?->name;
    }

    /**
     * Check if task is overdue (based on project deadline)
     */
    public function isOverdue(): bool
    {
        return $this->project->deadline_at < now() && $this->status !== 'done';
    }

    /**
     * Get priority level (numeric)
     */
    public function getPriorityLevel(): int
    {
        return match ($this->priority) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }

    /**
     * Is high priority and pending
     */
    public function isUrgent(): bool
    {
        return $this->priority === 'high' && $this->status !== 'done';
    }

    /**
     * Get progress percentage (uses work_percentage if set, otherwise based on status)
     */
    public function getProgressPercentage(): float
    {
        // If work percentage is explicitly set, use it
        if ($this->work_percentage > 0) {
            return floatval($this->work_percentage);
        }
        
        // Otherwise calculate based on status
        return match ($this->status) {
            'done' => 100,
            'in-progress' => 50,
            'todo' => 0,
            default => 0,
        };
    }

    /**
     * Check if task is unassigned
     */
    public function isUnassigned(): bool
    {
        return is_null($this->assigned_to);
    }

    /**
     * Get formatted title with priority indicator
     */
    public function getFormattedTitle(): string
    {
        $priority = match ($this->priority) {
            'high' => '🔴',
            'medium' => '🟡',
            'low' => '🟢',
            default => '⚪',
        };

        return "{$priority} {$this->title}";
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'done' => 'green',
            'in-progress' => 'blue',
            'todo' => 'gray',
            default => 'gray',
        };
    }
}
