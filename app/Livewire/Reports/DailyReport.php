<?php

namespace App\Livewire\Reports;
use App\Models\Translation;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;

class DailyReport extends Component
{
    public $today, $new_order, $delivered_orders, $total_payment, $total_expense, $total_sales, $lang;
    
    // New Metrics
    public $paymentSplit = [];
    public $itemVolume = 0;
    public $trendLabels = [];
    public $trendData = [];
    /* render the page */
    #[Title('Daily Report')]
    public function render()
    {
        return view('livewire.reports.daily-report');
    }
    /* processed before render */
    public function mount() {
        if(!\Illuminate\Support\Facades\Gate::allows('report_daily')){
            abort(404);
        }
        $this->today =\Carbon\Carbon::today()->toDateString();
        if(session()->has('selected_language'))
        {
            $this->lang = Translation::where('id',session()->get('selected_language'))->first();
        }
        else{
            $this->lang = Translation::where('default',1)->first();
        }
        $this->report();
    }
    /*processed on update of the element */
    public function updated($name,$value) {
        /* any updated on $today model */
        if(($name="today") && ($value!=""))
         {
             $this->today = $value;
        }
        $this->report();
    }
    /* report section */ 
    public function report(){
         $this->new_order = \App\Models\Order::whereDate('order_date',$this->today)->count();
         $this->delivered_orders = \App\Models\Order::whereDate('order_date',$this->today)->where('status',3)->count();
         $this->total_payment = \App\Models\Payment::whereDate('payment_date',$this->today)->sum('received_amount');
         $this->total_expense = \App\Models\Expense::whereDate('expense_date',$this->today)->sum('expense_amount');
         $this->total_sales = \App\Models\Order::whereDate('order_date',$this->today)->where('status',3)->sum('total');

         // 1. Payment Split
         $paymentRaw = \App\Models\Payment::whereDate('payment_date', $this->today)
                ->join('payment_types', 'payments.payment_type', '=', 'payment_types.id')
                ->selectRaw('payment_types.payment_type_name, SUM(payments.received_amount) as amount')
                ->groupBy('payment_types.payment_type_name')
                ->get();
         $this->paymentSplit = [];
         foreach($paymentRaw as $p) {
             $this->paymentSplit[] = [
                 'name' => $p->payment_type_name,
                 'amount' => $p->amount
             ];
         }

         // 2. Item Volume
         $this->itemVolume = DB::table('order_details')
             ->join('orders', 'order_details.order_id', '=', 'orders.id')
             ->whereDate('orders.order_date', $this->today)
             ->sum('order_details.service_quantity');

         // 3. 7-Day Trend Chart
         $sevenDaysAgo = \Carbon\Carbon::parse($this->today)->subDays(6)->toDateString();
         $trend = DB::table('orders')
             ->select(DB::raw('DATE(order_date) as date'), DB::raw('COUNT(id) as total_orders'))
             ->whereDate('order_date', '>=', $sevenDaysAgo)
             ->whereDate('order_date', '<=', $this->today)
             ->groupBy(DB::raw('DATE(order_date)'))
             ->orderBy('date')
             ->get()
             ->keyBy('date');

         $this->trendData = [];
         $this->trendLabels = [];
         for($i = 6; $i >= 0; $i--) {
             $date = \Carbon\Carbon::parse($this->today)->subDays($i)->toDateString();
             $this->trendLabels[] = \Carbon\Carbon::parse($date)->format('D'); // e.g. Mon, Tue
             $this->trendData[] = isset($trend[$date]) ? $trend[$date]->total_orders : 0;
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
}
