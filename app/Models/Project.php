<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'client_id',
        'budget',
        'progress',
        'status',
        'deadline_at',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'progress' => 'decimal:2',
        'deadline_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function team(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user_roles', 'project_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projectUserRoles(): HasMany
    {
        return $this->hasMany(ProjectUserRole::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function chatRooms(): HasMany
    {
        return $this->hasMany(ChatRoom::class);
    }

    public function timelineEvents(): HasMany
    {
        return $this->hasMany(TimelineEvent::class)->orderBy('event_date', 'desc');
    }

    // ==================== QUERY SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline_at', '<', now())
            ->whereNotIn('status', ['completed', 'on-hold']);
    }

    public function scopeNearingDeadline($query, $days = 7)
    {
        return $query->whereBetween('deadline_at', [now(), now()->addDays($days)])
            ->where('status', '!=', 'completed');
    }

    public function scopeHighBudget($query)
    {
        return $query->where('budget', '>=', 5000000);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Check if project is overdue
     */
    public function isOverdue(): bool
    {
        return $this->deadline_at < now() && $this->status !== 'completed';
    }

    /**
     * Get days until deadline
     */
    public function getDaysUntilDeadline(): int
    {
        return now()->diffInDays($this->deadline_at, false);
    }

    /**
     * Determine project health (Red/Yellow/Green)
     */
    public function getHealthStatus(): string
    {
        if ($this->status === 'completed') {
            return 'completed';
        }

        if ($this->isOverdue() || ($this->progress < 30 && $this->getDaysUntilDeadline() < 30)) {
            return 'red';
        }

        if ($this->progress < 50 && $this->getDaysUntilDeadline() < 45) {
            return 'yellow';
        }

        return 'green';
    }

    /**
     * Get budget utilization percentage
     */
    public function getBudgetUtilization(): float
    {
        $spent = $this->invoices()->sum('amount');
        return ($spent / $this->budget) * 100;
    }

    /**
     * Check if budget is exceeded
     */
    public function isBudgetExceeded(): bool
    {
        return $this->getBudgetUtilization() > 100;
    }

    /**
     * Get remaining budget
     */
    public function getRemainingBudget(): float
    {
        $spent = $this->invoices()->sum('amount');
        return max(0, $this->budget - $spent);
    }

    /**
     * Get pending tasks count
     */
    public function getPendingTasksCount(): int
    {
        return $this->tasks()->whereIn('status', ['todo', 'in-progress'])->count();
    }

    /**
     * Get team members count
     */
    public function getTeamCount(): int
    {
        return $this->team()->count();
    }

    /**
     * Update progress based on completed tasks
     */
    public function updateProgressFromTasks(): void
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return;
        }

        $completedTasks = $this->tasks()->where('status', 'done')->count();
        $newProgress = ($completedTasks / $totalTasks) * 100;

        $this->update(['progress' => min(100, $newProgress)]);
    }

    /**
     * Assign user to project with role
     */
    public function assignUserWithRole(User $user, string $role): ProjectUserRole
    {
        return $this->projectUserRoles()->create([
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    /**
     * Get project manager
     */
    public function getProjectManager(): ?User
    {
        return $this->team()
            ->wherePivot('role', 'Project Manager')
            ->first();
    }

    /**
     * Calculate project risk score (0-100)
     */
    public function getRiskScore(): int
    {
        $risk = 0;

        // Risk from deadline
        if ($this->isOverdue()) {
            $risk += 40;
        } elseif ($this->getDaysUntilDeadline() < 7) {
            $risk += 30;
        }

        // Risk from progress vs time
        $expectedProgress = 100 * (1 - ($this->getDaysUntilDeadline() / now()->diffInDays($this->deadline_at->copy()->subMonths(3))));
        if ($this->progress < $expectedProgress - 20) {
            $risk += 25;
        }

        // Risk from budget
        if ($this->isBudgetExceeded()) {
            $risk += 30;
        }

        // Risk from low team
        if ($this->getTeamCount() < 2) {
            $risk += 15;
        }

        return min(100, $risk);
    }
}
