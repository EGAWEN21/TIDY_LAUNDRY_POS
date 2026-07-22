<?php

namespace App\Livewire\Orders;

use Livewire\Attributes\Title;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\OrderDetail;
use App\Models\OrderAddonDetail;
use Auth;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Cursor;

use Livewire\Component;

class OrdersList extends Component
{
    public $orders;
    public $paid_amount, $customer, $customer_name, $search_query;
    public $order, $amount_to_pay, $note, $balance, $payment_mode, $order_filter, $lang;
    public $nextCursor;
    protected $currentCursor;
    public $hasMorePages;
    public $paid_filter;
    public $date_from, $date_to;
    public $date_preset;
    public $collapsedGroups = [];
    public $selectedOrders = [];

    #[Title('Orders')]
    public function render()
    {
        return view('livewire.orders.orders-list');
    }

    /* process before render */
    public function mount()
    {
        if(!\Illuminate\Support\Facades\Gate::allows('order_list')){
            abort(404);
        }
        $this->orders = new EloquentCollection();

        $this->loadOrders();
        if (session()->has('selected_language')) {   /* if session has selected language */
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            /* if session has no selected language */
            $this->lang = Translation::where('default', 1)->first();
        }
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
            $grouped[$dateKey]['total_paid'] += $order->payments_sum_received_amount ?? 0;
        }
        krsort($grouped);
        return $grouped;
    }

    public function applyDatePreset($preset)
    {
        if ($this->date_preset === $preset) {
            $this->clearDateFilter();
            return;
        }
        $this->date_preset = $preset;
        $today = \Carbon\Carbon::today();
        switch ($preset) {
            case 'today':
                $this->date_from = $today->format('Y-m-d');
                $this->date_to = $today->format('Y-m-d');
                break;
            case 'yesterday':
                $yesterday = $today->copy()->subDay();
                $this->date_from = $yesterday->format('Y-m-d');
                $this->date_to = $yesterday->format('Y-m-d');
                break;
            case 'this_week':
                $this->date_from = $today->copy()->startOfWeek()->format('Y-m-d');
                $this->date_to = $today->format('Y-m-d');
                break;
            case 'this_month':
                $this->date_from = $today->copy()->startOfMonth()->format('Y-m-d');
                $this->date_to = $today->format('Y-m-d');
                break;
        }
        $this->reloadOrders();
    }

    public function applyDateRange($from, $to)
    {
        $this->date_from = $from;
        $this->date_to = $to;
        $this->date_preset = 'custom';
        $this->reloadOrders();
    }

    public function clearDateFilter()
    {
        $this->date_from = null;
        $this->date_to = null;
        $this->date_preset = null;
        $this->reloadOrders();
    }

    public function toggleGroup($date)
    {
        if (in_array($date, $this->collapsedGroups)) {
            $this->collapsedGroups = array_values(array_diff($this->collapsedGroups, [$date]));
        } else {
            $this->collapsedGroups[] = $date;
        }
    }

    public function toggleDateGroup($dateKey, $orderIds)
    {
        $orderIds = array_map('strval', $orderIds);
        $allSelected = empty(array_diff($orderIds, $this->selectedOrders));
        if ($allSelected) {
            $this->selectedOrders = array_values(array_diff($this->selectedOrders, $orderIds));
        } else {
            $this->selectedOrders = array_unique(array_merge($this->selectedOrders, $orderIds));
        }
    }

    public function bulkChangeStatus($status)
    {
        if(empty($this->selectedOrders)) return;
        
        if(!\Illuminate\Support\Facades\Gate::allows('bulk_order_status_change')){
            $this->dispatch('alert', ['type' => 'error',  'message' => 'You do not have permission to change status in bulk!']);
            return;
        }

        foreach ($this->selectedOrders as $orderId) {
            $this->changeOrderStatus($orderId, $status, true);
        }
        
        $this->selectedOrders = [];
        $this->dispatch('alert', ['type' => 'success',  'message' => 'Status successfully updated for selected orders!']);
    }

    public function bulkDelete()
    {
        if(empty($this->selectedOrders)) return;

        if(!\Illuminate\Support\Facades\Gate::allows('bulk_order_delete')){
            $this->dispatch('alert', ['type' => 'error',  'message' => 'You do not have permission to delete orders in bulk!']);
            return;
        }

        \Illuminate\Support\Facades\DB::transaction(function () {
            foreach ($this->selectedOrders as $orderId) {
                $order = Order::find($orderId);
                if ($order) {
                    $order->deleted_by = Auth::id();
                    $order->save();
                    
                    OrderDetail::where('order_id', $order->id)->delete();
                    OrderAddonDetail::where('order_id', $order->id)->delete();
                    Payment::where('order_id', $order->id)->delete();
                    $order->delete();
                }
            }
        });
        
        $this->selectedOrders = [];
        $this->reloadOrders();
        $this->dispatch('alert', ['type' => 'success',  'message' => 'Selected orders have been moved to Recycle Bin!']);
    }
    /* process while update the content */
    private function getBaseOrderQuery()
    {
        if (Auth::user()->user_type == 1 || Auth::user()->viewable_staff_orders === 'all') {
            return \App\Models\Order::withSum('payments', 'received_amount');
        }
        $viewable_ids = [Auth::user()->id];
        if (!empty(Auth::user()->viewable_staff_orders)) {
            $extra_ids = explode(',', Auth::user()->viewable_staff_orders);
            $viewable_ids = array_merge($viewable_ids, $extra_ids);
        }
        return \App\Models\Order::withSum('payments', 'received_amount')->whereIn('created_by', $viewable_ids);
    }

    public function updated($name, $value)
    {

        // $this->reloadOrders();
        $ordersQuery = $this->getBaseOrderQuery()->orderBy('order_number','DESC');

        if ($this->date_from && $this->date_to) {
            $ordersQuery = $ordersQuery->whereDate('order_date', '>=', $this->date_from)
                                       ->whereDate('order_date', '<=', $this->date_to);
        }

        /* if the updated element is search_query */
        if ($name == 'search_query') {
            if ($value != '') {
                $ordersQuery = $ordersQuery
                    ->where(function ($q) use ($value) {
                        $q->where('order_number', 'like', '%' . $value . '%')
                            ->orwhere('customer_name', 'like', '%' . $value . '%')
                            ->orwhere('phone_number', 'like', '%' . $value . '%');
                    });
            }
            if ($this->order_filter != '') {
                $ordersQuery = $ordersQuery->where('status', $this->order_filter);
            }
            if ($this->paid_filter == '') {
                $this->orders = $ordersQuery->get();
            } elseif ($this->paid_filter != '') {
                $paymentStatus = $this->paid_filter;
                // Fetch orders and calculate payment status
                $this->orders = $ordersQuery->orderBy('order_number','DESC')->get()->map(function ($order) {
                    $paidAmount = $order->payments_sum_received_amount ?? 0;

                    if ($paidAmount == 0) {
                        $order->payment_status = 3;
                    } elseif ($paidAmount < $order->total) {
                        $order->payment_status = 2;
                    } elseif ($paidAmount >= $order->total) {
                        $order->payment_status = 1;
                    }
                    return $order;
                })
                    ->filter(function ($order) use ($paymentStatus) {
                        return $order->payment_status == $paymentStatus;
                    });
            }
        }


        /* if the updated element is order_filter */
        if ($name == 'order_filter') {
            if ($value != '') {
                $ordersQuery = $ordersQuery->where('status', $value);
            }

            if ($this->search_query != '') {
                $ordersQuery = $ordersQuery
                    ->where(function ($q) use ($value) {
                        $q->where('order_number', 'like', '%' . $this->search_query . '%')
                            ->orwhere('customer_name', 'like', '%' . $this->search_query . '%')
                            ->orwhere('phone_number', 'like', '%' . $this->search_query . '%');
                    });
            }

            if ($this->paid_filter == '') {
                $this->orders = $ordersQuery->get();
            } elseif ($this->paid_filter != '') {
                $paymentStatus = $this->paid_filter;
                // Fetch orders and calculate payment status
                $this->orders = $ordersQuery->orderBy('order_number','DESC')->get()->map(function ($order) {
                    $paidAmount = $order->payments_sum_received_amount ?? 0;

                    if ($paidAmount == 0) {
                        $order->payment_status = 3;
                    } elseif ($paidAmount < $order->total) {
                        $order->payment_status = 2;
                    } elseif ($paidAmount >= $order->total) {
                        $order->payment_status = 1;
                    }
                    return $order;
                })
                    ->filter(function ($order) use ($paymentStatus) {
                        return $order->payment_status == $paymentStatus;
                    });
            }
        }

        /* if the updated element is paid_filter */
        if ($name == 'paid_filter') {
            if ($value != '') {
                if ($this->search_query != '') {
                    $ordersQuery = $ordersQuery
                        ->where(function ($q) use ($value) {
                            $q->where('order_number', 'like', '%' . $this->search_query . '%')
                                ->orwhere('customer_name', 'like', '%' . $this->search_query . '%')
                                ->orwhere('phone_number', 'like', '%' . $this->search_query . '%');
                        });
                }
                if ($this->order_filter != '') {
                    $ordersQuery = $ordersQuery->where('status', $this->order_filter);
                }

                $paymentStatus = $value;
                // Fetch orders and calculate payment status
                $this->orders = $ordersQuery->orderBy('order_number','DESC')->get()->map(function ($order) {
                    $paidAmount = $order->payments_sum_received_amount ?? 0;

                    if ($paidAmount == 0) {
                        $order->payment_status = 3;
                    } elseif ($paidAmount < $order->total) {
                        $order->payment_status = 2;
                    } elseif ($paidAmount >= $order->total) {
                        $order->payment_status = 1;
                    }
                    return $order;
                })
                    ->filter(function ($order) use ($paymentStatus) {
                        return $order->payment_status == $paymentStatus;
                    });
            } else {
                if ($this->search_query != '') {
                    $ordersQuery = $ordersQuery
                        ->where(function ($q) use ($value) {
                            $q->where('order_number', 'like', '%' . $this->search_query . '%')
                                ->orwhere('customer_name', 'like', '%' . $this->search_query . '%')
                                ->orwhere('phone_number', 'like', '%' . $this->search_query . '%');
                        });
                }
                if ($this->order_filter != '') {
                    $ordersQuery = $ordersQuery->where('status', $this->order_filter);
                }
                $this->orders = $ordersQuery->orderBy('order_number','DESC')->get();
            }
        }
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
    /* refresh the page */
    public function refresh()
    {
        /* if search query or order filter is empty */
        if ($this->search_query == '' && $this->order_filter == '') {
            $this->orders->fresh();
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
    public function filterdata()
    {
        $baseQuery = $this->getBaseOrderQuery();

        // Apply date range filter
        if ($this->date_from && $this->date_to) {
            $baseQuery = $baseQuery->whereDate('order_date', '>=', $this->date_from)
                                   ->whereDate('order_date', '<=', $this->date_to);
        }

        if ($this->search_query || $this->search_query != '') {
            $searchQuery = $this->search_query;
            $baseQuery = $baseQuery->where(function($q) use ($searchQuery) {
                $q->where('order_number', 'like', '%' . $searchQuery . '%')
                  ->orWhere('customer_name', 'like', '%' . $searchQuery . '%')
                  ->orWhere('phone_number', 'like', '%' . $searchQuery . '%');
            });
        }

        if ($this->order_filter && $this->order_filter != '') {
            $baseQuery = $baseQuery->where('status', $this->order_filter);
        }

        $orders = $baseQuery->orderBy('order_number', 'DESC')
            ->cursorPaginate(10, ['*'], 'cursor', Cursor::fromEncoded($this->nextCursor));

        return $orders;
    }

    public function sendReceiptEmail($orderId)
    {
        try {
            $order = Order::with('details.service')->find($orderId);
            $customer = Customer::find($order->customer_id);

            if (!$customer || empty($customer->email)) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Customer email is not provided.']);
                return;
            }

            \Illuminate\Support\Facades\Mail::to($customer->email)->send(new \App\Mail\OrderReceiptEmail($order));

            $this->dispatch('alert', ['type' => 'success', 'message' => 'Receipt sent via Email successfully.']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Failed to send email. Check SMTP settings.']);
        }
    }

    public function sendReceiptSMS($orderId)
    {
        try {
            $order = Order::find($orderId);
            if (!$order->customer_id) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Customer phone number is not provided.']);
                return;
            }
            
            sendOrderCreateSMS($order->id, $order->customer_id);
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Receipt sent via SMS successfully.']);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Failed to send SMS.']);
        }
    }

    public function getWhatsAppReceiptUrl($orderId)
    {
        $order = Order::with('details.service')->find($orderId);
        $phone = $order->phone_number;
        if (!$phone && $order->customer_id) {
            $customer = Customer::find($order->customer_id);
            $phone = $customer ? $customer->phone : null;
        }

        if (!$phone) {
            return null;
        }

        $service = app(\App\Services\WhatsAppService::class);
        $message = $service->formatOrderMessage($order);
        
        $receiptUrl = \Illuminate\Support\Facades\URL::signedRoute('receipt.view', ['id' => $order->id]); 
        $message .= "\n\n*View Secure Digital Receipt:*\n" . $receiptUrl;
        
        $countryCode = getCountryCode();
        $countryCode = str_replace('+', '', $countryCode);
        $phone = ltrim($phone, '0');
        $fullPhone = $countryCode . $phone;

        return 'https://wa.me/' . $fullPhone . '?text=' . urlencode($message);
    }

    public function sendReceiptWhatsApp($orderId)
    {
        try {
            $url = $this->getWhatsAppReceiptUrl($orderId);
            if (!$url) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Customer phone number is not provided.']);
                return;
            }
            $this->dispatch('open-url', [['url' => $url]]);
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Failed to generate WhatsApp link.']);
        }
    }

    public function changeOrderStatus($orderId, $status, $isBulk = false)
    {
        if(!\Illuminate\Support\Facades\Gate::allows('order_status_change')){
            abort(404);
        }
        
        $result = \App\Actions\Orders\ChangeOrderStatusAction::execute($orderId, $status, $isBulk);
        
        if (!$result['success']) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $result['message']]);
            return;
        }

        if (isset($result['open_url'])) {
            $this->dispatch('open-url', [['url' => $result['open_url']]]);
        }

        if (isset($result['sms_error'])) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $result['sms_error'], 'title' => 'SMS Error']);
        } else {
            $this->dispatch('alert', ['type' => 'success', 'message' => $result['message']]);
        }
        
        $this->reloadOrders();
    }

    public function deleteOrder($order)
    {
        if(!\Illuminate\Support\Facades\Gate::allows('order_delete')){
            abort(404);
        }

        $order = Order::whereId($order)->first();
        if ($order) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
                $order->deleted_by = Auth::id();
                $order->save();
                
                OrderDetail::where('order_id', $order->id)->delete();
                OrderAddonDetail::where('order_id', $order->id)->delete();
                Payment::where('order_id', $order->id)->delete();
                $order->delete();
            });
            $this->reloadOrders();
        }
        $this->dispatch(
            'alert',
            ['type' => 'success',  'message' => 'Order has been moved to Recycle Bin!']
        );
    }
}
