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
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MasterSettings;
use Livewire\Attributes\Computed;

class LedgerReport extends Component
{
    public $selected_customer;
    public $customers;
    public $customer_query;
    public $start_date;
    public $end_date;
    public $lang;
    public $ageingData = [];
    public $totalOutstanding = 0;

    #[Title('Ledger Report')]
    public function render()
    {
        return view('livewire.reports.ledger-report');
    }
    public function mount()
    {
        if (!\Illuminate\Support\Facades\Gate::allows('report_ledger')) {
            abort(404);
        }
        if (session()->has('selected_language')) { /* if session has selected laugage*/
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
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
            ->whereNull('payments.deleted_at')
            ->select('order_id', DB::raw('SUM(received_amount) as total_paid'))
            ->groupBy('order_id');

        $orders = DB::table('orders')
            ->whereNull('orders.deleted_at')
            ->leftJoinSub($paymentsSub, 'paid_orders', function ($join) {
                $join->on('orders.id', '=', 'paid_orders.order_id');
            })
            ->select('orders.id', 'orders.order_date', 'orders.total', DB::raw('COALESCE(paid_orders.total_paid, 0) as paid'))
            ->whereRaw('orders.total > COALESCE(paid_orders.total_paid, 0)')
            ->where('orders.status', '!=', 4)
            ->get();

        $ageing = [ '0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0 ];

        foreach ($orders as $o) {
            $balance = $o->total - $o->paid;
            $days = Carbon::parse($o->order_date)->diffInDays($today);

            if ($days <= 30) {
                $ageing['0_30'] += $balance;
            } elseif ($days <= 60) {
                $ageing['31_60'] += $balance;
            } elseif ($days <= 90) {
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
        $this->totalOutstanding = array_sum($this->ageingData);
    }

    public function updated($name, $value)
    {
        if ($name == 'customer_query' && $value != '') {
            $query = Customer::where(function ($query) use ($value) {
                $query->where('name', 'like', '%' . sanitize_search($value) . '%')->orWhere('phone', 'like', '%' . sanitize_search($value) . '%');
            });
            $viewable_ids = \Illuminate\Support\Facades\Auth::user()->getViewableCustomerUserIds();
            if ($viewable_ids !== 'all') {
                $query->whereIn('created_by', $viewable_ids);
            }
            $this->customers = $query->latest()->limit(5)->get();
        } elseif ($name == 'customer_query' && $value == '') {
            $this->customers = collect();
        }
    }

    /* select customer */
    public function selectCustomer($id)
    {
        $this->selected_customer = Customer::where('id', $id)->first();
        $this->customer_query = '';
        $this->customers = collect();
    }

    #[Computed]
    public function topDebtors()
    {
        $paymentsSub = DB::table('payments')
            ->whereNull('deleted_at')
            ->select('customer_id', DB::raw('SUM(received_amount) as total_paid'), DB::raw('MAX(payment_date) as last_payment_date'))
            ->groupBy('customer_id');

        $ordersSub = DB::table('orders')
            ->whereNull('deleted_at')
            ->where('status', '!=', 4)
            ->select('customer_id', DB::raw('SUM(total) as total_ordered'), DB::raw('MAX(order_date) as last_order_date'))
            ->groupBy('customer_id');

        return DB::table('customers')
            ->whereNull('customers.deleted_at')
            ->joinSub($ordersSub, 'o', function($join) {
                $join->on('customers.id', '=', 'o.customer_id');
            })
            ->leftJoinSub($paymentsSub, 'p', function($join) {
                $join->on('customers.id', '=', 'p.customer_id');
            })
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                DB::raw('COALESCE(o.total_ordered, 0) - COALESCE(p.total_paid, 0) as total_owed'),
                'p.last_payment_date',
                'o.last_order_date'
            )
            ->having('total_owed', '>', 0)
            ->orderBy('total_owed', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($debtor) {
                $lastDate = $debtor->last_payment_date ?? $debtor->last_order_date;
                $debtor->days_outstanding = $lastDate ? Carbon::parse($lastDate)->diffInDays(Carbon::today()) : 0;
                return $debtor;
            });
    }

    public function downloadStatement()
    {
        if (!$this->selected_customer) {
            $this->dispatch('alert', ['type' => 'error', 'title' => 'Error', 'message' => 'Please select a customer first.']);
            return;
        }

        $master_settings = MasterSettings::first()?->siteData() ?? [];
        $transactions = $this->data();
        $firstData = $this->firstData();
        $customer = $this->selected_customer;
        $start_date = $this->start_date;
        $end_date = $this->end_date;

        $pdfContent = Pdf::loadView('livewire.reports.download-report.account-statement', compact(
            'master_settings', 'transactions', 'firstData', 'customer', 'start_date', 'end_date'
        ))->output();

        return response()->streamDownload(
            fn () => print($pdfContent),
            "Account_Statement_{$customer->name}.pdf"
        );
    }

    use \App\Traits\CsvExportable;

    public function downloadCsv()
    {
        if (!$this->selected_customer) {
            $debtors = $this->topDebtors;
            $filename = 'Top_Debtors_' . date('Ymd_His') . '.csv';
            $headers = ['Customer', 'Phone', 'Total Owed', 'Last Payment Date', 'Days Outstanding'];
            $rows = [];
            foreach ($debtors as $d) {
                $rows[] = [
                    $d->name,
                    $d->phone,
                    $d->total_owed,
                    $d->last_payment_date ? Carbon::parse($d->last_payment_date)->format('d/m/Y') : 'N/A',
                    $d->days_outstanding,
                ];
            }
            return $this->exportCsv($headers, $rows, $filename);
        }

        $customerName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $this->selected_customer->name);
        $filename = 'Ledger_' . $customerName . '_' . $this->start_date . '_to_' . $this->end_date . '.csv';
        $headers = ['Date', 'Type', 'Reference', 'Debit', 'Credit', 'Running Balance'];
        $rows = [];

        $firstData = $this->firstData();
        $openingBalance = $firstData['debits'] - $firstData['credits'];
        $runningBalance = $openingBalance;

        $rows[] = [
            Carbon::parse($this->start_date)->format('d/m/Y'),
            'Opening Balance',
            '-',
            $openingBalance > 0 ? $openingBalance : 0,
            $openingBalance < 0 ? abs($openingBalance) : 0,
            $runningBalance
        ];

        foreach ($this->data() as $row) {
            if ($row['type'] == 'debit') {
                $runningBalance += $row['total'];
                $rows[] = [
                    Carbon::parse($row['date'])->format('d/m/Y'),
                    'Debit (Order)',
                    'Order #' . $row['order_number'],
                    $row['total'],
                    0,
                    $runningBalance
                ];
            } else {
                $runningBalance -= $row['received_amount'];
                $rows[] = [
                    Carbon::parse($row['date'])->format('d/m/Y'),
                    'Credit (Payment)',
                    'Payment',
                    0,
                    $row['received_amount'],
                    $runningBalance
                ];
            }
        }

        $rows[] = [
            Carbon::parse($this->end_date)->format('d/m/Y'),
            'Closing Balance',
            '-',
            '-',
            '-',
            $runningBalance
        ];

        return $this->exportCsv($headers, $rows, $filename);
    }

    #[\Livewire\Attributes\Computed]
    public function data()
    {
        if (!$this->selected_customer) {
            return [];
        }
        $customerId = $this->selected_customer->id;
        $startDate = Carbon::parse($this->start_date)->toDateString();
        $endDate = Carbon::parse($this->end_date)->toDateString();

        return array_map(function ($row) {
            return (array) $row;
        }, DB::select("
            SELECT order_date as date, 'debit' as type, order_number, total, 0 as received_amount
            FROM orders 
            WHERE customer_id = ? AND DATE(order_date) >= ? AND DATE(order_date) <= ? AND status != 4 AND deleted_at IS NULL
            UNION ALL
            SELECT payment_date as date, 'credit' as type, NULL as order_number, 0 as total, received_amount
            FROM payments 
            WHERE customer_id = ? AND DATE(payment_date) >= ? AND DATE(payment_date) <= ? AND deleted_at IS NULL
            ORDER BY date ASC
        ", [$customerId, $startDate, $endDate, $customerId, $startDate, $endDate]));
    }

    #[\Livewire\Attributes\Computed]
    public function firstData()
    {
        if (!$this->selected_customer) {
            return ['debits' => 0, 'credits' => 0];
        }
        return [
            'debits'    => Order::where('customer_id', $this->selected_customer->id)->where('status', '!=', 4)->whereDate('order_date', '<', Carbon::parse($this->start_date)->toDateString())->sum('total'),
            'credits'    => Payment::where('customer_id', $this->selected_customer->id)->whereDate('payment_date', '<', Carbon::parse($this->start_date)->toDateString())->sum('received_amount'),
        ];
    }
}
