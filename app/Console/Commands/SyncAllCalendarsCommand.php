<?php

namespace App\Console\Commands;

use App\Jobs\SyncCalendarConnectionJob;
use App\Models\CalendarConnection;
use Illuminate\Console\Command;

class SyncAllCalendarsCommand extends Command
{
    protected $signature = 'calendar:sync-all {--now : Run synchronously instead of queueing}';
    protected $description = 'Synchronize all active Airbnb and Booking.com iCal calendar connections';

    public function handle(): int
    {
        $connections = CalendarConnection::where('status', '!=', 'paused')->get();

        if ($connections->isEmpty()) {
            $this->info('No active calendar connections found.');
            return 0;
        }

        $this->info("Found {$connections->count()} calendar connections. Beginning synchronization...");

        $syncNow = $this->option('now');

        foreach ($connections as $connection) {
            $this->line("Dispatching sync for: [{$connection->platform}] {$connection->calendar_name} (ID: {$connection->id})");

            if ($syncNow) {
                dispatch_sync(new SyncCalendarConnectionJob($connection->id));
            } else {
                SyncCalendarConnectionJob::dispatch($connection->id);
            }
        }

        $this->info('All calendar synchronization jobs have been dispatched.');
        return 0;
    }
}
