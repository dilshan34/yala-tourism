<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'calendar_connection_id',
        'property_id',
        'room_id',
        'source_platform',
        'source_event_uid',
        'summary',
        'check_in_date',
        'check_out_date',
        'booked_nights',
        'status',
        'is_duplicate_suspect',
        'is_confirmed_duplicate',
        'duplicate_master_event_id',
        'conflict_detected',
        'raw_metadata',
        'last_seen_in_feed_at',
    ];

    protected function casts(): array
    {
        return [
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'booked_nights' => 'integer',
            'is_duplicate_suspect' => 'boolean',
            'is_confirmed_duplicate' => 'boolean',
            'conflict_detected' => 'boolean',
            'raw_metadata' => 'array',
            'last_seen_in_feed_at' => 'datetime',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(CalendarConnection::class, 'calendar_connection_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function duplicateMaster(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'duplicate_master_event_id');
    }

    public function duplicateSlaves(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'duplicate_master_event_id');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to exclude duplicate events so occupancy isn't double-counted
     */
    public function scopeCountableOccupancy($query)
    {
        return $query->where('is_confirmed_duplicate', false)
                     ->whereIn('status', ['confirmed', 'blocked', 'unknown']);
    }
}
