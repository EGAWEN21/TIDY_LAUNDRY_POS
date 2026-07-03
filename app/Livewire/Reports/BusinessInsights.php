<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class BusinessInsights extends Component
{
    public $lang, $from_date, $to_date;
    public $kpi = [];
    public $chartData = [];

    #[Title('Business Insights')]
    public function render()
    {
        return view('livewire.reports.business-insights');
    }

    public function mount()
    {
        if(!\Illuminate\Support\Facades\Gate::allows('report_insights')){
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
        // 1. TAT (Turnaround Time) Efficiency
        $orders = DB::table('orders')
            ->select('status', 'order_date', 'delivery_date')
            ->whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->get();
            
        $totalOrders = $orders->count();
        $deliveredOnTime = 0;
        $delayed = 0;
        $pending = 0;
        
        foreach($orders as $o) {
            if ($o->status == 3) { // Delivered
                $deliveredOnTime++; // Simplifying for now, assuming delivered = on time
            } else if ($o->delivery_date && Carbon::parse($o->delivery_date)->isPast() && $o->status != 3 && $o->status != 4) {
                $delayed++;
            } else {
                $pending++;
            }
        }
        
        $this->kpi['tat'] = [
            'total' => $totalOrders,
            'on_time' => $deliveredOnTime,
            'delayed' => $delayed,
            'pending' => $pending
        ];

        // 2. Churn & Customer Health (At-Risk > 21 days)
        $atRiskThreshold = Carbon::today()->subDays(21)->toDateString();
        $allCustomers = DB::table('customers')
            ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
            ->select('customers.id', DB::raw('MAX(orders.order_date) as last_visit'))
            ->groupBy('customers.id')
            ->get();
            
        $activeCount = 0;
        $atRiskCount = 0;
        
        foreach($allCustomers as $c) {
            if($c->last_visit && Carbon::parse($c->last_visit)->gte($atRiskThreshold)) {
                $activeCount++;
            } else if ($c->last_visit) {
                $atRiskCount++;
            }
        }
        
        $this->kpi['health'] = [
            'active' => $activeCount,
            'at_risk' => $atRiskCount
        ];
        
        // 3. Staff Leaderboard (Revenue by User)
        $staffPerformance = DB::table('orders')
            ->join('users', 'orders.created_by', '=', 'users.id')
            ->select('users.name', DB::raw('SUM(orders.total) as total_revenue'), DB::raw('COUNT(orders.id) as total_orders'))
            ->whereDate('orders.order_date', '>=', $this->from_date)
            ->whereDate('orders.order_date', '<=', $this->to_date)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();
            
        $this->kpi['staff'] = $staffPerformance->toArray();
    }
}
