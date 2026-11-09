<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomUnavailability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class OccupancyService
{
    /**
     * Calculate occupancy metrics for a property or room across a date range.
     */
    public function calculateOccupancy(Carbon $startDate, Carbon $endDate, ?int $propertyId = null, ?int $roomId = null): array
    {
        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->endOfDay();

        // Calculate days in period
        $periodDays = $startDate->diffInDays($endDate->copy()->startOfDay()) + 1;
        if ($periodDays <= 0) {
            $periodDays = 1;
        }

        // Rooms query
        $roomsQuery = Room::active();
        if ($roomId) {
            $roomsQuery->where('id', $roomId);
        } elseif ($propertyId) {
            $roomsQuery->where('property_id', $propertyId);
        }
        $rooms = $roomsQuery->with('property')->get();
        $activeRoomCount = $rooms->count();

        if ($activeRoomCount === 0) {
            return [
                'total_rooms' => 0,
                'available_room_nights' => 0,
                'booked_room_nights' => 0,
                'maintenance_room_nights' => 0,
                'occupancy_percentage' => 0.0,
                'potential_duplicates_count' => 0,
                'active_reservations_count' => 0,
                'room_breakdown' => [],
            ];
        }

        $totalAvailableNights = $activeRoomCount * $periodDays;
        $totalMaintenanceNights = 0;
        $totalBookedNights = 0;
        $potentialDuplicatesCount = 0;
        $activeReservationsCount = 0;

        $roomBreakdown = [];

        foreach ($rooms as $room) {
            // Maintenance nights for this room in range
            $unavailabilities = RoomUnavailability::where('room_id', $room->id)
                ->where('start_date', '<=', $endDate->toDateString())
                ->where('end_date', '>=', $startDate->toDateString())
                ->get();

            $roomMaintenanceNights = 0;
            foreach ($unavailabilities as $unavail) {
                $uStart = Carbon::parse($unavail->start_date)->max($startDate);
                $uEnd = Carbon::parse($unavail->end_date)->min($endDate->copy()->addDay());
                // Exclusive end date
                $nights = $uStart->diffInDays($uEnd);
                $roomMaintenanceNights += max(0, $nights);
            }
            $totalMaintenanceNights += $roomMaintenanceNights;

            // Events query for this room in range
            // Only countable events (excluding user-confirmed duplicates so they don't double count)
            $events = CalendarEvent::where('room_id', $room->id)
                ->where('check_in_date', '<', $endDate->toDateString())
                ->where('check_out_date', '>', $startDate->toDateString())
                ->where('status', '!=', 'cancelled')
                ->get();

            $roomBookedNights = 0;
            $roomReservations = 0;

            foreach ($events as $event) {
                if ($event->is_duplicate_suspect) {
                    $potentialDuplicatesCount++;
                }

                // If confirmed duplicate, do not count in occupancy
                if ($event->is_confirmed_duplicate) {
                    continue;
                }

                $roomReservations++;

                // Calculate booked nights falling within the selected window
                $eStart = Carbon::parse($event->check_in_date)->max($startDate);
                $eEnd = Carbon::parse($event->check_out_date)->min($endDate->copy()->addDay());

                // Exclusive checkout convention: checkout day is not occupied
                $occupiedNights = $eStart->diffInDays($eEnd);
                if ($occupiedNights > 0) {
                    $roomBookedNights += $occupiedNights;
                }
            }

            $totalBookedNights += $roomBookedNights;
            $activeReservationsCount += $roomReservations;

            $roomNetAvailable = max(1, $periodDays - $roomMaintenanceNights);
            $roomOccupancyPct = min(100.0, round(($roomBookedNights / $roomNetAvailable) * 100, 1));

            $roomBreakdown[] = [
                'room_id' => $room->id,
                'room_name' => $room->name,
                'property_name' => $room->property->name,
                'property_color' => $room->property->color_hex ?? '#2563EB',
                'booked_nights' => $roomBookedNights,
                'available_nights' => $roomNetAvailable,
                'maintenance_nights' => $roomMaintenanceNights,
                'occupancy_percentage' => $roomOccupancyPct,
            ];
        }

        $netAvailableNights = max(1, $totalAvailableNights - $totalMaintenanceNights);
        $overallOccupancyPercentage = min(100.0, round(($totalBookedNights / $netAvailableNights) * 100, 1));

        return [
            'total_rooms' => $activeRoomCount,
            'period_days' => $periodDays,
            'available_room_nights' => $netAvailableNights,
            'booked_room_nights' => $totalBookedNights,
            'maintenance_room_nights' => $totalMaintenanceNights,
            'occupancy_percentage' => $overallOccupancyPercentage,
            'potential_duplicates_count' => (int) ($potentialDuplicatesCount / 2), // Halved because suspect flags both sides
            'active_reservations_count' => $activeReservationsCount,
            'room_breakdown' => $roomBreakdown,
        ];
    }
}
