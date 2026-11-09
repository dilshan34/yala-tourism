<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'calendar_connection_id',
        'started_at',
        'finished_at',
        'status',
        'events_found',
        'events_created',
        'events_updated',
        'events_removed',
        'http_response_time_ms',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_at' => 'datetime',
            'events_found' => 'integer',
            'events_created' => 'integer',
            'events_updated' => 'integer',
            'events_removed' => 'integer',
            'http_response_time_ms' => 'integer',
        ];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(CalendarConnection::class, 'calendar_connection_id');
    }
}
