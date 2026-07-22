<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Translation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderReport extends Component
{
    public $from_date, $to_date, $status = -1, $lang;
    
    // New Metrics
    public $kpi = [];
    public $serviceBreakdown = [];
    #[Title('Order Report')]
    public function render()
    {
        return view('livewire.reports.order-report');
    }
     /* processed before render */
     public function mount()
     {
        if(!\Illuminate\Support\Facades\Gate::allows('report_order')){
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
         $this->report();
     }
     /* report section */
     public function report()
     {
         $query = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
             ->whereDate('order_date', '<=', $this->to_date);
             
         if ($this->status != -1) {
             $query->where('status', $this->status);
         }
         
         
         // Calculate Current Period KPIs
         $currentOrders = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
             ->whereDate('order_date', '<=', $this->to_date);
             
         if ($this->status != -1) {
             $currentOrders->where('status', $this->status);
         }
         
         $currentOrders = $currentOrders->count();
         
         // Calculate Previous Period
         $daysDiff = Carbon::parse($this->from_date)->diffInDays(Carbon::parse($this->to_date)) + 1;
         $prev_to_date = Carbon::parse($this->from_date)->subDay()->toDateString();
         $prev_from_date = Carbon::parse($this->from_date)->subDays($daysDiff)->toDateString();
         
         $prevQuery = \App\Models\Order::whereDate('order_date', '>=', $prev_from_date)
             ->whereDate('order_date', '<=', $prev_to_date);
             
         if ($this->status != -1) {
             $prevQuery->where('status', $this->status);
         }
         
         $prevOrders = $prevQuery->count();
         
         $orderGrowth = 0;
         if ($prevOrders > 0) {
             $orderGrowth = (($currentOrders - $prevOrders) / $prevOrders) * 100;
         } else if ($currentOrders > 0) {
             $orderGrowth = 100;
         }
         
         $this->kpi = [
             'orders' => $currentOrders,
             'growth' => round($orderGrowth, 1)
         ];
         
         // Service Breakdown (100% Stacked Horizontal Bar)
         $serviceQuery = DB::table('order_details')
             ->join('orders', 'order_details.order_id', '=', 'orders.id')
             ->select('order_details.service_name', DB::raw('SUM(order_details.service_quantity) as volume'))
             ->whereDate('orders.order_date', '>=', $this->from_date)
             ->whereDate('orders.order_date', '<=', $this->to_date);
             
         if ($this->status != -1) {
             $serviceQuery->where('orders.status', $this->status);
         }
             
         $services = $serviceQuery->groupBy('order_details.service_name')
             ->orderByDesc('volume')
             ->get();
             
         $this->serviceBreakdown = [];
         foreach($services as $s) {
             $this->serviceBreakdown[] = [
                 'name' => $s->service_name,
                 'amount' => $s->volume // Using Volume for Order report instead of Revenue
             ];
         }
         
         $this->dispatch('update-order-charts', [
             'services' => $this->serviceBreakdown
         ]);
     }
     /* download report */
     public function downloadFile()
     {
         $from_date = $this->from_date;
         $to_date = $this->to_date;
         $status = $this->status;
         $pdfContent = Pdf::loadView('livewire.reports.download-report.order-report', compact('from_date', 'to_date', 'status'))->output();
         return response()->streamDownload(fn () => print($pdfContent), "OrderReport_from_" . $from_date . ".pdf");
     }

     #[\Livewire\Attributes\Computed]
     public function orders()
     {
         $query = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
             ->whereDate('order_date', '<=', $this->to_date);
             
         if ($this->status != -1) {
             $query->where('status', $this->status);
         }
         
         return $query->latest()->get();
     }
}
