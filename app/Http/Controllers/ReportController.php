<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Payment;
use App\Models\Property;
use App\Models\SafariBooking;
use App\Models\TransportBooking;
use App\Services\FinancialService;
use App\Services\OccupancyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
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
        $reportType = $request->input('type', 'monthly');
        $propertyId = $request->filled('property_id') ? (int) $request->property_id : null;
        $now = Carbon::now();

        [$startDate, $endDate] = match ($reportType) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [
                Carbon::parse($request->input('start_date', $now->copy()->startOfMonth())),
                Carbon::parse($request->input('end_date', $now->copy()->endOfMonth())),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };

        $properties = Property::active()->get();
        $finance = $this->financialService->getFinancialSummary($startDate, $endDate, $propertyId);
        $occupancy = $this->occupancyService->calculateOccupancy($startDate, $endDate, $propertyId);

        $safaris = SafariBooking::with(['primaryGuest', 'property', 'operator', 'driver'])
            ->whereBetween('safari_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->orderBy('safari_date')
            ->get();

        $transports = TransportBooking::with(['primaryGuest', 'property', 'driver', 'vehicle'])
            ->whereBetween('transport_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->orderBy('transport_date')
            ->get();

        return view('reports.index', compact(
            'reportType',
            'properties',
            'propertyId',
            'startDate',
            'endDate',
            'finance',
            'occupancy',
            'safaris',
            'transports'
        ));
    }

    /**
     * Export reports to CSV/Excel stream
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $propertyId = $request->filled('property_id') ? (int) $request->property_id : null;
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()))->toDateString();
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()))->toDateString();

        $filename = "tourism_financial_report_{$startDate}_to_{$endDate}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($startDate, $endDate, $propertyId) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['Tourism Business Management System - Financial & Operational Report']);
            fputcsv($handle, ['Reporting Period:', "{$startDate} to {$endDate}"]);
            fputcsv($handle, []);

            // 1. SAFARIS SECTION
            fputcsv($handle, ['=== SAFARI SERVICES ===']);
            fputcsv($handle, ['Reference', 'Date', 'Guest', 'Phone', 'Property', 'Type', 'Category', 'Guests', 'Selling Price (LKR)', 'Cost (LKR)', 'Profit (LKR)', 'Paid (LKR)', 'Outstanding (LKR)', 'Status']);

            $safaris = SafariBooking::with(['primaryGuest', 'property'])
                ->whereBetween('safari_date', [$startDate, $endDate])
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->orderBy('safari_date')
                ->get();

            foreach ($safaris as $s) {
                fputcsv($handle, [
                    $s->reference_no,
                    $s->safari_date->format('Y-m-d'),
                    $s->primaryGuest->full_name,
                    $s->primaryGuest->phone_number,
                    $s->effective_property_name,
                    $s->safari_type,
                    $s->safari_category,
                    $s->guest_count,
                    $s->selling_price,
                    $s->operator_cost ?? 'Unrecorded',
                    $s->profit ?? 'Unrecorded',
                    $s->amount_paid,
                    $s->outstanding_amount,
                    $s->operational_status,
                ]);
            }

            fputcsv($handle, []);

            // 2. TRANSPORTS SECTION
            fputcsv($handle, ['=== TRANSPORT SERVICES ===']);
            fputcsv($handle, ['Reference', 'Date', 'Guest', 'Phone', 'Property', 'Route', 'Type', 'Selling Price (LKR)', 'Cost (LKR)', 'Profit (LKR)', 'Paid (LKR)', 'Outstanding (LKR)', 'Status']);

            $transports = TransportBooking::with(['primaryGuest', 'property'])
                ->whereBetween('transport_date', [$startDate, $endDate])
                ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                ->orderBy('transport_date')
                ->get();

            foreach ($transports as $t) {
                fputcsv($handle, [
                    $t->reference_no,
                    $t->transport_date->format('Y-m-d'),
                    $t->primaryGuest->full_name,
                    $t->primaryGuest->phone_number,
                    $t->effective_property_name,
                    "{$t->pickup_location} -> {$t->drop_off_location}",
                    $t->transport_type,
                    $t->selling_price,
                    $t->driver_cost ?? 'Unrecorded',
                    $t->profit ?? 'Unrecorded',
                    $t->amount_paid,
                    $t->outstanding_amount,
                    $t->operational_status,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Printable PDF / Clean View
     */
    public function exportPdf(Request $request)
    {
        $propertyId = $request->filled('property_id') ? (int) $request->property_id : null;
        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()->endOfMonth()));

        $finance = $this->financialService->getFinancialSummary($startDate, $endDate, $propertyId);
        $occupancy = $this->occupancyService->calculateOccupancy($startDate, $endDate, $propertyId);

        $safaris = SafariBooking::with(['primaryGuest', 'property'])
            ->whereBetween('safari_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->get();

        $transports = TransportBooking::with(['primaryGuest', 'property'])
            ->whereBetween('transport_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
            ->get();

        return view('reports.print', compact('startDate', 'endDate', 'finance', 'occupancy', 'safaris', 'transports'));
    }
}
