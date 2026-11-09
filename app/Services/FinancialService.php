<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Property;
use App\Models\SafariBooking;
use App\Models\TransportBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialService
{
    /**
     * Compute financial ledger summaries for a specified period and optional property filter.
     */
    public function getFinancialSummary(Carbon $startDate, Carbon $endDate, ?int $propertyId = null): array
    {
        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();

        // Safaris Query
        $safariQuery = SafariBooking::whereBetween('safari_date', [$startDateStr, $endDateStr])
            ->where('operational_status', '!=', 'cancelled');

        if ($propertyId) {
            $safariQuery->where('property_id', $propertyId);
        }

        $safaris = $safariQuery->get();

        // Transports Query
        $transportQuery = TransportBooking::whereBetween('transport_date', [$startDateStr, $endDateStr])
            ->where('operational_status', '!=', 'cancelled');

        if ($propertyId) {
            $transportQuery->where('property_id', $propertyId);
        }

        $transports = $transportQuery->get();

        // Metrics computation
        $safariRevenue = (float) $safaris->sum('selling_price');
        $safariCostRecorded = (float) $safaris->whereNotNull('operator_cost')->sum('operator_cost');
        $safariCostUnrecordedCount = $safaris->whereNull('operator_cost')->count();
        $safariProfit = (float) $safaris->whereNotNull('profit')->sum('profit');
        $safariPaid = (float) $safaris->sum('amount_paid');
        $safariOutstanding = (float) $safaris->sum('outstanding_amount');

        $transportRevenue = (float) $transports->sum('selling_price');
        $transportCostRecorded = (float) $transports->whereNotNull('driver_cost')->sum('driver_cost');
        $transportCostUnrecordedCount = $transports->whereNull('driver_cost')->count();
        $transportProfit = (float) $transports->whereNotNull('profit')->sum('profit');
        $transportPaid = (float) $transports->sum('amount_paid');
        $transportOutstanding = (float) $transports->sum('outstanding_amount');

        $totalRevenue = $safariRevenue + $transportRevenue;
        $totalCost = $safariCostRecorded + $transportCostRecorded;
        $totalProfit = $safariProfit + $transportProfit;
        $totalPaid = $safariPaid + $transportPaid;
        $totalOutstanding = $safariOutstanding + $transportOutstanding;

        return [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'total_profit' => $totalProfit,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'safari' => [
                'count' => $safaris->count(),
                'revenue' => $safariRevenue,
                'cost' => $safariCostRecorded,
                'profit' => $safariProfit,
                'paid' => $safariPaid,
                'outstanding' => $safariOutstanding,
                'unrecorded_costs_count' => $safariCostUnrecordedCount,
            ],
            'transport' => [
                'count' => $transports->count(),
                'revenue' => $transportRevenue,
                'cost' => $transportCostRecorded,
                'profit' => $transportProfit,
                'paid' => $transportPaid,
                'outstanding' => $transportOutstanding,
                'unrecorded_costs_count' => $transportCostUnrecordedCount,
            ],
            'currency' => 'LKR',
        ];
    }

    /**
     * Get monthly revenue and profit trends for Chart.js.
     */
    public function getMonthlyTrends(int $months = 6, ?int $propertyId = null): array
    {
        $labels = [];
        $safariRevenue = [];
        $transportRevenue = [];
        $totalProfits = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth()->toDateString();
            $endOfMonth = $month->copy()->endOfMonth()->toDateString();

            $labels[] = $month->format('M Y');

            $sQ = SafariBooking::whereBetween('safari_date', [$startOfMonth, $endOfMonth])
                ->where('operational_status', '!=', 'cancelled');
            $tQ = TransportBooking::whereBetween('transport_date', [$startOfMonth, $endOfMonth])
                ->where('operational_status', '!=', 'cancelled');

            if ($propertyId) {
                $sQ->where('property_id', $propertyId);
                $tQ->where('property_id', $propertyId);
            }

            $sRev = (float) $sQ->sum('selling_price');
            $tRev = (float) $tQ->sum('selling_price');
            $sProf = (float) $sQ->whereNotNull('profit')->sum('profit');
            $tProf = (float) $tQ->whereNotNull('profit')->sum('profit');

            $safariRevenue[] = $sRev;
            $transportRevenue[] = $tRev;
            $totalProfits[] = ($sProf + $tProf);
        }

        return [
            'labels' => $labels,
            'safari_revenue' => $safariRevenue,
            'transport_revenue' => $transportRevenue,
            'total_profits' => $totalProfits,
        ];
    }
}
