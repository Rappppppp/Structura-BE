<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'chat_room_id',
        'user_id',
        'message',
    ];
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    public function chatRoom(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==================== QUERY SCOPES ====================

    public function scopeByChatRoom($query, $roomId)
    {
        return $query->where('chat_room_id', $roomId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeContaining($query, $text)
    {
        return $query->where('message', 'like', "%{$text}%");
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Get sender name
     */
    public function getSenderName(): string
    {
        return $this->user->name;
    }

    /**
     * Get sender email
     */
    public function getSenderEmail(): string
    {
        return $this->user->email;
    }

    /**
     * Check if message is recent (less than 5 minutes old)
     */
    public function isRecent(): bool
    {
        return $this->created_at > now()->subMinutes(5);
    }

    /**
     * Get time ago string
     */
    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get message length
     */
    public function getMessageLength(): int
    {
        return strlen($this->message);
    }

    /**
     * Check if message contains mentions
     */
    public function hasMentions(): bool
    {
        return str_contains($this->message, '@');
    }

    /**
     * Get mentioned users
     */
    public function getMentionedUsers()
    {
        preg_match_all('/@(\w+)/', $this->message, $matches);
        if (empty($matches[1])) {
            return [];
        }

        return User::whereIn('name', $matches[1])->get();
    }

    /**
     * Mark message as deleted (soft delete)
     */
    public function deleteGracefully(): bool
    {
        return $this->delete();
    }

    /**
     * Restore deleted message
     */
    public function restore(): bool
    {
        return parent::restore();
    }

    /**
     * Get message preview (truncate if too long)
     */
    public function getPreview(int $length = 100): string
    {
        if (strlen($this->message) > $length) {
            return substr($this->message, 0, $length) . '...';
        }

        return $this->message;
    }

    /**
     * Check if message is edited
     */
    public function isEdited(): bool
    {
        return $this->created_at != $this->updated_at;
    }
}
