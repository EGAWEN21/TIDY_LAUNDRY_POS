<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesReport extends Component
{
    public $from_date, $to_date, $lang;
    
    // New Metrics
    public $kpi = [];
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
        if(!\Illuminate\Support\Facades\Gate::allows('report_sales')){
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
          // Calculate Current Period KPIs at the database level
          $currentKpi = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
              ->whereDate('order_date', '<=', $this->to_date)
              ->where('status', 3)
              ->selectRaw('COALESCE(SUM(total), 0) as sales, COALESCE(SUM(discount), 0) as discount, COUNT(*) as orders')
              ->first();
          
          $currentSales = $currentKpi->sales;
          $currentDiscount = $currentKpi->discount;
          $currentOrders = $currentKpi->orders;
          $currentAov = $currentOrders > 0 ? $currentSales / $currentOrders : 0;
          
          // Calculate Previous Period (Period-over-Period) at the database level
          $daysDiff = Carbon::parse($this->from_date)->diffInDays(Carbon::parse($this->to_date)) + 1;
          $prev_to_date = Carbon::parse($this->from_date)->subDay()->toDateString();
          $prev_from_date = Carbon::parse($this->from_date)->subDays($daysDiff)->toDateString();
          
          $prevSales = \App\Models\Order::whereDate('order_date', '>=', $prev_from_date)
              ->whereDate('order_date', '<=', $prev_to_date)
              ->where('status', 3)
              ->sum('total');
              
          // Growth Calculation
          $salesGrowth = 0;
          if ($prevSales > 0) {
              $salesGrowth = (($currentSales - $prevSales) / $prevSales) * 100;
          } else if ($currentSales > 0) {
              $salesGrowth = 100;
          }
          
          $this->kpi = [
              'sales' => $currentSales,
              'orders' => $currentOrders,
              'discount' => $currentDiscount,
              'aov' => $currentAov,
              'growth' => round($salesGrowth, 1)
          ];
          
          // Fetch orders for the table display is now handled by #[Computed]
          
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
          foreach($services as $s) {
              $this->serviceBreakdown[] = [
                  'name' => $s->service_name,
                  'amount' => $s->revenue
              ];
          }
          
          $this->dispatch('update-sales-charts', [
              'services' => $this->serviceBreakdown
          ]);
      }
      /* download pdf file */
      public function downloadFile()
      {
          $from_date = $this->from_date;
          $to_date = $this->to_date;
          $pdfContent = Pdf::loadView('livewire.reports.download-report.sales-report', compact('from_date', 'to_date'))->output();
          return response()->streamDownload(fn () => print($pdfContent), "SalesReport_from_" . $from_date . ".pdf");
      }

      #[\Livewire\Attributes\Computed]
      public function orders()
      {
          return \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
              ->whereDate('order_date', '<=', $this->to_date)
              ->where('status', 3)
              ->latest()
              ->get();
      }
}
