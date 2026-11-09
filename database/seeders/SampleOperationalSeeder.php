<?php

namespace Database\Seeders;

use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\Driver;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Room;
use App\Models\SafariBooking;
use App\Models\SafariOperator;
use App\Models\TransportBooking;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleOperationalSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $wildPulse = Property::where('slug', 'wild-pulse')->first();
        $yalaBliss = Property::where('slug', 'yala-bliss')->first();
        $roomWp1 = Room::where('property_id', $wildPulse->id)->where('name', 'Room 1')->first();
        $roomWp2 = Room::where('property_id', $wildPulse->id)->where('name', 'Room 2')->first();
        $roomYb1 = Room::where('property_id', $yalaBliss->id)->where('name', 'Room 1')->first();

        $guest1 = Guest::where('phone_number', '+49 170 1234567')->first();
        $guest2 = Guest::where('phone_number', '+33 6 12 34 56 78')->first();
        $guest3 = Guest::where('phone_number', '+44 7911 123456')->first();
        $guest4 = Guest::where('phone_number', '+7 903 123-45-67')->first();

        $operator1 = SafariOperator::first();
        $driver1 = Driver::first();
        $driver2 = Driver::skip(1)->first();
        $vehicle1 = Vehicle::first();
        $vehicle2 = Vehicle::skip(1)->first();

        // 1. Calendar Connections & Sample Imported Events
        if ($roomWp1) {
            $connAirbnb = CalendarConnection::firstOrCreate(
                ['room_id' => $roomWp1->id, 'platform' => 'airbnb'],
                [
                    'property_id' => $wildPulse->id,
                    'calendar_name' => 'Wild Pulse R1 Airbnb Sync',
                    'ical_url' => 'https://www.airbnb.com/calendar/ical/12345678.ics?s=sample_token',
                    'status' => 'active',
                    'last_synced_at' => now()->subMinutes(10),
                    'last_successful_sync_at' => now()->subMinutes(10),
                ]
            );

            // Today's Check-in Event
            CalendarEvent::firstOrCreate(
                ['calendar_connection_id' => $connAirbnb->id, 'source_event_uid' => 'sample_airbnb_ev_01'],
                [
                    'property_id' => $wildPulse->id,
                    'room_id' => $roomWp1->id,
                    'source_platform' => 'airbnb',
                    'summary' => 'Reserved - Airbnb (HM9K4B)',
                    'check_in_date' => $today->toDateString(),
                    'check_out_date' => $today->copy()->addDays(3)->toDateString(),
                    'booked_nights' => 3,
                    'status' => 'confirmed',
                    'last_seen_in_feed_at' => now(),
                ]
            );
        }

        if ($roomWp2) {
            $connBooking = CalendarConnection::firstOrCreate(
                ['room_id' => $roomWp2->id, 'platform' => 'booking_com'],
                [
                    'property_id' => $wildPulse->id,
                    'calendar_name' => 'Wild Pulse R2 Booking.com',
                    'ical_url' => 'https://ical.booking.com/v1/export?t=sample_booking_token_wp2',
                    'status' => 'active',
                    'last_synced_at' => now()->subMinutes(15),
                    'last_successful_sync_at' => now()->subMinutes(15),
                ]
            );

            // Tomorrow's Check-in Event
            CalendarEvent::firstOrCreate(
                ['calendar_connection_id' => $connBooking->id, 'source_event_uid' => 'sample_booking_ev_02'],
                [
                    'property_id' => $wildPulse->id,
                    'room_id' => $roomWp2->id,
                    'source_platform' => 'booking_com',
                    'summary' => 'CLOSED - Booking.com (BKG-4491)',
                    'check_in_date' => $tomorrow->toDateString(),
                    'check_out_date' => $tomorrow->copy()->addDays(2)->toDateString(),
                    'booked_nights' => 2,
                    'status' => 'confirmed',
                    'last_seen_in_feed_at' => now(),
                ]
            );
        }

        // 2. Safari Bookings
        // Safari for Today (Confirmed)
        if ($guest1) {
            $safariToday = SafariBooking::firstOrCreate(
                ['reference_no' => 'SAF-' . $today->format('Ym') . '-0001'],
                [
                    'primary_guest_id' => $guest1->id,
                    'property_id' => $wildPulse->id,
                    'safari_date' => $today->toDateString(),
                    'safari_type' => 'half_day_morning',
                    'safari_category' => 'private',
                    'guest_count' => 2,
                    'pickup_time' => '05:30:00',
                    'pickup_location' => 'Wild Pulse Main Lobby',
                    'national_park_block' => 'Yala Block 1 (Main Gate)',
                    'safari_operator_id' => $operator1 ? $operator1->id : null,
                    'driver_id' => $driver1 ? $driver1->id : null,
                    'driver_name' => $driver1 ? $driver1->name : 'Kamal Perera',
                    'driver_phone' => $driver1 ? $driver1->phone_number : '+94 77 234 5678',
                    'jeep_details' => 'Modified Land Cruiser WP-CA-8899',
                    'selling_price' => 45000.00,
                    'operator_cost' => 28000.00,
                    'amount_paid' => 45000.00,
                    'operational_status' => 'confirmed',
                    'notes' => 'Guest requested early binocular setup and breakfast pack.',
                ]
            );

            Payment::firstOrCreate(
                ['payable_type' => SafariBooking::class, 'payable_id' => $safariToday->id],
                [
                    'amount' => 45000.00,
                    'currency' => 'LKR',
                    'payment_date' => $today->toDateString(),
                    'payment_method' => 'card',
                    'transaction_reference' => 'CARD-TX-9921',
                    'notes' => 'Paid via POS terminal at front desk',
                ]
            );
        }

        // Safari for Tomorrow (Pending Confirmation - Operational Reminder trigger)
        if ($guest2) {
            $safariTomorrow = SafariBooking::firstOrCreate(
                ['reference_no' => 'SAF-' . $today->format('Ym') . '-0002'],
                [
                    'primary_guest_id' => $guest2->id,
                    'property_id' => $yalaBliss->id,
                    'safari_date' => $tomorrow->toDateString(),
                    'safari_type' => 'full_day',
                    'safari_category' => 'private',
                    'guest_count' => 2,
                    'pickup_time' => '05:00:00',
                    'pickup_location' => 'Yala Bliss Cottage Verandah',
                    'national_park_block' => 'Yala Block 1 & 2',
                    'safari_operator_id' => $operator1 ? $operator1->id : null,
                    'driver_id' => $driver1 ? $driver1->id : null,
                    'driver_name' => $driver1 ? $driver1->name : 'Kamal Perera',
                    'driver_phone' => $driver1 ? $driver1->phone_number : '+94 77 234 5678',
                    'jeep_details' => 'Toyota Hilux 4x4 Custom',
                    'selling_price' => 75000.00,
                    'operator_cost' => 48000.00,
                    'amount_paid' => 30000.00, // Partially paid
                    'operational_status' => 'pending', // Pending reminder
                    'notes' => 'Full day packed lunch arranged with organic juice.',
                ]
            );

            Payment::firstOrCreate(
                ['payable_type' => SafariBooking::class, 'payable_id' => $safariTomorrow->id],
                [
                    'amount' => 30000.00,
                    'currency' => 'LKR',
                    'payment_date' => $today->toDateString(),
                    'payment_method' => 'bank_transfer',
                    'transaction_reference' => 'BFT-883921',
                    'notes' => 'Advance deposit received via Commercial Bank',
                ]
            );
        }

        // 3. Transport Bookings
        // Transport for Tomorrow (Airport Transfer from Bandaranaike Intl Airport)
        if ($guest3) {
            $transportTomorrow = TransportBooking::firstOrCreate(
                ['reference_no' => 'TRN-' . $today->format('Ym') . '-0001'],
                [
                    'primary_guest_id' => $guest3->id,
                    'property_id' => $wildPulse->id,
                    'transport_date' => $tomorrow->toDateString(),
                    'pickup_time' => '11:30:00',
                    'pickup_location' => 'Bandaranaike International Airport (CMB) Arrival Hall',
                    'drop_off_location' => 'Wild Pulse Eco-Lodge, Yala',
                    'additional_stops' => 'Lunch stop at Southern Expressway Welipenna Service Area',
                    'transport_type' => 'airport_pickup',
                    'driver_id' => $driver2 ? $driver2->id : null,
                    'vehicle_id' => $vehicle1 ? $vehicle1->id : null,
                    'driver_name' => $driver2 ? $driver2->name : 'Nimal Silva',
                    'driver_phone' => $driver2 ? $driver2->phone_number : '+94 76 345 6789',
                    'selling_price' => 52000.00,
                    'driver_cost' => 34000.00,
                    'amount_paid' => 0.00, // Unpaid
                    'operational_status' => 'confirmed',
                    'notes' => 'Flight UL504 from London Heathrow arriving 10:45 AM. Driver has placard.',
                ]
            );
        }

        // External Transport (No Accommodation)
        if ($guest4) {
            $transportExternal = TransportBooking::firstOrCreate(
                ['reference_no' => 'TRN-' . $today->format('Ym') . '-0002'],
                [
                    'primary_guest_id' => $guest4->id,
                    'property_id' => null, // External
                    'custom_property_name' => 'Jetwing Yala',
                    'transport_date' => $today->toDateString(),
                    'pickup_time' => '14:00:00',
                    'pickup_location' => 'Jetwing Yala Lobby',
                    'drop_off_location' => 'Ella Train Station',
                    'additional_stops' => 'Ravana Waterfall photo stop',
                    'transport_type' => 'hotel_transfer',
                    'driver_id' => $driver1 ? $driver1->id : null,
                    'vehicle_id' => $vehicle2 ? $vehicle2->id : null,
                    'driver_name' => $driver1 ? $driver1->name : 'Kamal Perera',
                    'driver_phone' => $driver1 ? $driver1->phone_number : '+94 77 234 5678',
                    'selling_price' => 38000.00,
                    'driver_cost' => 24000.00,
                    'amount_paid' => 38000.00,
                    'operational_status' => 'completed',
                    'notes' => 'Smooth ride, passenger tipped driver.',
                ]
            );

            Payment::firstOrCreate(
                ['payable_type' => TransportBooking::class, 'payable_id' => $transportExternal->id],
                [
                    'amount' => 38000.00,
                    'currency' => 'LKR',
                    'payment_date' => $today->toDateString(),
                    'payment_method' => 'cash',
                    'transaction_reference' => 'CASH-COLLECTED',
                    'notes' => 'Collected in full by driver in cash',
                ]
            );
        }
    }
}
