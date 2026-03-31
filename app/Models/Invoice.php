<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Use invoice_id as the route key for API endpoints
     */
    public function getRouteKeyName()
    {
        return 'invoice_id';
    }

    protected $fillable = [
        'id',
        'invoice_id',
        'project_id',
        'amount',
        'status',
        'due_date',
        'paid_at',
        'contract_value',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'contract_value' => 'decimal:2',
        'due_date' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // ==================== QUERY SCOPES ====================

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                    ->where('due_date', '<', now());
            });
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaidAfter($query, $date)
    {
        return $query->where('paid_at', '>=', $date);
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date < now();
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDue(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(?\DateTime $paidAt = null): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    /**
     * Mark invoice as overdue
     */
    public function markAsOverdue(): bool
    {
        if ($this->status === 'pending' && $this->isOverdue()) {
            return $this->update(['status' => 'overdue']);
        }
        return false;
    }

    /**
     * Get payment percentage
     */
    public function getPaymentPercentage(): float
    {
        if (!$this->contract_value) {
            return 0;
        }
        return ($this->amount / $this->contract_value) * 100;
    }

    /**
     * Get formatted invoice number
     */
    public function getFormattedInvoiceNumber(): string
    {
        return "#{$this->invoice_id}";
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }
        return now()->diffInDays($this->due_date);
    }

    /**
     * Calculate late fee (if any)
     */
    public function calculateLateFee(float $dailyPercentage = 0.1): float
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->amount * ($dailyPercentage / 100) * $this->getDaysOverdue();
    }

    /**
     * Get total amount with late fees
     */
    public function getTotalAmountWithLateFee(float $dailyPercentage = 0.1): float
    {
        return $this->amount + $this->calculateLateFee($dailyPercentage);
    }

    /**
     * Automatically update status based on due date
     */
    public function syncStatus(): void
    {
        if ($this->status === 'pending' && $this->isOverdue()) {
            $this->update(['status' => 'overdue']);
        }
    }
}
