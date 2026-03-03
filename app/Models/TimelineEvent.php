<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class TimelineEvent extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'project_id',
        'title',
        'description',
        'event_date',
    ];

    protected $casts = [
        'event_date' => 'datetime',
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

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now())
            ->orderBy('event_date');
    }

    public function scopePast($query)
    {
        return $query->where('event_date', '<', now())
            ->orderBy('event_date', 'desc');
    }

    public function scopeByMonth($query, int $month, int $year)
    {
        return $query->whereYear('event_date', $year)
            ->whereMonth('event_date', $month);
    }

    public function scopeInRange($query, \DateTime $startDate, \DateTime $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('event_date', 'desc');
    }

    public function scopeEarliestFirst($query)
    {
        return $query->orderBy('event_date', 'asc');
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Check if event is in the past
     */
    public function isPast(): bool
    {
        return $this->event_date < now();
    }

    /**
     * Check if event is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->event_date > now();
    }

    /**
     * Check if event is today
     */
    public function isToday(): bool
    {
        return $this->event_date->isToday();
    }

    /**
     * Get days from now
     */
    public function getDaysFromNow(): int
    {
        return now()->diffInDays($this->event_date);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDate(): string
    {
        return $this->event_date->format('M d, Y');
    }

    /**
     * Get formatted date time
     */
    public function getFormattedDateTime(): string
    {
        return $this->event_date->format('M d, Y h:i A');
    }

    /**
     * Get relative time (e.g., "2 days from now")
     */
    public function getRelativeTime(): string
    {
        return $this->event_date->diffForHumans();
    }

    /**
     * Get month name
     */
    public function getMonthName(): string
    {
        return $this->event_date->format('F');
    }

    /**
     * Get day name
     */
    public function getDayName(): string
    {
        return $this->event_date->format('l');
    }

    /**
     * Get quarter (Q1, Q2, etc.)
     */
    public function getQuarter(): string
    {
        $quarter = ceil($this->event_date->month / 3);
        return "Q{$quarter}";
    }

    /**
     * Get event status
     */
    public function getStatus(): string
    {
        if ($this->isToday()) {
            return 'today';
        }

        if ($this->isUpcoming()) {
            $days = $this->getDaysFromNow();
            if ($days === 1) {
                return 'tomorrow';
            }
            if ($days <= 7) {
                return 'this_week';
            }
            if ($days <= 30) {
                return 'this_month';
            }
            return 'upcoming';
        }

        $daysPast = now()->diffInDays($this->event_date);
        if ($daysPast === 1) {
            return 'yesterday';
        }

        return 'past';
    }

    /**
     * Get event type/category based on title
     */
    public function getEventType(): string
    {
        $title = strtolower($this->title);

        if (str_contains($title, 'approval') || str_contains($title, 'approved')) {
            return 'approval';
        }

        if (str_contains($title, 'completion') || str_contains($title, 'completed')) {
            return 'completion';
        }

        if (str_contains($title, 'review')) {
            return 'review';
        }

        if (str_contains($title, 'meeting')) {
            return 'meeting';
        }

        if (str_contains($title, 'deadline') || str_contains($title, 'due')) {
            return 'deadline';
        }

        return 'milestone';
    }

    /**
     * Get color based on event type
     */
    public function getColor(): string
    {
        return match($this->getEventType()) {
            'approval' => 'green',
            'completion' => 'blue',
            'review' => 'orange',
            'meeting' => 'purple',
            'deadline' => 'red',
            'milestone' => 'indigo',
            default => 'gray',
        };
    }

    /**
     * Create a milestone event
     */
    public static function createMilestone(Project $project, string $title, \DateTime $date, ?string $description = null): self
    {
        return $project->timelineEvents()->create([
            'title' => $title,
            'event_date' => $date,
            'description' => $description,
        ]);
    }

    /**
     * Get next event
     */
    public function getNextEvent()
    {
        return $this->project->timelineEvents()
            ->where('event_date', '>', $this->event_date)
            ->orderBy('event_date')
            ->first();
    }

    /**
     * Get previous event
     */
    public function getPreviousEvent()
    {
        return $this->project->timelineEvents()
            ->where('event_date', '<', $this->event_date)
            ->orderBy('event_date', 'desc')
            ->first();
    }
}
