<?php

namespace App\Http\Controllers;

use App\Jobs\SyncCalendarConnectionJob;
use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\CalendarEventMatch;
use App\Models\CalendarSyncLog;
use App\Models\Property;
use App\Services\CalendarDeduplicationService;
use App\Services\ICalParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarConnectionController extends Controller
{
    protected ICalParserService $parserService;
    protected CalendarDeduplicationService $deduplicationService;

    public function __construct(ICalParserService $parserService, CalendarDeduplicationService $deduplicationService)
    {
        $this->parserService = $parserService;
        $this->deduplicationService = $deduplicationService;
    }

    public function index(Request $request)
    {
        $connections = CalendarConnection::with(['property', 'room', 'syncLogs' => fn($q) => $q->latest('started_at')->take(5)])
            ->latest()
            ->paginate(15);

        $properties = Property::active()->with('rooms')->get();

        // Potential Duplicates awaiting review
        $potentialMatches = CalendarEventMatch::with(['room.property', 'primaryEvent.connection', 'matchedEvent.connection'])
            ->where('match_type', 'potential_overlap')
            ->latest()
            ->get();

        // Recent Sync Logs
        $recentLogs = CalendarSyncLog::with('connection.property', 'connection.room')
            ->latest('started_at')
            ->take(20)
            ->get();

        return view('calendar-connections.index', compact('connections', 'properties', 'potentialMatches', 'recentLogs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => ['required', 'exists:properties,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'platform' => ['required', 'in:airbnb,booking_com,other'],
            'calendar_name' => ['required', 'string', 'max:191'],
            'ical_url' => ['required', 'url'],
        ]);

        $connection = CalendarConnection::create([
            'property_id' => $validated['property_id'],
            'room_id' => $validated['room_id'],
            'platform' => $validated['platform'],
            'calendar_name' => $validated['calendar_name'],
            'ical_url' => $validated['ical_url'], // Encrypted automatically via cast
            'status' => 'active',
        ]);

        // Attempt initial sync immediately in foreground so user receives instant feedback
        $syncResult = $this->parserService->syncConnection($connection);

        if ($syncResult['success']) {
            return redirect()->route('calendar-connections.index')
                ->with('success', "Calendar '{$connection->calendar_name}' added and synchronized successfully! Found {$syncResult['events_found']} events.");
        } else {
            return redirect()->route('calendar-connections.index')
                ->with('error', "Calendar connection saved, but initial fetch encountered an issue: {$syncResult['error']}");
        }
    }

    public function update(Request $request, CalendarConnection $connection)
    {
        $validated = $request->validate([
            'calendar_name' => ['required', 'string', 'max:191'],
            'ical_url' => ['nullable', 'url'],
            'status' => ['required', 'in:active,paused,failed'],
        ]);

        $data = [
            'calendar_name' => $validated['calendar_name'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['ical_url'])) {
            $data['ical_url'] = $validated['ical_url'];
        }

        $connection->update($data);

        return back()->with('success', "Calendar connection '{$connection->calendar_name}' updated.");
    }

    public function destroy(CalendarConnection $connection)
    {
        $name = $connection->calendar_name;
        $connection->delete();
        return back()->with('success', "Connection '{$name}' deleted.");
    }

    /**
     * AJAX endpoint to test a calendar URL before saving
     */
    public function test(Request $request): JsonResponse
    {
        $request->validate(['ical_url' => ['required', 'url']]);
        $result = $this->parserService->testConnection($request->input('ical_url'));
        return response()->json($result);
    }

    /**
     * Sync one calendar connection immediately
     */
    public function syncNow(CalendarConnection $connection)
    {
        $result = $this->parserService->syncConnection($connection);

        if ($result['success']) {
            return back()->with('success', "Sync complete! Found {$result['events_found']} events ({$result['events_created']} new, {$result['events_updated']} updated).");
        } else {
            return back()->with('error', "Sync failed: {$result['error']}");
        }
    }

    /**
     * Sync all calendars
     */
    public function syncAll()
    {
        $connections = CalendarConnection::where('status', '!=', 'paused')->get();
        $successCount = 0;

        foreach ($connections as $conn) {
            $result = $this->parserService->syncConnection($conn);
            if ($result['success']) {
                $successCount++;
            }
        }

        return back()->with('success', "Synchronized {$successCount} of {$connections->count()} active calendar connections.");
    }

    /**
     * Mark overlapping events as user-confirmed duplicates
     */
    public function confirmDuplicate(Request $request, CalendarEvent $primaryEvent, CalendarEvent $secondaryEvent)
    {
        $this->deduplicationService->confirmDuplicate($primaryEvent, $secondaryEvent, auth()->id());
        return back()->with('success', 'Events confirmed as the same reservation. Duplicate event occupancy weight removed.');
    }

    /**
     * Mark overlapping events as genuine separate conflict
     */
    public function markAsConflict(Request $request, CalendarEvent $event1, CalendarEvent $event2)
    {
        $this->deduplicationService->markAsDistinctConflict($event1, $event2, auth()->id());
        return back()->with('success', 'Events marked as separate bookings. Scheduling conflict retained.');
    }
}
