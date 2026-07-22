<?php

namespace App\Livewire\Customers\Partials;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Livewire\Component;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Facades\Auth;
use App\Models\Translation;

class CustomerInvoice extends Component
{
    public $nextCursor;
    protected $currentCursor;
    public $hasMorePages;
    public $customer;
    public $orders;
    public $order, $amount_to_pay, $note, $balance, $payment_mode, $order_filter, $lang;
    public $paid_amount, $customer_name, $search_query;
    public $collapsedGroups = [];


    public function render()
    {
        return view('livewire.customers.partials.customer-invoice');
    }

    public function mount(Customer $customer){
        $this->customer = $customer;
        $this->orders = new EloquentCollection();
        $this->loadOrders();
        if (session()->has('selected_language')) { /* if session has selected laugage*/
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }
    }

    public function filterdata(){
        $orders = Order::where('customer_id',$this->customer->id)->latest()->cursorPaginate(10, ['*'], 'cursor', Cursor::fromEncoded($this->nextCursor));
        return $orders;
    }

    public function getGroupedOrders()
    {
        $grouped = [];
        foreach ($this->orders as $order) {
            $dateKey = \Carbon\Carbon::parse($order->order_date)->format('Y-m-d');
            if (!isset($grouped[$dateKey])) {
                $grouped[$dateKey] = [
                    'orders' => [],
                    'count' => 0,
                    'total_sales' => 0,
                    'total_paid' => 0,
                ];
            }
            $grouped[$dateKey]['orders'][] = $order;
            $grouped[$dateKey]['count']++;
            $grouped[$dateKey]['total_sales'] += $order->total;
            $grouped[$dateKey]['total_paid'] += \App\Models\Payment::where('order_id', $order->id)->sum('received_amount');
        }
        krsort($grouped);
        return $grouped;
    }

    public function toggleGroup($date)
    {
        if (in_array($date, $this->collapsedGroups)) {
            $this->collapsedGroups = array_values(array_diff($this->collapsedGroups, [$date]));
        } else {
            $this->collapsedGroups[] = $date;
        }
    }

    public function loadOrders()
    {
        if ($this->hasMorePages !== null  && !$this->hasMorePages) {
            return;
        }
        $myorder = $this->filterdata();
        $this->orders->push(...$myorder->items());
        if ($this->hasMorePages = $myorder->hasMorePages()) {
            $this->nextCursor = $myorder->nextCursor()->encode();
        }
        $this->currentCursor = $myorder->cursor();
    }
    public function reloadOrders()
    {
        $this->orders = new EloquentCollection();
        $this->nextCursor = null;
        $this->hasMorePages = null;
        if ($this->hasMorePages !== null  && !$this->hasMorePages) {
            return;
        }
        $orders = $this->filterdata();
        $this->orders->push(...$orders->items());
        if ($this->hasMorePages = $orders->hasMorePages()) {
            $this->nextCursor = $orders->nextCursor()->encode();
        }
        $this->currentCursor = $orders->cursor();
    }

    /* get paid informatiion */
    public function payment($id)
    {
        $this->order = Order::where('id', $id)->first();
        $this->customer = Customer::where('id', $this->order->customer_id)->first();
        $this->customer_name = $this->customer->name ?? null;
        $this->paid_amount = Payment::where('order_id', $this->order->id)->sum('received_amount');
        $this->balance = round($this->order->total - $this->paid_amount, 2);
    }
    /* reset input fields */
    private function resetInputFields()
    {
        $this->balance = '';
        $this->order = '';
        $this->customer = '';
        $this->note = '';
        $this->payment_mode = "";
    }
    /* add paymentinformation */
    public function addPayment()
    {
        $this->validate([
            'payment_mode' => 'required',
            'balance' => 'required|numeric'
        ]);

        try {
            \App\Actions\Payments\ProcessPaymentAction::execute(
                $this->order,
                (float) $this->balance,
                $this->payment_mode,
                $this->note
            );
            $this->resetInputFields();
            $this->dispatch('closemodal');
            $this->dispatch('alert', ['type' => 'success',  'message' => 'Payment has been updated!']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('balance', $e->validator->errors()->first('payment_error') ?? $e->getMessage());
        }
    }
}
