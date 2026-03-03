<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProjectUserRole extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'project_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== QUERY SCOPES ====================

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeManagers($query)
    {
        return $query->where('role', 'Project Manager');
    }

    public function scopeLeads($query)
    {
        return $query->where('role', 'like', '%Lead%');
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Get full name from user
     */
    public function getUserName(): string
    {
        return $this->user->name;
    }

    /**
     * Get email from user
     */
    public function getUserEmail(): string
    {
        return $this->user->email;
    }

    /**
     * Check if user is project manager
     */
    public function isProjectManager(): bool
    {
        return $this->role === 'Project Manager';
    }

    /**
     * Check if user is lead
     */
    public function isLead(): bool
    {
        return str_contains(strtolower($this->role), 'lead');
    }

    /**
     * Get user's tasks in this project
     */
    public function getUserTasks()
    {
        return $this->project->tasks()->where('assigned_to', $this->user_id)->get();
    }

    /**
     * Get user's pending tasks count in this project
     */
    public function getPendingTasksCount(): int
    {
        return $this->project->tasks()
            ->where('assigned_to', $this->user_id)
            ->whereIn('status', ['todo', 'in-progress'])
            ->count();
    }

    /**
     * Get user's completed tasks count in this project
     */
    public function getCompletedTasksCount(): int
    {
        return $this->project->tasks()
            ->where('assigned_to', $this->user_id)
            ->where('status', 'done')
            ->count();
    }
}
