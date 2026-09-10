<?php

namespace App\Livewire\Reports;

use App\Models\Translation;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;

class DailyReport extends Component
{
    public $today;
    public $from_date;
    public $to_date;
    public $granularity = 'daily';
    public $new_order;
    public $delivered_orders;
    public $total_payment;
    public $total_expense;
    public $total_sales;
    public $lang;

    // New Metrics
    public $paymentSplit = [];
    public $itemVolume = 0;
    public $trendLabels = [];
    public $trendData = [];

    // Phase 2 Metrics
    public $cash_collected;
    public $pending_orders;
    public $unpaidDeliveries = [];
    public $overdueOrders = [];

    /* render the page */
    #[Title('Daily Report')]
    public function render()
    {
        return view('livewire.reports.daily-report');
    }
    /* processed before render */
    public function mount()
    {
        if (!\Illuminate\Support\Facades\Gate::allows('report_daily')) {
            abort(404);
        }
        $this->today = \Carbon\Carbon::today()->toDateString();
        $this->from_date = $this->today;
        $this->to_date = $this->today;

        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }
        $this->report();
    }

    public function updatedGranularity($value)
    {
        $today = \Carbon\Carbon::parse($this->today);
        
        switch ($value) {
            case 'daily':
                $this->from_date = $this->today;
                $this->to_date = $this->today;
                break;
            case 'weekly':
                $this->from_date = $today->copy()->startOfWeek()->toDateString();
                $this->to_date = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'monthly':
                $this->from_date = $today->copy()->startOfMonth()->toDateString();
                $this->to_date = $today->copy()->endOfMonth()->toDateString();
                break;
            case 'yearly':
                $this->from_date = $today->copy()->startOfYear()->toDateString();
                $this->to_date = $today->copy()->endOfYear()->toDateString();
                break;
        }
        $this->report();
    }

    /*processed on update of the element */
    public function updated($name, $value)
    {
        /* any updated on $today model */
        if (($name == "today") && ($value != "")) {
            $this->today = $value;
            $this->from_date = $value;
            $this->to_date = $value;
            $this->granularity = 'daily';
        }
        $this->report();
    }
    /* report section */
    public function report()
    {
        $this->new_order = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)->whereDate('order_date', '<=', $this->to_date)->count();
        $this->delivered_orders = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)->whereDate('order_date', '<=', $this->to_date)->where('status', 3)->count();
        $this->total_payment = \App\Models\Payment::whereDate('payment_date', '>=', $this->from_date)->whereDate('payment_date', '<=', $this->to_date)->sum('received_amount');
        $this->total_expense = \App\Models\Expense::whereDate('expense_date', '>=', $this->from_date)->whereDate('expense_date', '<=', $this->to_date)->sum('expense_amount');
        $this->total_sales = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)->whereDate('order_date', '<=', $this->to_date)->where('status', 3)->sum('total');

        $this->cash_collected = \App\Models\Payment::whereDate('payment_date', '>=', $this->from_date)->whereDate('payment_date', '<=', $this->to_date)->sum('received_amount');
        $this->pending_orders = \App\Models\Order::whereIn('status', [0, 1, 2])->whereNull('deleted_at')->count();

        $this->unpaidDeliveries = json_decode(json_encode(DB::table('orders')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->select('customers.name as customer_name', 'customers.phone as phone_number', 'orders.order_number', 'orders.total', 
                DB::raw('orders.total - COALESCE((SELECT SUM(received_amount) FROM payments WHERE order_id = orders.id), 0) as amount_owed'))
            ->whereDate('orders.order_date', '>=', $this->from_date)
            ->whereDate('orders.order_date', '<=', $this->to_date)
            ->where('orders.status', 3)
            ->whereNull('orders.deleted_at')
            ->whereRaw('orders.total > COALESCE((SELECT SUM(received_amount) FROM payments WHERE order_id = orders.id), 0)')
            ->get()), true);

        $overdueOrdersRaw = DB::table('orders')
            ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
            ->select('customers.name as customer_name', 'customers.phone as phone_number', 'orders.order_number', 'orders.delivery_date')
            ->whereDate('orders.delivery_date', '<', \Carbon\Carbon::today()->toDateString())
            ->whereNotIn('orders.status', [3, 4])
            ->whereNull('orders.deleted_at')
            ->get();
            
        $this->overdueOrders = [];
        foreach ($overdueOrdersRaw as $order) {
            $order->days_overdue = \Carbon\Carbon::parse($order->delivery_date)->diffInDays(\Carbon\Carbon::today());
            $this->overdueOrders[] = (array)$order;
        }

        // 1. Payment Split
        $paymentRaw = \App\Models\Payment::whereDate('payment_date', '>=', $this->from_date)
               ->whereDate('payment_date', '<=', $this->to_date)
               ->selectRaw('payment_type, SUM(received_amount) as amount')
               ->groupBy('payment_type')
               ->get();
        $this->paymentSplit = [];
        foreach ($paymentRaw as $p) {
            $this->paymentSplit[] = [
                'name' => getpaymentMode($p->payment_type),
                'amount' => $p->amount
            ];
        }

        // 2. Item Volume
        $this->itemVolume = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->whereDate('orders.order_date', '>=', $this->from_date)
            ->whereDate('orders.order_date', '<=', $this->to_date)
            ->sum('order_details.service_quantity');

        // 3. 7-Day Trend Chart
        $sevenDaysAgo = \Carbon\Carbon::parse($this->today)->subDays(6)->toDateString();
        $trend = DB::table('payments')
            ->select(DB::raw('DATE(payment_date) as date'), DB::raw('SUM(received_amount) as total_amount'))
            ->whereDate('payment_date', '>=', $sevenDaysAgo)
            ->whereDate('payment_date', '<=', $this->today)
            ->groupBy(DB::raw('DATE(payment_date)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $this->trendData = [];
        $this->trendLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::parse($this->today)->subDays($i)->toDateString();
            $this->trendLabels[] = \Carbon\Carbon::parse($date)->format('D'); // e.g. Mon, Tue
            $this->trendData[] = isset($trend[$date]) ? (float) $trend[$date]->total_amount : 0;
        }

        // Trigger front-end event to re-render charts
        $this->dispatch('update-daily-charts', [
            'labels' => $this->trendLabels,
            'data' => $this->trendData,
            'payment' => $this->paymentSplit
        ]);
    }

    /* download report */
    public function downloadFile()
    {
        $today = $this->today;
        $pdfContent = Pdf::loadView('livewire.reports.download-report.daily-report', compact('today'))->output();
        return response()->streamDownload(fn () => print($pdfContent), "dailyReport_" . $today . ".pdf");
    }

    use \App\Traits\CsvExportable;

    public function downloadCsv()
    {
        $todayFormatted = \Carbon\Carbon::parse($this->today)->format('d/m/Y');
        $filename = 'DailyReport_' . $this->today . '.csv';
        $headers = ['Particulars', 'Value'];
        $rows = [
            ['Report Date', $todayFormatted],
            ['Orders Received', $this->new_order ?? 0],
            ['Orders Delivered', $this->delivered_orders ?? 0],
            ['Items Processed', $this->itemVolume ?? 0],
            ['Pending Now', $this->pending_orders ?? 0],
            ['Total Sales (Revenue Billed)', $this->total_sales ?? 0],
            ['Cash Collected', $this->cash_collected ?? 0],
            ['Collection Gap', ($this->total_sales ?? 0) - ($this->cash_collected ?? 0)],
            ['Total Expense', $this->total_expense ?? 0],
        ];

        return $this->exportCsv($headers, $rows, $filename);
    }
}
