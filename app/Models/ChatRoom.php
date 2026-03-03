<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'project_id',
        'last_message',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at', 'desc');
    }

    // ==================== QUERY SCOPES ====================

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeRecentlyActive($query, $minutes = 60)
    {
        return $query->where('last_message_at', '>=', now()->subMinutes($minutes));
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Add message to chat room
     */
    public function addMessage(User $user, string $message): ChatMessage
    {
        $chatMessage = $this->messages()->create([
            'user_id' => $user->id,
            'message' => $message,
        ]);

        $this->update([
            'last_message' => $message,
            'last_message_at' => now(),
        ]);

        return $chatMessage;
    }

    /**
     * Get message count
     */
    public function getMessageCount(): int
    {
        return $this->messages()->count();
    }

    /**
     * Get messages by user
     */
    public function getMessagesByUser(User $user)
    {
        return $this->messages()->where('user_id', $user->id)->get();
    }

    /**
     * Get last message user
     */
    public function getLastMessageUser(): ?User
    {
        return $this->messages()->first()?->user;
    }

    /**
     * Get unique participants count
     */
    public function getParticipantsCount(): int
    {
        return $this->messages()->distinct('user_id')->count();
    }

    /**
     * Get all participants
     */
    public function getParticipants()
    {
        return User::whereIn('id', function ($q) {
            $q->select('user_id')
                ->from('chat_messages')
                ->where('chat_room_id', $this->id)
                ->distinct();
        })->get();
    }

    /**
     * Get messages since date
     */
    public function getMessagesSince(\DateTime $date)
    {
        return $this->messages()->where('created_at', '>=', $date)->get();
    }

    /**
     * Is room active (has messages in last hour)
     */
    public function isActive(): bool
    {
        return $this->last_message_at && $this->last_message_at > now()->subHour();
    }
}
