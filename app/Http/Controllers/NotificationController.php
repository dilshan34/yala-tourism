<?php

namespace App\Http\Controllers;

use App\Models\SafariBooking;
use App\Models\TransportBooking;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);

        // Upcoming activities for tomorrow
        $tomorrow = Carbon::tomorrow()->toDateString();

        $tomorrowSafaris = SafariBooking::with(['primaryGuest', 'property', 'operator', 'driver'])
            ->whereDate('safari_date', $tomorrow)
            ->where('operational_status', '!=', 'cancelled')
            ->get();

        $tomorrowTransports = TransportBooking::with(['primaryGuest', 'property', 'driver', 'vehicle'])
            ->whereDate('transport_date', $tomorrow)
            ->where('operational_status', '!=', 'cancelled')
            ->get();

        return view('notifications.index', compact('notifications', 'tomorrowSafaris', 'tomorrowTransports'));
    }

    public function markAsRead(string $id)
    {
        $notification = auth()->user()->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Manually trigger operational reminder check
     */
    public function triggerCheck()
    {
        $tomorrow = Carbon::tomorrow()->toDateString();
        $staffUsers = User::where('is_active', true)->get();

        $safaris = SafariBooking::with(['primaryGuest', 'property'])
            ->whereDate('safari_date', $tomorrow)
            ->where('operational_status', '!=', 'cancelled')
            ->whereNull('reminder_sent_at')
            ->get();

        $transports = TransportBooking::with(['primaryGuest', 'property'])
            ->whereDate('transport_date', $tomorrow)
            ->where('operational_status', '!=', 'cancelled')
            ->whereNull('reminder_sent_at')
            ->get();

        $count = 0;
        foreach ($safaris as $s) {
            $msg = "Reminder: Safari scheduled tomorrow at {$s->pickup_time} for {$s->primaryGuest->full_name}. Pickup: {$s->pickup_location}";
            foreach ($staffUsers as $u) {
                $u->notify(new OperationalReminderNotification('safari', $s->id, $s->reference_no, $msg));
            }
            $s->update(['reminder_sent_at' => now()]);
            $count++;
        }

        foreach ($transports as $t) {
            $msg = "Reminder: Transport scheduled tomorrow at {$t->pickup_time} for {$t->primaryGuest->full_name}. Route: {$t->pickup_location} -> {$t->drop_off_location}";
            foreach ($staffUsers as $u) {
                $u->notify(new OperationalReminderNotification('transport', $t->id, $t->reference_no, $msg));
            }
            $t->update(['reminder_sent_at' => now()]);
            $count++;
        }

        return back()->with('success', "Operational reminder check completed. Generated {$count} new reminders.");
    }
}
