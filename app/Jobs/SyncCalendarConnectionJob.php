<?php

namespace App\Jobs;

use App\Models\CalendarConnection;
use App\Services\ICalParserService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncCalendarConnectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $connectionId;
    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(int $connectionId)
    {
        $this->connectionId = $connectionId;
    }

    public function handle(ICalParserService $parserService): void
    {
        $lockKey = 'ical_sync_lock_' . $this->connectionId;
        $lock = Cache::lock($lockKey, 90);

        if (!$lock->get()) {
            Log::info("Calendar sync job skipped for connection {$this->connectionId}: job already running.");
            return;
        }

        try {
            $connection = CalendarConnection::find($this->connectionId);
            if (!$connection) {
                return;
            }

            if ($connection->status === 'paused') {
                return;
            }

            $parserService->syncConnection($connection);
        } finally {
            $lock->release();
        }
    }
}
