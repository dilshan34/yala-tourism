<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Property;
use App\Models\Room;
use App\Models\SafariBooking;
use App\Models\TransportBooking;
use App\Services\FinancialService;
use App\Services\OccupancyService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected FinancialService $financialService;
    protected OccupancyService $occupancyService;

    public function __construct(FinancialService $financialService, OccupancyService $occupancyService)
    {
        $this->financialService = $financialService;
        $this->occupancyService = $occupancyService;
    }

    public function index(Request $request)
    {
        $propertyId = $request->filled('property_id') ? (int) $request->property_id : null;
        $timeFilter = $request->input('filter', 'this_month');

        $now = Carbon::now();
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // Calculate Date Range based on filter
        [$startDate, $endDate] = match ($timeFilter) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                Carbon::parse($request->input('start_date', $now->copy()->startOfMonth())),
                Carbon::parse($request->input('end_date', $now->copy()->endOfMonth())),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()], // 'this_month'
        };

        // All active properties for filter dropdown
        $properties = Property::active()->withCount('rooms')->get();

        // 1. KPI Cards
        $totalProperties = $properties->count();
        $totalRooms = Room::active()->when($propertyId, fn($q) => $q->where('property_id', $propertyId))->count();

        // Current Occupied Rooms (Today)
        $currentOccupiedRooms = CalendarEvent::where('check_in_date', '<=', $today->toDateString())
            ->where('check_out_date', '>', $today->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where('is_confirmed_duplicate', false)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->distinct('room_id')
            ->count('room_id');

        // Today's Check-ins & Check-outs
        $todayCheckIns = CalendarEvent::with(['property', 'room'])
            ->whereDate('check_in_date', $today->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where('is_confirmed_duplicate', false)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->get();

        $todayCheckOuts = CalendarEvent::with(['property', 'room'])
            ->whereDate('check_out_date', $today->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where('is_confirmed_duplicate', false)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->get();

        // Upcoming Accommodation Reservations (Next 30 days)
        $upcomingReservations = CalendarEvent::with(['property', 'room'])
            ->where('check_in_date', '>', $today->toDateString())
            ->where('check_in_date', '<=', $today->copy()->addDays(30)->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where('is_confirmed_duplicate', false)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->count();

        // Pending Safaris & Transports
        $pendingSafarisCount = SafariBooking::pending()
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->count();

        $pendingTransportsCount = TransportBooking::pending()
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->count();

        // Financial Metrics for the selected date range
        $finance = $this->financialService->getFinancialSummary($startDate, $endDate, $propertyId);

        // Occupancy calculation for the selected range
        $occupancyData = $this->occupancyService->calculateOccupancy($startDate, $endDate, $propertyId);

        // 2. Today's Operations
        $todaySafaris = SafariBooking::with(['primaryGuest', 'property', 'operator', 'driver'])
            ->whereDate('safari_date', $today->toDateString())
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->orderBy('pickup_time')
            ->get();

        $todayTransports = TransportBooking::with(['primaryGuest', 'property', 'driver', 'vehicle'])
            ->whereDate('transport_date', $today->toDateString())
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->orderBy('pickup_time')
            ->get();

        // 3. Tomorrow's Activities (Most Critical Operational Section)
        $tomorrowCheckIns = CalendarEvent::with(['property', 'room'])
            ->whereDate('check_in_date', $tomorrow->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where('is_confirmed_duplicate', false)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->get();

        $tomorrowCheckOuts = CalendarEvent::with(['property', 'room'])
            ->whereDate('check_out_date', $tomorrow->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where('is_confirmed_duplicate', false)
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->get();

        $tomorrowSafaris = SafariBooking::with(['primaryGuest', 'property', 'operator', 'driver'])
            ->whereDate('safari_date', $tomorrow->toDateString())
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->orderBy('pickup_time')
            ->get();

        $tomorrowTransports = TransportBooking::with(['primaryGuest', 'property', 'driver', 'vehicle'])
            ->whereDate('transport_date', $tomorrow->toDateString())
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->orderBy('pickup_time')
            ->get();

        // 4. Chart Data for Chart.js
        $monthlyTrends = $this->financialService->getMonthlyTrends(6, $propertyId);

        // Service Status distribution
        $safariStatusCounts = [
            'Pending' => SafariBooking::where('operational_status', 'pending')->count(),
            'Confirmed' => SafariBooking::where('operational_status', 'confirmed')->count(),
            'In Progress' => SafariBooking::where('operational_status', 'in_progress')->count(),
            'Completed' => SafariBooking::where('operational_status', 'completed')->count(),
            'Cancelled' => SafariBooking::where('operational_status', 'cancelled')->count(),
        ];

        $transportStatusCounts = [
            'Pending' => TransportBooking::where('operational_status', 'pending')->count(),
            'Confirmed' => TransportBooking::where('operational_status', 'confirmed')->count(),
            'Driver Assigned' => TransportBooking::where('operational_status', 'driver_assigned')->count(),
            'In Progress' => TransportBooking::where('operational_status', 'in_progress')->count(),
            'Completed' => TransportBooking::where('operational_status', 'completed')->count(),
            'Cancelled' => TransportBooking::where('operational_status', 'cancelled')->count(),
        ];

        // Revenue by Property
        $revenueByPropertyLabels = [];
        $revenueByPropertyValues = [];
        foreach ($properties as $prop) {
            $revenueByPropertyLabels[] = $prop->name;
            $sRev = (float) SafariBooking::where('property_id', $prop->id)
                ->whereBetween('safari_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('operational_status', '!=', 'cancelled')
                ->sum('selling_price');
            $tRev = (float) TransportBooking::where('property_id', $prop->id)
                ->whereBetween('transport_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->where('operational_status', '!=', 'cancelled')
                ->sum('selling_price');
            $revenueByPropertyValues[] = $sRev + $tRev;
        }

        // External / No Accommodation Revenue
        $externalSafariRev = (float) SafariBooking::whereNull('property_id')
            ->whereBetween('safari_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('operational_status', '!=', 'cancelled')
            ->sum('selling_price');
        $externalTransportRev = (float) TransportBooking::whereNull('property_id')
            ->whereBetween('transport_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('operational_status', '!=', 'cancelled')
            ->sum('selling_price');

        if (($externalSafariRev + $externalTransportRev) > 0) {
            $revenueByPropertyLabels[] = 'External / Other';
            $revenueByPropertyValues[] = $externalSafariRev + $externalTransportRev;
        }

        return view('dashboard.index', compact(
            'properties',
            'propertyId',
            'timeFilter',
            'startDate',
            'endDate',
            'totalProperties',
            'totalRooms',
            'currentOccupiedRooms',
            'todayCheckIns',
            'todayCheckOuts',
            'upcomingReservations',
            'pendingSafarisCount',
            'pendingTransportsCount',
            'finance',
            'occupancyData',
            'todaySafaris',
            'todayTransports',
            'tomorrowCheckIns',
            'tomorrowCheckOuts',
            'tomorrowSafaris',
            'tomorrowTransports',
            'monthlyTrends',
            'safariStatusCounts',
            'transportStatusCounts',
            'revenueByPropertyLabels',
            'revenueByPropertyValues'
        ));
    }
}
