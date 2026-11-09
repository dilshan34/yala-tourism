<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\CalendarEventMatch;
use Illuminate\Support\Facades\Log;

class CalendarDeduplicationService
{
    /**
     * Evaluate an event against all other events for the same room to identify overlaps & potential duplicates.
     */
    public function evaluateEvent(CalendarEvent $event): void
    {
        if ($event->status === 'cancelled') {
            return;
        }

        // Query overlapping active events in the same room from DIFFERENT connections
        $overlappingEvents = CalendarEvent::where('room_id', $event->room_id)
            ->where('id', '!=', $event->id)
            ->where('calendar_connection_id', '!=', $event->calendar_connection_id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($event) {
                // Standard date overlap formula: startA < endB AND endA > startB
                $query->where('check_in_date', '<', $event->check_out_date)
                      ->where('check_out_date', '>', $event->check_in_date);
            })
            ->get();

        if ($overlappingEvents->isEmpty()) {
            return;
        }

        foreach ($overlappingEvents as $otherEvent) {
            // Check if exact same dates
            $isExactMatch = ($event->check_in_date->toDateString() === $otherEvent->check_in_date->toDateString()) &&
                            ($event->check_out_date->toDateString() === $otherEvent->check_out_date->toDateString());

            // Mark both as suspect
            $event->update([
                'is_duplicate_suspect' => true,
                'conflict_detected' => !$isExactMatch,
            ]);

            $otherEvent->update([
                'is_duplicate_suspect' => true,
                'conflict_detected' => !$isExactMatch,
            ]);

            // Record or update audit match
            CalendarEventMatch::firstOrCreate(
                [
                    'room_id' => $event->room_id,
                    'primary_event_id' => min($event->id, $otherEvent->id),
                    'matched_event_id' => max($event->id, $otherEvent->id),
                ],
                [
                    'match_type' => 'potential_overlap',
                    'notes' => $isExactMatch
                        ? 'Exact date match across platforms (likely synchronized calendar).'
                        : 'Partial date overlap detected between different platform feeds.',
                ]
            );
        }
    }

    /**
     * Confirm that two overlapping events represent the same reservation.
     * Keeps primaryEvent as countable, marks secondaryEvent as confirmed duplicate.
     */
    public function confirmDuplicate(CalendarEvent $primaryEvent, CalendarEvent $secondaryEvent, ?int $userId = null): void
    {
        $secondaryEvent->update([
            'is_duplicate_suspect' => false,
            'is_confirmed_duplicate' => true,
            'duplicate_master_event_id' => $primaryEvent->id,
            'conflict_detected' => false,
        ]);

        $primaryEvent->update([
            'is_duplicate_suspect' => false,
            'conflict_detected' => false,
        ]);

        // Update match record
        CalendarEventMatch::updateOrCreate(
            [
                'room_id' => $primaryEvent->room_id,
                'primary_event_id' => min($primaryEvent->id, $secondaryEvent->id),
                'matched_event_id' => max($primaryEvent->id, $secondaryEvent->id),
            ],
            [
                'match_type' => 'user_confirmed_duplicate',
                'reviewed_by_user_id' => $userId ?? auth()->id(),
                'reviewed_at' => now(),
                'notes' => 'Confirmed by user as the same booking imported from multiple feeds. Occupancy counted once.',
            ]
        );
    }

    /**
     * Mark that two overlapping events are distinct, real bookings (a real conflict).
     */
    public function markAsDistinctConflict(CalendarEvent $event1, CalendarEvent $event2, ?int $userId = null): void
    {
        $event1->update([
            'is_duplicate_suspect' => false,
            'is_confirmed_duplicate' => false,
            'conflict_detected' => true,
        ]);

        $event2->update([
            'is_duplicate_suspect' => false,
            'is_confirmed_duplicate' => false,
            'conflict_detected' => true,
        ]);

        CalendarEventMatch::updateOrCreate(
            [
                'room_id' => $event1->room_id,
                'primary_event_id' => min($event1->id, $event2->id),
                'matched_event_id' => max($event1->id, $event2->id),
            ],
            [
                'match_type' => 'user_marked_distinct_conflict',
                'reviewed_by_user_id' => $userId ?? auth()->id(),
                'reviewed_at' => now(),
                'notes' => 'Flagged as genuine scheduling conflict by manager.',
            ]
        );
    }
}
