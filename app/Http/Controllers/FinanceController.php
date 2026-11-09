<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Property;
use App\Models\SafariBooking;
use App\Models\TransportBooking;
use App\Services\FinancialService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    protected FinancialService $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function index(Request $request)
    {
        $timeFilter = $request->input('filter', 'this_month');
        $propertyId = $request->filled('property_id') ? (int) $request->property_id : null;

        $now = Carbon::now();
        $today = Carbon::today();

        [$startDate, $endDate] = match ($timeFilter) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                Carbon::parse($request->input('start_date', $now->copy()->startOfMonth())),
                Carbon::parse($request->input('end_date', $now->copy()->endOfMonth())),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        $summary = $this->financialService->getFinancialSummary($startDate, $endDate, $propertyId);

        // Recent Payments Ledger
        $payments = Payment::with(['payable', 'recordedBy'])
            ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->latest('payment_date')
            ->paginate(20);

        // Outstanding Receivables List
        $outstandingSafaris = SafariBooking::with('primaryGuest', 'property')
            ->where('outstanding_amount', '>', 0)
            ->where('operational_status', '!=', 'cancelled')
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->orderByDesc('outstanding_amount')
            ->get();

        $outstandingTransports = TransportBooking::with('primaryGuest', 'property')
            ->where('outstanding_amount', '>', 0)
            ->where('operational_status', '!=', 'cancelled')
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->orderByDesc('outstanding_amount')
            ->get();

        $properties = Property::active()->get();

        return view('finances.index', compact(
            'summary',
            'payments',
            'outstandingSafaris',
            'outstandingTransports',
            'properties',
            'timeFilter',
            'propertyId',
            'startDate',
            'endDate'
        ));
    }
}
