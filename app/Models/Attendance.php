<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Attendance extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'scope',
        'scope_id',
        'scope_name',
        'check_in_time',
        'check_out_time',
        'check_in_photo',
        'check_out_photo',
        'duration',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project if scope is 'project'.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'scope_id');
    }

    /**
     * Scope: Get active (checked-in but not checked-out) attendance records.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('check_out_time');
    }

    /**
     * Scope: Get completed (both checked-in and checked-out) attendance records.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('check_out_time');
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by scope and optionally by scope_id.
     */
    public function scopeForScope(Builder $query, string $scope, ?string $scopeId = null): Builder
    {
        $query->where('scope', $scope);
        if ($scopeId) {
            $query->where('scope_id', $scopeId);
        }
        return $query;
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeByDateRange(Builder $query, ?string $startDate = null, ?string $endDate = null): Builder
    {
        if ($startDate) {
            $query->whereDate('check_in_time', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('check_in_time', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Calculate and update duration in minutes.
     */
    public function calculateDuration(): void
    {
        if ($this->check_in_time && $this->check_out_time) {
            // diffInMinutes returns a float, so cast to int to store as integer minutes
            $this->duration = (int) floor($this->check_in_time->diffInMinutes($this->check_out_time));
            $this->save();
        }
    }
}
