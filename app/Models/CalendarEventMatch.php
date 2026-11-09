<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEventMatch extends Model
{
    protected $fillable = [
        'room_id',
        'primary_event_id',
        'matched_event_id',
        'match_type',
        'reviewed_by_user_id',
        'reviewed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function primaryEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'primary_event_id');
    }

    public function matchedEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'matched_event_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
