<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\WithPagination;

class SalesReport extends Component
{
    use WithPagination;
    public $from_date;
    public $to_date;
    public $granularity = 'monthly';
    public $lang;

    #[\Livewire\Attributes\Url(as: 'tab')]
    public $activeTab = 'financial';
    public $status = -1;

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
        $this->report();
    }


    // New Metrics
    public $kpi = [];
    public $financialKpi = [];
    public $operationalKpi = [];
    public $trendLabels = [];
    public $trendData = [];
    public $pipelineData = [];
    public $serviceBreakdown = [];
    
    /* render the page */
    #[Title('Sales Report')]
    public function render()
    {
        return view('livewire.reports.sales-report');
    }
    /* processed before render */
    public function mount()
    {
        if (!\Illuminate\Support\Facades\Gate::allows('report_sales')) {
            abort(404);
        }
        $this->from_date = Carbon::now()->startOfMonth()->toDateString();
        $this->to_date = Carbon::now()->endOfMonth()->toDateString();

        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }
        $this->report();
    }
    /*processed on update of the element */
    public function updated($name, $value)
    {
        $this->resetPage();
        $this->report();
    }
    /* report section */
    public function report()
    {
        // === Financial KPIs ===
        $finData = DB::table('orders')
            ->whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->where('status', 3)
            ->selectRaw('COALESCE(SUM(total), 0) as revenue_billed, COALESCE(SUM(discount), 0) as discount, COUNT(*) as orders')
            ->first();

        $revenueBilled = $finData->revenue_billed;
        $discount = $finData->discount;
        $completedOrders = $finData->orders;
        $aov = $completedOrders > 0 ? $revenueBilled / $completedOrders : 0;
        $discountRate = ($revenueBilled + $discount) > 0 ? ($discount / ($revenueBilled + $discount)) * 100 : 0;

        $cashCollected = DB::table('payments')
            ->whereDate('payment_date', '>=', $this->from_date)
            ->whereDate('payment_date', '<=', $this->to_date)
            ->sum('received_amount');

        $collectionRate = $revenueBilled > 0 ? ($cashCollected / $revenueBilled) * 100 : 0;

        // Previous Period (Period-over-Period) for growth
        $daysDiff = Carbon::parse($this->from_date)->diffInDays(Carbon::parse($this->to_date)) + 1;
        $prev_to_date = Carbon::parse($this->from_date)->subDay()->toDateString();
        $prev_from_date = Carbon::parse($this->from_date)->subDays($daysDiff)->toDateString();

        $prevSales = DB::table('orders')->whereDate('order_date', '>=', $prev_from_date)
            ->whereDate('order_date', '<=', $prev_to_date)
            ->where('status', 3)
            ->sum('total');

        $salesGrowth = 0;
        if ($prevSales > 0) {
            $salesGrowth = (($revenueBilled - $prevSales) / $prevSales) * 100;
        } elseif ($revenueBilled > 0) {
            $salesGrowth = 100;
        }

        $this->financialKpi = [
            'revenue_billed' => $revenueBilled,
            'cash_collected' => $cashCollected,
            'collection_rate_pct' => round($collectionRate, 1),
            'aov' => $aov,
            'discount_rate_pct' => round($discountRate, 1),
            'growth' => round($salesGrowth, 1)
        ];

        // === Operational KPIs ===
        $opBaseQuery = DB::table('orders')
            ->whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date);

        $totalOrders = $opBaseQuery->count();
        
        $deliveredOrdersList = (clone $opBaseQuery)->where('status', 3)->get(['order_date', 'updated_at']);
        $totalTatDays = 0;
        foreach ($deliveredOrdersList as $o) {
            $totalTatDays += \Carbon\Carbon::parse($o->order_date)->diffInDays(\Carbon\Carbon::parse($o->updated_at));
        }
        $avgTat = $deliveredOrdersList->count() > 0 ? $totalTatDays / $deliveredOrdersList->count() : 0;
        
        $deliveredOrders = (clone $opBaseQuery)->where('status', 3)->count();
        $onTimeDelivered = (clone $opBaseQuery)->where('status', 3)->whereRaw('DATE(updated_at) <= delivery_date')->count();
        $onTimePct = $deliveredOrders > 0 ? ($onTimeDelivered / $deliveredOrders) * 100 : 0;
        
        $returnedOrders = (clone $opBaseQuery)->where('status', 4)->count();
        $returnRate = $totalOrders > 0 ? ($returnedOrders / $totalOrders) * 100 : 0;
        
        $today = Carbon::now()->toDateString();
        $overdueCount = (clone $opBaseQuery)->whereDate('delivery_date', '<', $today)->whereNotIn('status', [3,4])->count();

        $this->operationalKpi = [
            'total_orders' => $totalOrders,
            'avg_tat' => round($avgTat, 1),
            'on_time_pct' => round($onTimePct, 1),
            'return_rate' => round($returnRate, 1),
            'overdue_count' => $overdueCount
        ];

        // === Revenue trend ===
        $trendRecords = DB::table('orders')
            ->whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->where('status', 3)
            ->selectRaw('DATE(order_date) as date, SUM(total) as amount')
            ->groupBy(DB::raw('DATE(order_date)'))
            ->orderBy('date')
            ->get();
        
        $this->trendLabels = $trendRecords->pluck('date')->toArray();
        $this->trendData = $trendRecords->pluck('amount')->toArray();

        // === Pipeline Data ===
        $pipelineCounts = DB::table('orders')
            ->whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $this->pipelineData = [
            ['name' => 'Pending', 'data' => [$pipelineCounts[0] ?? 0]],
            ['name' => 'Processing', 'data' => [$pipelineCounts[1] ?? 0]],
            ['name' => 'Ready', 'data' => [$pipelineCounts[2] ?? 0]],
            ['name' => 'Delivered', 'data' => [$pipelineCounts[3] ?? 0]],
            ['name' => 'Returned', 'data' => [$pipelineCounts[4] ?? 0]],
        ];

        // Service Breakdown (already uses DB-level aggregation - keep as is)
        $services = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->select('order_details.service_name', DB::raw('SUM(order_details.service_detail_total) as revenue'))
            ->whereDate('orders.order_date', '>=', $this->from_date)
            ->whereDate('orders.order_date', '<=', $this->to_date)
            ->where('orders.status', 3)
            ->groupBy('order_details.service_name')
            ->orderByDesc('revenue')
            ->get();

        $this->serviceBreakdown = [];
        foreach ($services as $s) {
            $this->serviceBreakdown[] = [
                'name' => $s->service_name,
                'amount' => $s->revenue
            ];
        }

        $this->dispatch('update-sales-charts', [
            'services' => $this->serviceBreakdown,
            'trendLabels' => $this->trendLabels,
            'trendData' => $this->trendData,
            'pipelineData' => $this->pipelineData,
            'activeTab' => $this->activeTab
        ]);
    }
    use \App\Traits\CsvExportable;

    /* download pdf file */
    public function downloadFile()
    {
        $from_date = $this->from_date;
        $to_date = $this->to_date;
        $pdfContent = Pdf::loadView('livewire.reports.download-report.sales-report', compact('from_date', 'to_date'))->output();
        return response()->streamDownload(fn () => print($pdfContent), "SalesReport_from_" . $from_date . ".pdf");
    }

    public function downloadCsv()
    {
        $query = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->withSum('payments as paid', 'received_amount');
            
        if ($this->activeTab === 'financial') {
            $query->where('status', 3);
        } else {
            if ($this->status != -1) {
                $query->where('status', $this->status);
            }
        }

        $orders = $query->latest()->get();

        $filename = 'SalesReport_' . $this->from_date . '.csv';
        $headers = ['Order No', 'Date', 'Customer', 'Status', 'Total', 'Outstanding'];
        $rows = [];

        foreach ($orders as $order) {
            $statusName = 'Unknown';
            if ($order->status == 0) $statusName = 'Pending';
            elseif ($order->status == 1) $statusName = 'Processing';
            elseif ($order->status == 2) $statusName = 'Ready';
            elseif ($order->status == 3) $statusName = 'Delivered';
            elseif ($order->status == 4) $statusName = 'Returned';
            
            $outstanding = $order->total - ($order->paid ?? 0);

            $rows[] = [
                $order->order_number,
                \Carbon\Carbon::parse($order->order_date)->format('d/m/Y'),
                $order->customer_name,
                $statusName,
                $order->total,
                $outstanding
            ];
        }

        return $this->exportCsv($headers, $rows, $filename);
    }

    #[\Livewire\Attributes\Computed]
    public function orders()
    {
        $query = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->withSum('payments as paid', 'received_amount');
            
        if ($this->activeTab === 'financial') {
            $query->where('status', 3);
        } else {
            if ($this->status != -1) {
                $query->where('status', $this->status);
            }
        }

        $orders = $query->latest()->paginate(50);
        foreach ($orders as $order) {
            $order->outstanding = $order->total - ($order->paid ?? 0);
        }
        
        return $orders;
    }
}
