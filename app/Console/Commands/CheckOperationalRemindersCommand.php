<?php

namespace App\Console\Commands;

use App\Models\SafariBooking;
use App\Models\TransportBooking;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckOperationalRemindersCommand extends Command
{
    protected $signature = 'operations:check-reminders';
    protected $description = 'Scan next-day scheduled safaris and transports to trigger operational reminders';

    public function handle(): int
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $this->info("Scanning scheduled operations for tomorrow: {$tomorrow}");

        // 1. Tomorrow's Safaris (excluding cancelled)
        $upcomingSafaris = SafariBooking::with(['primaryGuest', 'property', 'operator', 'driver'])
            ->whereDate('safari_date', $tomorrow)
            ->where('operational_status', '!=', 'cancelled')
            ->whereNull('reminder_sent_at')
            ->get();

        $this->info("Found {$upcomingSafaris->count()} upcoming safaris requiring reminders.");

        // 2. Tomorrow's Transports (excluding cancelled)
        $upcomingTransports = TransportBooking::with(['primaryGuest', 'property', 'driver', 'vehicle'])
            ->whereDate('transport_date', $tomorrow)
            ->where('operational_status', '!=', 'cancelled')
            ->whereNull('reminder_sent_at')
            ->get();

        $this->info("Found {$upcomingTransports->count()} upcoming transports requiring reminders.");

        $staffUsers = User::where('is_active', true)->get();

        foreach ($upcomingSafaris as $safari) {
            $msg = "Reminder: Safari scheduled tomorrow at {$safari->pickup_time} for {$safari->primaryGuest->full_name} ({$safari->pickup_location}). Status: {$safari->operational_status}";
            
            foreach ($staffUsers as $user) {
                $user->notify(new OperationalReminderNotification('safari', $safari->id, $safari->reference_no, $msg));
            }

            $safari->update(['reminder_sent_at' => now()]);
            $this->line(" [Safari] Reminder sent for {$safari->reference_no}");
        }

        foreach ($upcomingTransports as $transport) {
            $msg = "Reminder: Transport scheduled tomorrow at {$transport->pickup_time} for {$transport->primaryGuest->full_name}. Route: {$transport->pickup_location} -> {$transport->drop_off_location}. Status: {$transport->operational_status}";

            foreach ($staffUsers as $user) {
                $user->notify(new OperationalReminderNotification('transport', $transport->id, $transport->reference_no, $msg));
            }

            $transport->update(['reminder_sent_at' => now()]);
            $this->line(" [Transport] Reminder sent for {$transport->reference_no}");
        }

        $this->info('Operational reminders process completed.');
        return 0;
    }
}
