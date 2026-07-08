<?php

namespace App\Http\Services;

use App\Models\Booking;
use App\Models\Master;
use App\Models\MasterBay;
use Carbon\Carbon;

/**
 * Web-only finance dashboard aggregation for a master, over an arbitrary
 * date range. Mirrors the aggregation logic of the mobile app's
 * `_FinanceViewData.fromSnapshot` (carbeat_mobile/lib/features/master_finance/
 * presentation/master_finance_page.dart), which only ever aggregates a single
 * business day's snapshot. This service reads directly from `bookings` for a
 * `from`..`to` range instead, since the web dashboard supports an arbitrary
 * period. All inputs (total_amount, paid_amount, financial_status,
 * crm_payment_method, bay_id / technician_name) already exist on
 * Booking/MasterBay — no schema changes were needed to reach parity with the
 * mobile aggregation.
 */
class MasterFinanceService
{
    public function buildReport(Master $master, string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $bays = MasterBay::where('master_id', $master->id)->get()->keyBy('id');

        $bookings = Booking::where('master_id', $master->id)
            ->whereNotNull('crm_uuid')
            ->where('status', '!=', 'cancelled')
            ->whereBetween('start_time', [$fromDate, $toDate])
            ->get();

        $totalRevenue = 0;
        $paidRevenue = 0;
        $cashRevenue = 0;
        $cardRevenue = 0;
        $qrRevenue = 0;
        $partialOrders = 0;
        $partialOutstanding = 0;
        $pendingOrders = 0;
        $pendingAmount = 0;
        $debtOrders = 0;
        $debtAmount = 0;
        $completedOrders = 0;
        $pricedOrders = 0;

        /** @var array<string, int> $revenueByBay */
        $revenueByBay = [];
        /** @var array<string, int> $revenueByTechnician */
        $revenueByTechnician = [];

        foreach ($bookings as $booking) {
            $total = (int) ($booking->total_amount ?? 0);
            if ($total <= 0) {
                continue;
            }

            $pricedOrders++;
            $totalRevenue += $total;

            $paid = (int) ($booking->paid_amount ?? 0);
            $resolvedPaid = max(0, min($paid, $total));
            $due = max(0, $total - $resolvedPaid);
            $paidRevenue += $resolvedPaid;

            $bay = $booking->bay_id ? $bays->get($booking->bay_id) : null;
            $bayTitle = $bay->title ?? '—';
            $technicianName = $bay->technician_name ?: '—';

            $revenueByBay[$bayTitle] = ($revenueByBay[$bayTitle] ?? 0) + $total;
            $revenueByTechnician[$technicianName] = ($revenueByTechnician[$technicianName] ?? 0) + $total;

            switch ($booking->crm_payment_method) {
                case 'cash':
                    $cashRevenue += $resolvedPaid;
                    break;
                case 'card':
                    $cardRevenue += $resolvedPaid;
                    break;
                case 'qr':
                    $qrRevenue += $resolvedPaid;
                    break;
            }

            switch ($booking->financial_status) {
                case 'partial':
                    $partialOrders++;
                    $partialOutstanding += $due;
                    break;
                case 'pending':
                    $pendingOrders++;
                    $pendingAmount += $total;
                    break;
                case 'debt':
                    $debtOrders++;
                    $debtAmount += $due;
                    break;
                case 'paid':
                    $completedOrders++;
                    break;
            }
        }

        $outstandingRevenue = max(0, $totalRevenue - $paidRevenue);
        $averageCheck = $pricedOrders === 0 ? 0 : round($totalRevenue / $pricedOrders, 2);

        arsort($revenueByBay);
        arsort($revenueByTechnician);

        $topBay = array_key_first($revenueByBay);
        $topTechnician = array_key_first($revenueByTechnician);

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'cash' => [
                'cashRevenue' => $cashRevenue,
                'cardRevenue' => $cardRevenue,
                'qrRevenue' => $qrRevenue,
                'partialOrders' => $partialOrders,
                'partialOutstanding' => $partialOutstanding,
                'pendingOrders' => $pendingOrders,
                'pendingAmount' => $pendingAmount,
                'debtOrders' => $debtOrders,
                'debtAmount' => $debtAmount,
                'paidRevenue' => $paidRevenue,
                'outstandingRevenue' => $outstandingRevenue,
                'totalRevenue' => $totalRevenue,
            ],
            'profitability' => [
                'totalRevenue' => $totalRevenue,
                'completedOrders' => $completedOrders,
                'averageCheck' => $averageCheck,
                'topBayLabel' => $topBay ?? '—',
                'topBayRevenue' => $topBay !== null ? $revenueByBay[$topBay] : 0,
                'topTechnicianLabel' => $topTechnician ?? '—',
                'topTechnicianRevenue' => $topTechnician !== null ? $revenueByTechnician[$topTechnician] : 0,
                'revenueByBay' => $revenueByBay,
                'revenueByTechnician' => $revenueByTechnician,
            ],
            'kpi' => [
                'completedOrders' => $completedOrders,
                'averageCheck' => $averageCheck,
                'partialOrders' => $partialOrders,
                'partialOutstanding' => $partialOutstanding,
                'debtOrders' => $debtOrders,
                'debtAmount' => $debtAmount,
                'revenueByTechnician' => $revenueByTechnician,
            ],
        ];
    }
}
