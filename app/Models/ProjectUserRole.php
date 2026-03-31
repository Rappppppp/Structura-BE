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
        'base_role',
        'specialty_role',
        'role', // Keep for backward compatibility during migration
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

    public function scopeByBaseRole($query, $baseRole)
    {
        return $query->where('base_role', $baseRole);
    }

    public function scopeBySpecialtyRole($query, $specialtyRole)
    {
        return $query->where('specialty_role', $specialtyRole);
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeAdmins($query)
    {
        return $query->where('base_role', 'admin');
    }

    public function scopeMembers($query)
    {
        return $query->where('base_role', 'member');
    }

    public function scopeViewers($query)
    {
        return $query->where('base_role', 'viewer');
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
     * Check if user is project admin
     */
    public function isProjectAdmin(): bool
    {
        return $this->base_role === 'admin';
    }

    /**
     * Check if user has member or higher access
     */
    public function isMember(): bool
    {
        return in_array($this->base_role, ['admin', 'member']);
    }

    /**
     * Check if user has viewer or higher access
     */
    public function isViewer(): bool
    {
        return in_array($this->base_role, ['admin', 'member', 'viewer']);
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
