<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerReport extends Component
{
    public $lang;
    public $statusFilter = '0';
    public $kpiSummary = [
        'total_customers' => 0,
        'active_count' => 0,
        'at_risk_count' => 0,
        'lost_count' => 0,
        'total_outstanding' => 0,
        'avg_ltv' => 0,
    ];

    public $expandedCustomerId = null;

    #[Title('Customer Report')]
    public function render()
    {
        $this->customersData();
        return view('livewire.reports.customer-report');
    }

    public function mount()
    {
        if (!\Illuminate\Support\Facades\Gate::allows('report_customer')) {
            abort(404);
        }

        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }
    }

    public function toggleCustomerOrders($customerId)
    {
        if ($this->expandedCustomerId === $customerId) {
            $this->expandedCustomerId = null;
        } else {
            $this->expandedCustomerId = $customerId;
        }
    }

    #[Computed]
    public function customerOrders()
    {
        if (!$this->expandedCustomerId) {
            return [];
        }

        $orders = \App\Models\Order::with(['details', 'payments'])
            ->where('customer_id', $this->expandedCustomerId)
            ->orderBy('order_date', 'desc')
            ->get();

        $mapped = $orders->map(function ($order) {
            $paid = $order->payments->sum('received_amount');
            $outstanding = $order->total - $paid;

            $services = $order->details->map(function ($detail) {
                return $detail->service_name ?? 'Unknown Service';
            })->implode(', ');

            return (object) [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_date' => $order->order_date,
                'delivery_date' => $order->delivery_date,
                'status' => $order->status,
                'total' => $order->total,
                'paid' => $paid,
                'outstanding' => max(0, $outstanding),
                'services' => $services,
            ];
        });

        return $mapped;
    }

    #[Computed]
    public function acquisitionTrend()
    {
        $months = [];
        $counts = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->format('M');
            $counts[$date->format('M')] = 0;
        }

        $oneYearAgo = Carbon::now()->subMonths(11)->startOfMonth();

        $trendData = DB::table('customers')
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $oneYearAgo)
            ->get();
            
        $trend = [];
        foreach ($trendData as $t) {
            $m = Carbon::parse($t->created_at)->month;
            $y = Carbon::parse($t->created_at)->year;
            $key = $y . '-' . $m;
            if (!isset($trend[$key])) {
                $trend[$key] = (object)['y' => $y, 'm' => $m, 'total' => 0];
            }
            $trend[$key]->total++;
        }

        foreach ($trend as $t) {
            $monthName = Carbon::create($t->y, $t->m, 1)->format('M');
            if (isset($counts[$monthName])) {
                $counts[$monthName] = $t->total;
            }
        }

        return [
            'months' => $months,
            'counts' => array_values($counts),
        ];
    }

    #[Computed]
    public function customersData()
    {
        $thirtyDaysAgo = Carbon::today()->subDays(30);
        $twentyOneDaysAgo = Carbon::today()->subDays(21);
        $fortyDaysAgo = Carbon::today()->subDays(40);
        $sixtyDaysAgo = Carbon::today()->subDays(60);
        $prevMonthStart = Carbon::today()->subMonth()->startOfMonth();
        $prevMonthEnd = Carbon::today()->startOfMonth();

        $query = DB::table('customers')
            ->whereNull('customers.deleted_at');
        
        $viewable_ids = \Illuminate\Support\Facades\Auth::user()->getViewableCustomerUserIds();
        if ($viewable_ids !== 'all') {
            $query->whereIn('customers.created_by', $viewable_ids);
        }

        $customersAggregates = $query->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.created_at as registration_date',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('COALESCE(SUM(orders.total), 0) as total_spend'),
                DB::raw('MAX(orders.order_date) as last_visit'),
                DB::raw('MIN(orders.order_date) as first_visit')
            )
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.order_date >= ? THEN orders.total ELSE 0 END), 0) as spend_30", [$thirtyDaysAgo->toDateString()])
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.order_date >= ? THEN orders.total ELSE 0 END), 0) as spend_7", [Carbon::today()->subDays(7)->toDateString()])
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.order_date >= ? AND orders.order_date < ? THEN orders.total ELSE 0 END), 0) as spend_prev_month", [$prevMonthStart->toDateString(), $prevMonthEnd->toDateString()])
            ->leftJoin('orders', function ($join) {
                $join->on('customers.id', '=', 'orders.customer_id')
                     ->where('orders.status', '!=', 4)
                     ->whereNull('orders.deleted_at');
            })
            ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.created_at')
            ->get();

        $payments = DB::table('payments')
            ->whereNull('payments.deleted_at')
            ->select('customer_id', DB::raw('SUM(received_amount) as total_paid'))
            ->groupBy('customer_id')
            ->get()
            ->keyBy('customer_id');

        $data = [];

        $kpi = [
            'total_customers' => 0,
            'active_count' => 0,
            'at_risk_count' => 0,
            'lost_count' => 0,
            'total_outstanding' => 0,
            'total_lifetime_spend' => 0,
            'avg_ltv' => 0,
        ];

        foreach ($customersAggregates as $c) {
            $totalPaid = isset($payments[$c->id]) ? $payments[$c->id]->total_paid : 0;
            $outstanding = $c->total_spend - $totalPaid;
            if ($outstanding < 0) $outstanding = 0;
            $aov = $c->total_orders > 0 ? $c->total_spend / $c->total_orders : 0;

            $regDate = Carbon::parse($c->registration_date);
            $firstVisit = $c->first_visit ? Carbon::parse($c->first_visit) : null;
            $lastVisit = $c->last_visit ? Carbon::parse($c->last_visit) : null;
            
            $daysSinceLastVisit = $lastVisit ? $lastVisit->diffInDays(Carbon::today()) : 999;
            
            // 1=New, 2=Active, 3=Lapsing, 4=Dormant, 5=Lost
            $statusCode = 2; 
            $statusName = 'Active';

            if ($regDate->gte($thirtyDaysAgo) || ($firstVisit && $firstVisit->gte($thirtyDaysAgo))) {
                $statusCode = 1;
                $statusName = 'New';
            } elseif ($daysSinceLastVisit <= 21) {
                $statusCode = 2;
                $statusName = 'Active';
            } elseif ($daysSinceLastVisit <= 40) {
                $statusCode = 3;
                $statusName = 'Lapsing';
            } elseif ($daysSinceLastVisit <= 60) {
                $statusCode = 4;
                $statusName = 'Dormant';
            } else {
                $statusCode = 5;
                $statusName = 'Lost';
            }

            // KPIs (calculate based on all customers, not just filtered)
            $kpi['total_customers']++;
            $kpi['total_outstanding'] += $outstanding;
            $kpi['total_lifetime_spend'] += $c->total_spend;

            if (in_array($statusCode, [1, 2])) {
                $kpi['active_count']++;
            } elseif (in_array($statusCode, [3, 4])) {
                $kpi['at_risk_count']++;
            } elseif ($statusCode == 5) {
                $kpi['lost_count']++;
            }

            // Filtering
            if ($this->statusFilter == '0' || $this->statusFilter == $statusCode) {
                $data[] = [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'registration_date' => $regDate->format('d/m/Y'),
                    'total_orders' => $c->total_orders,
                    'total_spend' => $c->total_spend,
                    'spend_30' => $c->spend_30,
                    'spend_prev_month' => $c->spend_prev_month,
                    'spend_trend_up' => $c->spend_30 >= $c->spend_prev_month,
                    'spend_7' => $c->spend_7,
                    'aov' => $aov,
                    'outstanding' => $outstanding,
                    'last_visit' => $lastVisit ? $lastVisit->format('d/m/Y') : 'N/A',
                    'status' => $statusName,
                    'status_code' => $statusCode
                ];
            }
        }

        if ($kpi['total_customers'] > 0) {
            $kpi['avg_ltv'] = $kpi['total_lifetime_spend'] / $kpi['total_customers'];
        }

        $this->kpiSummary = $kpi;

        return collect($data)->sortByDesc('total_spend')->values()->all();
    }

    use \App\Traits\CsvExportable;

    public function downloadCsv()
    {
        $data = $this->customersData();
        $csvFileName = 'customer_report_' . date('Ymd_His') . '.csv';

        $headers = ['Name', 'Phone', 'Lifecycle Stage', 'Orders', 'Lifetime Spend', 'Outstanding Balance'];
        $rows = [];
        foreach ($data as $row) {
            $rows[] = [
                $row['name'],
                $row['phone'],
                $row['status'],
                $row['total_orders'],
                $row['total_spend'],
                $row['outstanding']
            ];
        }

        return $this->exportCsv($headers, $rows, $csvFileName);
    }
}

