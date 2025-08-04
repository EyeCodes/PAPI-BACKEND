 <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMemory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'key',
        'content',
        'metadata',
        'importance',
        'last_accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_accessed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('key', $key);
    }

    public function scopeImportant($query, $minImportance = 5)
    {
        return $query->where('importance', '>=', $minImportance);
    }

    public function scopeRecentlyAccessed($query, $days = 30)
    {
        return $query->where('last_accessed_at', '>=', now()->subDays($days));
    }

    public function markAsAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    public function getShortContentAttribute(): string
    {
        return strlen($this->content) > 100
            ? substr($this->content, 0, 100) . '...'
            : $this->content;
    }

    public function getImportanceLevelAttribute(): string
    {
        if ($this->importance >= 8) return 'Critical';
        if ($this->importance >= 6) return 'High';
        if ($this->importance >= 4) return 'Medium';
        return 'Low';
    }
}
