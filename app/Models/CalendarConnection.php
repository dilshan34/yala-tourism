<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarConnection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'room_id',
        'platform',
        'calendar_name',
        'ical_url',
        'status',
        'sync_interval_minutes',
        'last_synced_at',
        'last_successful_sync_at',
        'last_error_message',
        'last_http_code',
    ];

    protected function casts(): array
    {
        return [
            'ical_url' => 'encrypted',
            'last_synced_at' => 'datetime',
            'last_successful_sync_at' => 'datetime',
            'sync_interval_minutes' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(CalendarSyncLog::class);
    }

    /**
     * Masked calendar URL for safe display in UI
     */
    public function getMaskedUrlAttribute(): string
    {
        try {
            $url = $this->ical_url;
            if (empty($url)) return '';
            $parsed = parse_url($url);
            $host = $parsed['host'] ?? 'calendar';
            return "https://{$host}/••••••••";
        } catch (\Throwable $e) {
            return '••••••••';
        }
    }
}
