<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Translation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LedgerReport extends Component
{
    public $selected_customer,$customers,$customer_query,$start_date,$end_date,$lang;
    public $ageingData = [];
    #[Title('Ledger Report')]
    public function render()
    {
        return view('livewire.reports.ledger-report');
    }
    public function mount()
    {
        if(!\Illuminate\Support\Facades\Gate::allows('report_ledger')){
            abort(404);
        }
        if(session()->has('selected_language'))
        { /* if session has selected laugage*/
            $this->lang = Translation::where('id',session()->get('selected_language'))->first();
        }
        else{
            $this->lang = Translation::where('default',1)->first();
        }
        $this->start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->end_date = Carbon::now()->endOfMonth()->toDateString();
        $this->customers = collect();
        $this->calculateAgeing();
    }

    public function calculateAgeing()
    {
        $today = Carbon::today();
        
        $paymentsSub = DB::table('payments')
            ->select('order_id', DB::raw('SUM(received_amount) as total_paid'))
            ->groupBy('order_id');

        $orders = DB::table('orders')
            ->leftJoinSub($paymentsSub, 'paid_orders', function ($join) {
                $join->on('orders.id', '=', 'paid_orders.order_id');
            })
            ->select('orders.id', 'orders.order_date', 'orders.total', DB::raw('COALESCE(paid_orders.total_paid, 0) as paid'))
            ->whereRaw('orders.total > COALESCE(paid_orders.total_paid, 0)')
            ->get();
            
        $ageing = [ '0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0 ];
        
        foreach($orders as $o) {
            $balance = $o->total - $o->paid;
            $days = Carbon::parse($o->order_date)->diffInDays($today);
            
            if ($days <= 30) {
                $ageing['0_30'] += $balance;
            } else if ($days <= 60) {
                $ageing['31_60'] += $balance;
            } else if ($days <= 90) {
                $ageing['61_90'] += $balance;
            } else {
                $ageing['90_plus'] += $balance;
            }
        }
        
        $this->ageingData = [
            $ageing['0_30'],
            $ageing['31_60'],
            $ageing['61_90'],
            $ageing['90_plus']
        ];
    }

    public function updated($name,$value)
    {
        if($name == 'customer_query' && $value != '')
        {
            $this->customers = Customer::where(function($query) use ($value) { 
                $query->where('name', 'like', '%' . $value . '%')->orWhere('phone', 'like', '%' . $value . '%');
            })->latest()->limit(5)->get();
        }
        elseif($name == 'customer_query' && $value == ''){
            $this->customers = collect();
        }
    }

    /* select customer */
    public function selectCustomer($id)
    {
        $this->selected_customer = Customer::where('id',$id)->first();
        $this->customer_query = '';
        $this->customers = collect();
    }

    /* get Data */
    public function getData()
    {
        if(!$this->selected_customer)
        {
            $this->dispatch(
                'alert', ['type' => 'error','title' => 'Fetching failed',  'message' => 'You have not selected a customer!']);
            return;
        }
    }

    #[\Livewire\Attributes\Computed]
    public function data()
    {
        if(!$this->selected_customer) return [];
        $customerId = $this->selected_customer->id;
        $startDate = Carbon::parse($this->start_date)->toDateString();
        $endDate = Carbon::parse($this->end_date)->toDateString();

        return array_map(function($row) { return (array) $row; }, DB::select("
            SELECT order_date as date, 'debit' as type, order_number, total, 0 as received_amount
            FROM orders 
            WHERE customer_id = ? AND DATE(order_date) >= ? AND DATE(order_date) <= ?
            UNION ALL
            SELECT payment_date as date, 'credit' as type, NULL as order_number, 0 as total, received_amount
            FROM payments 
            WHERE customer_id = ? AND DATE(payment_date) >= ? AND DATE(payment_date) <= ?
            ORDER BY date ASC
        ", [$customerId, $startDate, $endDate, $customerId, $startDate, $endDate]));
    }

    #[\Livewire\Attributes\Computed]
    public function firstData()
    {
        if(!$this->selected_customer) return ['debits' => 0, 'credits' => 0];
        return [
            'debits'    => Order::where('customer_id',$this->selected_customer->id)->whereDate('order_date','<',Carbon::parse($this->start_date)->toDateString())->sum('total'),
            'credits'    => Payment::where('customer_id',$this->selected_customer->id)->whereDate('payment_date','<',Carbon::parse($this->start_date)->toDateString())->sum('received_amount'),
        ];
    }
}
