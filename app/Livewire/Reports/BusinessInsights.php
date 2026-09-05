<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusinessInsights extends Component
{
    public $lang;
    public $from_date;
    public $to_date;
    public $granularity = 'monthly';

    public function updatedGranularity($value)
    {
        $now = \Carbon\Carbon::now();
        switch ($value) {
            case 'daily':
                $this->from_date = $now->copy()->startOfDay()->toDateString();
                $this->to_date = $now->copy()->endOfDay()->toDateString();
                break;
            case 'weekly':
                $this->from_date = $now->copy()->startOfWeek()->toDateString();
                $this->to_date = $now->copy()->endOfWeek()->toDateString();
                break;
            case 'monthly':
                $this->from_date = $now->copy()->startOfMonth()->toDateString();
                $this->to_date = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'yearly':
                $this->from_date = $now->copy()->startOfYear()->toDateString();
                $this->to_date = $now->copy()->endOfYear()->toDateString();
                break;
        }
        $this->generateInsights();
    }

    public $operationsHealth = [];
    public $businessHealth = [];
    public $staffPerformance = [];
    public $monthlyRevenueTrend = [];

    #[Title('Business Insights')]
    public function render()
    {
        return view('livewire.reports.business-insights');
    }

    public function mount()
    {
        if (!\Illuminate\Support\Facades\Gate::allows('report_insights')) {
            abort(404);
        }

        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }

        // Default to 'This Month' for performance
        $this->from_date = Carbon::now()->startOfMonth()->toDateString();
        $this->to_date = Carbon::now()->endOfMonth()->toDateString();

        $this->generateInsights();
    }

    public function updated($name, $value)
    {
        if (in_array($name, ['from_date', 'to_date'])) {
            $this->generateInsights();
        }
    }

    public function generateInsights()
    {
        $daysDiff = Carbon::parse($this->from_date)->diffInDays(Carbon::parse($this->to_date)) + 1;
        $prev_from_date = Carbon::parse($this->from_date)->subDays($daysDiff)->toDateString();
        $prev_to_date = Carbon::parse($this->to_date)->subDays($daysDiff)->toDateString();

        // 1. Operations Health
        $orders = DB::table('orders')
            ->select('id', 'status', 'order_date', 'delivery_date', 'updated_at')
            ->whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->whereNull('deleted_at')
            ->get();

        $totalOrders = $orders->count();
        $delivered = $orders->where('status', 3);
        $deliveredCount = $delivered->count();

        $onTimeCount = 0;
        $totalTatDays = 0;
        foreach ($delivered as $o) {
            $tatDays = Carbon::parse($o->order_date)->diffInDays(Carbon::parse($o->updated_at));
            $totalTatDays += $tatDays;
            if ($o->delivery_date && Carbon::parse($o->updated_at)->startOfDay()->lte(Carbon::parse($o->delivery_date)->startOfDay())) {
                $onTimeCount++;
            }
        }

        $onTimePct = $deliveredCount > 0 ? round(($onTimeCount / $deliveredCount) * 100, 1) : 0;
        $avgTat = $deliveredCount > 0 ? round($totalTatDays / $deliveredCount, 1) : 0;

        $delayed = $orders->filter(function ($o) {
            return $o->delivery_date 
                && Carbon::parse($o->delivery_date)->endOfDay()->isPast() 
                && !in_array($o->status, [3, 4]);
        })->count();

        $returnCount = $orders->where('status', 4)->count();
        $returnRate = $totalOrders > 0 ? round(($returnCount / $totalOrders) * 100, 1) : 0;

        $this->operationsHealth = [
            'avg_tat' => $avgTat,
            'on_time_pct' => $onTimePct,
            'return_rate' => $returnRate,
            'overdue_count' => $delayed,
        ];

        // 2. Business Health
        $salesOrders = DB::table('orders')
            ->whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->where('status', 3)
            ->whereNull('deleted_at')
            ->get();
        $totalSales = $salesOrders->sum('total');
        $salesCount = $salesOrders->count();

        $prevSalesOrders = DB::table('orders')
            ->whereDate('order_date', '>=', $prev_from_date)
            ->whereDate('order_date', '<=', $prev_to_date)
            ->where('status', 3)
            ->whereNull('deleted_at')
            ->get();
        $prevTotalSales = $prevSalesOrders->sum('total');
        $prevSalesCount = $prevSalesOrders->count();

        $aov = $salesCount > 0 ? $totalSales / $salesCount : 0;
        $prevAov = $prevSalesCount > 0 ? $prevTotalSales / $prevSalesCount : 0;
        $aovTrendUp = $aov >= $prevAov;

        $totalPayments = DB::table('payments')
            ->whereDate('payment_date', '>=', $this->from_date)
            ->whereDate('payment_date', '<=', $this->to_date)
            ->sum('received_amount');

        $collectionRate = $totalSales > 0 ? round(($totalPayments / $totalSales) * 100, 1) : 0;

        $totalExpenses = DB::table('expenses')
            ->whereDate('expense_date', '>=', $this->from_date)
            ->whereDate('expense_date', '<=', $this->to_date)
            ->sum('expense_amount');

        $netCashPosition = $totalPayments - $totalExpenses;

        $newCustomersCount = DB::table('customers')
            ->whereDate('created_at', '>=', $this->from_date)
            ->whereDate('created_at', '<=', $this->to_date)
            ->count();

        $this->businessHealth = [
            'collection_rate' => $collectionRate,
            'aov' => $aov,
            'aov_trend_up' => $aovTrendUp,
            'net_cash_position' => $netCashPosition,
            'new_customers_count' => $newCustomersCount,
        ];

        // 3. Staff Leaderboard (Revenue by User)
        $staffOrders = DB::table('orders')
            ->whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->whereNull('deleted_at')
            ->select('created_by', 'status', 'total')
            ->get();
            
        $prevStaffOrders = DB::table('orders')
            ->whereDate('order_date', '>=', $prev_from_date)
            ->whereDate('order_date', '<=', $prev_to_date)
            ->whereNull('deleted_at')
            ->select('created_by', 'status', 'total')
            ->get();

        $users = DB::table('users')->pluck('name', 'id');
        
        $staffPerformance = [];
        $staffGroups = $staffOrders->groupBy('created_by');
        $prevStaffGroups = $prevStaffOrders->groupBy('created_by');

        foreach ($staffGroups as $userId => $userOrders) {
            if (!isset($users[$userId])) continue;

            $totalOrders = $userOrders->count();
            $deliveredOrders = $userOrders->where('status', 3);
            $totalRevenue = $deliveredOrders->sum('total');
            $returnedOrders = $userOrders->where('status', 4)->count();
            
            $aovPerStaff = $deliveredOrders->count() > 0 ? $totalRevenue / $deliveredOrders->count() : 0;
            $returnRatePerStaff = $totalOrders > 0 ? round(($returnedOrders / $totalOrders) * 100, 1) : 0;

            $prevUserOrders = $prevStaffGroups->get($userId, collect());
            $prevTotalRevenue = $prevUserOrders->where('status', 3)->sum('total');
            $trendUp = $totalRevenue >= $prevTotalRevenue;

            $staffPerformance[] = [
                'name' => $users[$userId],
                'total_revenue' => $totalRevenue,
                'aov_per_staff' => $aovPerStaff,
                'return_rate_per_staff' => $returnRatePerStaff,
                'trend_up' => $trendUp,
                'total_orders' => $totalOrders,
            ];
        }

        usort($staffPerformance, function ($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });

        $this->staffPerformance = $staffPerformance;

        // 4. Monthly Revenue Trend (Last 6 Months)
        $monthlyRevenueTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            
            $monthRevenue = DB::table('orders')
                ->whereDate('order_date', '>=', $monthStart->toDateString())
                ->whereDate('order_date', '<=', $monthEnd->toDateString())
                ->where('status', 3)
                ->whereNull('deleted_at')
                ->sum('total');
                
            $monthlyRevenueTrend[] = [
                'month' => $monthStart->format('M Y'),
                'revenue' => $monthRevenue
            ];
        }
        $this->monthlyRevenueTrend = $monthlyRevenueTrend;

        $this->dispatch('update-insights-chart', [
            'monthlyRevenueTrend' => $this->monthlyRevenueTrend,
        ]);
    }
}
