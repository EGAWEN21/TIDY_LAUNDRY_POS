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
use Illuminate\Support\Facades\Schema;
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
            $grouped[$dateKey]['total_paid'] += \App\Models\Payment::where('order_id', $order->id)->sum('received_amount');
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
    /* process while update the content */
    private function getBaseOrderQuery()
    {
        if (Auth::user()->user_type == 1 || Auth::user()->viewable_staff_orders === 'all') {
            return \App\Models\Order::query();
        }
        $viewable_ids = [Auth::user()->id];
        if (!empty(Auth::user()->viewable_staff_orders)) {
            $extra_ids = explode(',', Auth::user()->viewable_staff_orders);
            $viewable_ids = array_merge($viewable_ids, $extra_ids);
        }
        return \App\Models\Order::whereIn('created_by', $viewable_ids);
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
                    $paidAmount = Payment::where('order_id', $order->id)->sum('received_amount');

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
                    $paidAmount = Payment::where('order_id', $order->id)->sum('received_amount');

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
                    $paidAmount = Payment::where('order_id', $order->id)->sum('received_amount');

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
        $this->balance = number_format($this->order->total - $this->paid_amount, 2);
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
        /* if balance is < 0 */
        if ($this->balance < 0) {
            $this->addError('balance', 'Pls Provide Valid Amount.');
            return 0;
        }
        /* if the balance is > order total */
        if ($this->balance > $this->order->total) {
            $this->addError('balance', 'Paid Amount cannot be greater than total.');
            return 0;
        }
        if ($this->order->status == 4) {
            return 0;
        }
        $this->validate([
            'payment_mode' => 'required',
        ]);
        /* if any balance */
        if ($this->balance) {
            \App\Models\Payment::create([
                'payment_date'  => \Carbon\Carbon::today()->toDateString(),
                'customer_id'   => $this->customer->id ?? null,
                'customer_name' => $this->customer->name ?? null,
                'order_id'  => $this->order->id,
                'payment_type'  => $this->payment_mode,
                'payment_note'  => $this->note,
                'financial_year_id' => getFinancialYearId(),
                'received_amount'   => $this->balance,
                'created_by'    => Auth::user()->id,
            ]);
            $this->resetInputFields();
            $this->dispatch('closemodal');
            $this->dispatch(
                'alert',
                ['type' => 'success',  'message' => 'Payment Updated has been updated!']
            );
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

    public function changeOrderStatus($orderId, $status)
    {
        if(!\Illuminate\Support\Facades\Gate::allows('order_status_change')){
            abort(404);
        }
        
        $order = Order::find($orderId);
        if($order) {
            $order->status = $status;
            $order->save();
            
            // 1. Trigger Email Automation
            sendOrderStatusChangeEmail($order->id, $status);
            if (Auth::user()) {
                $statusText = getOrderStatus($status, true);
                Auth::user()->notify(new \App\Notifications\SystemNotification('Email Automation', "Automated Status Email triggered for Order {$order->order_number} ({$statusText})", 'info'));
            }

            // 2. Trigger WhatsApp Hybrid Automation
            $settings = new \App\Models\MasterSettings();
            $site = $settings->siteData();
            
            if (isset($site['enable_automated_whatsapp']) && $site['enable_automated_whatsapp'] == 1) {
                // Strategy 3: Burner API (Automated)
                $waService = new \App\Services\WhatsAppService();
                $messagePayload = getFormatedTextSMS($order->id, ($status == 2 ? 3 : 2));
                $waService->sendAutomatedStatusUpdate($order, $messagePayload); 
                if (Auth::user()) {
                    Auth::user()->notify(new \App\Notifications\SystemNotification('WhatsApp Automation', "Automated WhatsApp Message sent for Order {$order->order_number} via Burner API", 'success'));
                }
            } else {
                // Strategy 1: wa.me Fallback (Manual Assist)
                $customer = \App\Models\Customer::find($order->customer_id);
                if ($customer && !empty($customer->phone)) {
                    $phone = ltrim($customer->phone, '+');
                    if (!str_starts_with($phone, ltrim(getCountryCode(), '+')) && strlen($phone) <= 10) {
                        $phone = ltrim(getCountryCode(), '+') . $phone;
                    }
                    $messagePayload = getFormatedTextSMS($order->id, ($status == 2 ? 3 : 2));
                    $url = "https://wa.me/{$phone}?text=" . urlencode($messagePayload);
                    $this->dispatch('open-url', [['url' => $url]]);
                    if (Auth::user()) {
                        Auth::user()->notify(new \App\Notifications\SystemNotification('WhatsApp Fallback', "Manual wa.me link generated for Order {$order->order_number}", 'warning'));
                    }
                }
            }

            // 3. Trigger SMS Automation
            $message = sendOrderStatusChangeSMS($order->id, $status);
            if($message) {
                $this->dispatch('alert', ['type' => 'error',  'message' => $message, 'title'=>'SMS Error']);
            } else {
                $this->dispatch('alert', ['type' => 'success', 'message' => 'Status successfully updated!']);
            }
            
            $this->reloadOrders();
        }
    }

    public function deleteOrder($order)
    {
        $order = Order::whereId($order)->first();
        if ($order) {
            Schema::disableForeignKeyConstraints();
            OrderDetail::where('order_id', $order->id)->delete();
            OrderAddonDetail::where('order_id', $order->id)->delete();
            Payment::where('order_id', $order->id)->delete();
            $order->delete();
            Schema::enableForeignKeyConstraints();
            $this->reloadOrders();
        }
        $this->dispatch(
            'alert',
            ['type' => 'success',  'message' => 'Order has been deleted!']
        );
    }
}
