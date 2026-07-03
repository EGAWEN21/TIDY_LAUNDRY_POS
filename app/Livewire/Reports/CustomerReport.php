<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomerReport extends Component
{
    public $lang, $customersData = [], $statusFilter = 'all';

    #[Title('Customer Report')]
    public function render()
    {
        return view('livewire.reports.customer-report');
    }

    public function mount()
    {
        if(!\Illuminate\Support\Facades\Gate::allows('report_customer')){
            abort(404);
        }
        
        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }
        $this->report();
    }

    public function updatedStatusFilter()
    {
        $this->report();
    }

    public function report()
    {
        /** 
         * OPTIMIZATION (Preventing N+1 & Memory Exhaustion):
         * Instead of loading all customers and iterating over their orders via Eloquent relationships
         * which would crash on large datasets, we use a single aggregate SQL query to sum up 
         * lifetime spend, 30-day velocity, and 7-day velocity.
         */
        $thirtyDaysAgo = Carbon::today()->subDays(30)->toDateString();
        $sevenDaysAgo = Carbon::today()->subDays(7)->toDateString();
        $atRiskThreshold = Carbon::today()->subDays(21); // 21 Days as requested by user

        // Aggregate Orders per customer
        $customersAggregates = DB::table('customers')
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.created_at as registration_date',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(orders.total), 0) as total_spend'),
                DB::raw("COALESCE(SUM(CASE WHEN orders.order_date >= '{$thirtyDaysAgo}' THEN orders.total ELSE 0 END), 0) as spend_30"),
                DB::raw("COALESCE(SUM(CASE WHEN orders.order_date >= '{$sevenDaysAgo}' THEN orders.total ELSE 0 END), 0) as spend_7"),
                DB::raw('MAX(orders.order_date) as last_visit')
            )
            ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.created_at')
            ->having('total_orders', '>', 0) // Only show customers who actually have orders
            ->get();
            
        // Aggregate Payments per customer for Outstanding Balances
        $payments = DB::table('payments')
            ->select('customer_id', DB::raw('SUM(received_amount) as total_paid'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $data = [];
        foreach($customersAggregates as $c) {
            $totalPaid = isset($payments[$c->id]) ? $payments[$c->id]->total_paid : 0;
            $outstanding = $c->total_spend - $totalPaid;
            $aov = $c->total_orders > 0 ? $c->total_spend / $c->total_orders : 0;
            
            // Status Logic (21 Days)
            $status = 'Active';
            if ($c->last_visit && Carbon::parse($c->last_visit)->lt($atRiskThreshold)) {
                $status = 'At-Risk';
            }
            
            // Filtering
            if ($this->statusFilter == 'all' || strtolower($this->statusFilter) == strtolower($status)) {
                $data[] = [
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'registration_date' => Carbon::parse($c->registration_date)->format('d/m/Y'),
                    'total_orders' => $c->total_orders,
                    'total_spend' => $c->total_spend,
                    'spend_30' => $c->spend_30,
                    'spend_7' => $c->spend_7,
                    'aov' => $aov,
                    'outstanding' => $outstanding,
                    'last_visit' => $c->last_visit ? Carbon::parse($c->last_visit)->format('d/m/Y') : 'N/A',
                    'status' => $status
                ];
            }
        }
        
        // Sort by highest lifetime spend
        $this->customersData = collect($data)->sortByDesc('total_spend')->values()->all();
    }
}
