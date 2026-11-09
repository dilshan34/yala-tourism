<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomUnavailability;
use App\Models\SafariBooking;
use App\Models\TransportBooking;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $properties = Property::active()->with('rooms')->get();
        return view('calendar.index', compact('properties'));
    }

    /**
     * API JSON endpoint consumed by FullCalendar.js
     */
    public function events(Request $request): JsonResponse
    {
        $start = $request->query('start');
        $end = $request->query('end');

        $propertyId = $request->query('property_id');
        $roomId = $request->query('room_id');
        $platform = $request->query('platform');
        $serviceType = $request->query('service_type'); // 'all', 'accommodation', 'safari', 'transport', 'maintenance'
        $statusFilter = $request->query('status'); // 'all', 'pending', 'completed'

        $events = [];

        // 1. Accommodation Events (from iCal)
        if (in_array($serviceType, [null, 'all', 'accommodation'], true)) {
            $calEventsQuery = CalendarEvent::with(['property', 'room', 'connection'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($start, $end) {
                    $q->where('check_in_date', '<', $end)
                      ->where('check_out_date', '>', $start);
                });

            if ($propertyId) {
                $calEventsQuery->where('property_id', $propertyId);
            }
            if ($roomId) {
                $calEventsQuery->where('room_id', $roomId);
            }
            if ($platform && $platform !== 'all') {
                $calEventsQuery->where('source_platform', $platform);
            }

            foreach ($calEventsQuery->get() as $event) {
                $propSlug = $event->property->slug ?? '';
                $color = match ($propSlug) {
                    'wild-pulse' => '#2563EB', // Blue
                    'yala-bliss' => '#9333EA',  // Purple
                    default => '#0D9488',       // Teal
                };

                $platformLabel = match ($event->source_platform) {
                    'airbnb' => 'Airbnb',
                    'booking_com' => 'Booking.com',
                    default => 'iCal Feed',
                };

                $title = "{$event->property->name} - {$event->room->name} ({$platformLabel})";
                if ($event->is_confirmed_duplicate) {
                    $title .= " [Duplicate Sync]";
                } elseif ($event->conflict_detected) {
                    $title .= " ⚠️ [Conflict]";
                }

                $events[] = [
                    'id' => 'acc_' . $event->id,
                    'title' => $title,
                    'start' => $event->check_in_date->toDateString(),
                    'end' => $event->check_out_date->toDateString(), // FullCalendar handles exclusive end date automatically for all-day events
                    'allDay' => true,
                    'backgroundColor' => $event->conflict_detected ? '#DC2626' : $color,
                    'borderColor' => $event->is_duplicate_suspect ? '#F59E0B' : $color,
                    'textColor' => '#FFFFFF',
                    'extendedProps' => [
                        'type' => 'accommodation',
                        'event_id' => $event->id,
                        'property_name' => $event->property->name ?? '',
                        'room_name' => $event->room->name ?? '',
                        'platform' => $platformLabel,
                        'summary' => $event->summary,
                        'check_in' => $event->check_in_date->format('d M Y'),
                        'check_out' => $event->check_out_date->format('d M Y'),
                        'booked_nights' => $event->booked_nights,
                        'status' => ucfirst($event->status),
                        'is_duplicate' => $event->is_confirmed_duplicate,
                        'conflict' => $event->conflict_detected,
                        'connection_name' => $event->connection->calendar_name ?? '',
                    ],
                ];
            }
        }

        // 2. Room Maintenance Unavailabilities
        if (in_array($serviceType, [null, 'all', 'maintenance'], true)) {
            $unavailQuery = RoomUnavailability::with(['room.property'])
                ->where('start_date', '<', $end)
                ->where('end_date', '>', $start);

            if ($propertyId) {
                $unavailQuery->whereHas('room', fn($q) => $q->where('property_id', $propertyId));
            }
            if ($roomId) {
                $unavailQuery->where('room_id', $roomId);
            }

            foreach ($unavailQuery->get() as $unavail) {
                $events[] = [
                    'id' => 'maint_' . $unavail->id,
                    'title' => "[Maintenance] {$unavail->room->property->name} - {$unavail->room->name}: {$unavail->reason}",
                    'start' => $unavail->start_date->toDateString(),
                    'end' => $unavail->end_date->toDateString(),
                    'allDay' => true,
                    'backgroundColor' => '#64748B', // Grey
                    'borderColor' => '#475569',
                    'textColor' => '#FFFFFF',
                    'extendedProps' => [
                        'type' => 'maintenance',
                        'property_name' => $unavail->room->property->name ?? '',
                        'room_name' => $unavail->room->name ?? '',
                        'reason' => $unavail->reason,
                        'start_date' => $unavail->start_date->format('d M Y'),
                        'end_date' => $unavail->end_date->format('d M Y'),
                    ],
                ];
            }
        }

        // 3. Safari Bookings
        if (in_array($serviceType, [null, 'all', 'safari'], true)) {
            $safarisQuery = SafariBooking::with(['primaryGuest', 'property', 'operator', 'driver'])
                ->where('safari_date', '>=', substr($start, 0, 10))
                ->where('safari_date', '<=', substr($end, 0, 10));

            if ($propertyId) {
                $safarisQuery->where('property_id', $propertyId);
            }
            if ($statusFilter === 'pending') {
                $safarisQuery->whereIn('operational_status', ['pending', 'confirmed', 'in_progress']);
            } elseif ($statusFilter === 'completed') {
                $safarisQuery->where('operational_status', 'completed');
            }

            foreach ($safarisQuery->get() as $safari) {
                $color = match ($safari->operational_status) {
                    'cancelled' => '#E11D48', // Red
                    'completed' => '#059669', // Emerald
                    'in_progress' => '#10B981',
                    default => '#10B981',    // Safari Green
                };

                $events[] = [
                    'id' => 'safari_' . $safari->id,
                    'title' => "🦁 Safari: {$safari->primaryGuest->full_name} ({$safari->pickup_time})",
                    'start' => $safari->safari_date->toDateString() . 'T' . $safari->pickup_time,
                    'allDay' => false,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#FFFFFF',
                    'extendedProps' => [
                        'type' => 'safari',
                        'id' => $safari->id,
                        'reference_no' => $safari->reference_no,
                        'guest_name' => $safari->primaryGuest->full_name,
                        'guest_phone' => $safari->primaryGuest->phone_number,
                        'property_name' => $safari->effective_property_name,
                        'safari_date' => $safari->safari_date->format('d M Y'),
                        'pickup_time' => $safari->pickup_time,
                        'pickup_location' => $safari->pickup_location,
                        'safari_type' => ucwords(str_replace('_', ' ', $safari->safari_type)),
                        'safari_category' => ucfirst($safari->safari_category),
                        'guest_count' => $safari->guest_count,
                        'operator' => $safari->operator->name ?? 'Unassigned',
                        'driver' => $safari->driver_name ?? ($safari->driver->name ?? 'Unassigned'),
                        'driver_phone' => $safari->driver_phone ?? ($safari->driver->phone_number ?? 'N/A'),
                        'selling_price' => number_format($safari->selling_price, 2) . ' LKR',
                        'amount_paid' => number_format($safari->amount_paid, 2) . ' LKR',
                        'payment_status' => ucfirst(str_replace('_', ' ', $safari->payment_status)),
                        'operational_status' => ucfirst(str_replace('_', ' ', $safari->operational_status)),
                        'view_url' => route('safaris.show', $safari->id),
                    ],
                ];
            }
        }

        // 4. Transport Bookings
        if (in_array($serviceType, [null, 'all', 'transport'], true)) {
            $transportsQuery = TransportBooking::with(['primaryGuest', 'property', 'driver', 'vehicle'])
                ->where('transport_date', '>=', substr($start, 0, 10))
                ->where('transport_date', '<=', substr($end, 0, 10));

            if ($propertyId) {
                $transportsQuery->where('property_id', $propertyId);
            }
            if ($statusFilter === 'pending') {
                $transportsQuery->whereIn('operational_status', ['pending', 'confirmed', 'driver_assigned', 'in_progress']);
            } elseif ($statusFilter === 'completed') {
                $transportsQuery->where('operational_status', 'completed');
            }

            foreach ($transportsQuery->get() as $transport) {
                $color = match ($transport->operational_status) {
                    'cancelled' => '#E11D48', // Red
                    'completed' => '#D97706', // Orange Amber
                    default => '#F59E0B',    // Amber
                };

                $events[] = [
                    'id' => 'transport_' . $transport->id,
                    'title' => "🚐 Transport: {$transport->primaryGuest->full_name} ({$transport->pickup_location})",
                    'start' => $transport->transport_date->toDateString() . 'T' . $transport->pickup_time,
                    'allDay' => false,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'textColor' => '#FFFFFF',
                    'extendedProps' => [
                        'type' => 'transport',
                        'id' => $transport->id,
                        'reference_no' => $transport->reference_no,
                        'guest_name' => $transport->primaryGuest->full_name,
                        'guest_phone' => $transport->primaryGuest->phone_number,
                        'property_name' => $transport->effective_property_name,
                        'transport_date' => $transport->transport_date->format('d M Y'),
                        'pickup_time' => $transport->pickup_time,
                        'pickup_location' => $transport->pickup_location,
                        'drop_off_location' => $transport->drop_off_location,
                        'transport_type' => ucwords(str_replace('_', ' ', $transport->transport_type)),
                        'driver' => $transport->driver_name ?? ($transport->driver->name ?? 'Unassigned'),
                        'driver_phone' => $transport->driver_phone ?? ($transport->driver->phone_number ?? 'N/A'),
                        'vehicle' => $transport->vehicle->registration_number ?? 'Unassigned',
                        'selling_price' => number_format($transport->selling_price, 2) . ' LKR',
                        'amount_paid' => number_format($transport->amount_paid, 2) . ' LKR',
                        'payment_status' => ucfirst(str_replace('_', ' ', $transport->payment_status)),
                        'operational_status' => ucfirst(str_replace('_', ' ', $transport->operational_status)),
                        'view_url' => route('transports.show', $transport->id),
                    ],
                ];
            }
        }

        return response()->json($events);
    }
}
