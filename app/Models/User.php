<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'role',
        'company',
        'email',
        'phone_number',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ==================== RELATIONSHIPS ====================

    public function teamMember(): HasOne
    {
        return $this->hasOne(TeamMember::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_user_roles', 'user_id', 'project_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function projectRoles(): HasMany
    {
        return $this->hasMany(ProjectUserRole::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function ownedClients(): HasMany
    {
        return $this->hasMany(Client::class, 'account_owner_id');
    }

    // ==================== QUERY SCOPES ====================

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    // ==================== BUSINESS LOGIC ====================

    public function getProjectCountAttribute(): int
    {
        return $this->projects()->count();
    }

    public function getAssignedTasksCountAttribute(): int
    {
        return $this->assignedTasks()->where('status', '!=', 'done')->count();
    }

    public function isProjectManager(): bool
    {
        return $this->role === 'Project Manager';
    }

    public function isArchitect(): bool
    {
        return str_contains(strtolower($this->role), 'architect');
    }
}
