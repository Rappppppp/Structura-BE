<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'industry',
        'contact_person',
        'email',
        'phone',
        'location',
        'active_projects',
        'total_value',
        'status',
        'account_owner_id',
    ];

    protected $casts = [
        'total_value' => 'decimal:2',
        'active_projects' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_clients', 'client_id', 'project_id')
            ->withTimestamps();
    }

    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_owner_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
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

    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeHighValue($query)
    {
        return $query->where('total_value', '>=', 5000000);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('account_owner_id', $userId);
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Check if client has overdue invoices
     */
    public function hasOverdueInvoices(): bool
    {
        return $this->invoices()
            ->where('status', 'overdue')
            ->exists();
    }

    /**
     * Get total project value for this client
     */
    public function getTotalProjectValue(): float
    {
        return $this->projects()->sum('budget');
    }

    /**
     * Get average project progress
     */
    public function getAverageProjectProgress(): float
    {
        $avg = $this->projects()->avg('progress');
        return $avg ?? 0;
    }

    /**
     * Get unpaid invoice amount
     */
    public function getUnpaidAmount(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('amount');
}

    /**
     * Get all active projects
     */
    public function getActiveProjects()
    {
        return $this->projects()->where('status', 'active')->get();
    }

    /**
     * Update active projects count
     */
    public function updateActiveProjectsCount(): void
    {
        $this->update([
            'active_projects' => $this->projects()->where('status', 'active')->count(),
        ]);
    }

    /**
     * Calculate health score (0-100)
     */
    public function getHealthScore(): int
    {
        $score = 100;

        // Deduct for overdue invoices
        if ($this->hasOverdueInvoices()) {
            $score -= 20;
        }

        // Deduct if inactive
        if ($this->status !== 'active') {
            $score -= 30;
        }

        // Consider project progress
        $avgProgress = $this->getAverageProjectProgress();
        if ($avgProgress < 30) {
            $score -= 20;
        }

        return max(0, $score);
    }
}
