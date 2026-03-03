<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'role',
        'avatar',
        'projects_count',
    ];

    protected $casts = [
        'projects_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projects()
    {
        return $this->user->projects();
    }

    // ==================== QUERY SCOPES ====================

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeArchitects($query)
    {
        return $query->where('role', 'like', '%Architect%');
    }

    public function scopeEngineers($query)
    {
        return $query->where('role', 'like', '%Engineer%');
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'like', '%Manager%');
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('user_id');
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Get full name from user
     */
    public function getFullName(): string
    {
        return $this->user->name;
    }

    /**
     * Get email from user
     */
    public function getEmail(): string
    {
        return $this->user->email;
    }

    /**
     * Get phone from user
     */
    public function getPhone(): string
    {
        return $this->user->phone_number;
    }

    /**
     * Update projects count
     */
    public function updateProjectsCount(): void
    {
        $this->update([
            'projects_count' => $this->user->projects()->count(),
        ]);
    }

    /**
     * Get assigned tasks count
     */
    public function getAssignedTasksCount(): int
    {
        return $this->user->assignedTasks()->count();
    }

    /**
     * Get completed tasks count
     */
    public function getCompletedTasksCount(): int
    {
        return $this->user->assignedTasks()->where('status', 'done')->count();
    }

    /**
     * Get task completion rate
     */
    public function getTaskCompletionRate(): float
    {
        $total = $this->getAssignedTasksCount();
        if ($total === 0) {
            return 0;
        }

        return ($this->getCompletedTasksCount() / $total) * 100;
    }

    /**
     * Get active tasks
     */
    public function getActiveTasks()
    {
        return $this->user->assignedTasks()
            ->whereIn('status', ['todo', 'in-progress'])
            ->get();
    }

    /**
     * Is specialist in a field
     */
    public function isSpecialist(string $field): bool
    {
        return str_contains(strtolower($this->role), strtolower($field));
    }
}
