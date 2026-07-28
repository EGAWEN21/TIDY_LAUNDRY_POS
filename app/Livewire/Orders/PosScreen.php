<?php

namespace App\Livewire\Orders;

use Livewire\Component;

use App\Models\Addon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceDetail;
use App\Models\ServiceType;
use App\Models\OrderAddonDetail;
use App\Models\Translation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

class PosScreen extends Component
{
    use Traits\ManagesCart, Traits\ManagesPayments, Traits\ManagesCustomers;
    public $services, $search_query, $order_id, $inputs = [], $selservices = [], $customer, $date, $delivery_date, $discount, $paid_amount, $payment_type = 1;
    public $payment_notes, $service_types, $service, $inputi, $prices = [], $selling_price = [], $quantity = [], $selected_type = [], $addons, $selected_addons = [], $colors = [];
    public $customer_name, $customer_phone, $email, $tax_no, $address, $selected_customer, $customers, $customer_query, $is_active = 1;
    public $total, $sub_total, $addon_total, $tax_percent, $tax, $balance, $flag = 0, $lang,$taxamount;
    public $taxable,$order, $request_id;
    public $payments = [],$payment_amount,$notes;

    #[Layout('components.layouts.pos'),Title('POS')]
    public function render()
    {
        return view('livewire.orders.pos-screen');
    }

    public function mount($id = null)
    {
        $isRequestEdit = request()->routeIs('orders.requests.edit');

        if ($id && !$isRequestEdit && !Auth::user()->hasPermission('order_edit')) {
            abort(403);
        }

        if (!$id && !Auth::user()->hasPermission('order_create')) {
            abort(403);
        }
        $this->services = Service::where('is_active', 1)->latest()->get();
        $this->date = Carbon::today()->toDateString();
        $this->addons = Addon::where('is_active', 1)->latest()->get();
        $this->delivery_date = Carbon::today()->addDays(2)->toDateString();
        $this->tax_percent = getTaxPercentage();
        
        // Remove prospective ID guessing to prevent race conditions.
        // True ID is securely generated inside CreateOrderAction lockForUpdate.
        $this->order_id = '[New Order]';

        if (request()->routeIs('orders.requests.edit') && $id) {
            $this->request_id = $id;
            $req = \App\Models\OrderRequest::findOrFail($id);
            if (!Auth::user()->hasPermission('accept_reject_order') && !Auth::user()->hasPermission('edit_pending_requests') && $req->created_by != Auth::id()) {
                abort(403);
            }
            $payload = $req->payload;
            
            if (isset($payload['payments'])) {
                foreach($payload['payments'] as $payment){
                    array_push($this->payments,[
                        'payment_id' => $payment['payment_id'] ?? null,
                        'payment_type' => $payment['payment_type'],
                        'amount' => $payment['amount'],
                        'notes' => $payment['notes']
                    ]);
                }
            }
            if (isset($payload['customer_id']) && $payload['customer_id'] != NULL) {
                $this->selectCustomer($payload['customer_id']);
            }
            
            if (isset($payload['details'])) {
                foreach ($payload['details'] as $row) {
                    $this->add($this->inputi);
                    $service = Service::where('id', $row['service_id'])->first();
                    
                    if ($service) {
                        $typeIds = $row['service_type_ids'] ?? null;

                        if (!empty($typeIds) && is_array($typeIds)) {
                            // COMPOSITE: Use stored IDs
                            $this->selservices[$this->inputi]['service'] = $service->id;
                            $this->selservices[$this->inputi]['service_types'] = $typeIds;
                        } else {
                            // LEGACY: Reverse-lookup by name
                            $serviceType = ServiceType::where('service_type_name', $row['service_name'])->first();
                            $this->selservices[$this->inputi]['service'] = $service->id;
                            $this->selservices[$this->inputi]['service_types'] = $serviceType ? [$serviceType->id] : [];
                        }
                        
                        $this->selling_price[$this->inputi] = $row['service_price'];
                        $this->colors[$this->inputi] = $row['color_code'] ?? '#000000';
                        
                        if ($payload['tax_type'] == 2) {
                            $itemtotallocal = $row['service_price'] * (100 / (100 + ($this->tax_percent ?? 0)));
                            $this->prices[$this->inputi] = number_format($itemtotallocal, 2);
                        } else {
                            $this->prices[$this->inputi] = $row['service_price'];
                        }
                        
                        $this->quantity[$this->inputi] = $row['service_quantity'];
                    }
                }
            }
            $this->delivery_date = Carbon::parse($payload['delivery_date'])->toDateString();
            $this->date = Carbon::parse($payload['order_date'])->toDateString();
            $this->order_id = $req->request_number;
            $this->payment_notes = $payload['note'] ?? '';
            $this->discount = $payload['discount'] ?? 0;
            
            if (isset($payload['addons'])) {
                foreach ($payload['addons'] as $row) {
                    $this->selected_addons[$row['addon_id']] = true;
                }
            }
        } elseif($id) {
            $this->order = Order::whereId($id)->firstOrFail();
            $payments = Payment::where('order_id', $this->order->id)->get();
            foreach($payments as $payment){
                array_push($this->payments,[
                    'payment_id' => $payment->id,
                    'payment_type' => $payment->payment_type,
                    'amount' => $payment->received_amount,
                    'notes' => $payment->payment_note
                ]);
            }
            if ($this->order->customer_id && $this->order->customer_id != NULL) {
                $this->selectCustomer($this->order->customer_id);
            }
            foreach ($this->order->details as $row) {
                $this->editItem($row);
            }
            $this->delivery_date = Carbon::parse($this->order->delivery_date)->toDateString();
            $this->date = Carbon::parse($this->order->order_date)->toDateString();
            $this->order_id = $this->order->order_number;
            $this->payment_notes = $this->order->note;
            $this->discount = $this->order->discount;
            foreach ($this->order->addons as $row) {
                $this->selected_addons[$row->addon_id] = true;
            }
            
        } else {
            $draft = \App\Models\PosDraft::where('user_id', Auth::id())->first();
            if ($draft && isset($draft->payload)) {
                $payload = $draft->payload;
                $this->selservices = $payload['selservices'] ?? [];
                $this->inputs = $payload['inputs'] ?? [];
                $this->inputi = $payload['inputi'] ?? 0;
                $this->prices = $payload['prices'] ?? [];
                $this->selling_price = $payload['selling_price'] ?? [];
                $this->quantity = $payload['quantity'] ?? [];
                $this->selected_type = $payload['selected_type'] ?? [];
                $this->selected_addons = $payload['selected_addons'] ?? [];
                $this->colors = $payload['colors'] ?? [];
                if (isset($payload['customer_id'])) {
                    $this->selectCustomer($payload['customer_id']);
                }
                $this->discount = $payload['discount'] ?? 0;
                $this->payments = $payload['payments'] ?? [];
                $this->payment_notes = $payload['payment_notes'] ?? '';
            }
        }
        $this->lang = getSessionTranslation();
        $this->service_types = collect();
        $this->calculateTotal();
    }
    /* process while update element */
    public function updated($name, $value)
    {

        /* if updated value is empty set the value as null */
        if ($value == '') data_set($this, $name, null);
        /* if updated elemtnt is search_query */
        if ($name == 'search_query' && $value != '') {
            $this->services = Service::where('service_name', 'like', '%' . sanitize_search($value) . '%')->latest()->get();
        } elseif ($name == 'search_query' && $value == '') {
            $this->services = Service::latest()->get();
        }
        /* if the updated value is customer_query */
        if ($name == 'customer_query' && $value != '') {
            $this->customers = Customer::where(function ($query) use ($value) {
                $query->where('name', 'like', '%' . sanitize_search($value) . '%')->orWhere('phone', 'like', '%' . sanitize_search($value) . '%');
            })->latest()->limit(5)->get();
        } elseif ($name == 'customer_query' && $value == '') {
            $this->customers = collect();
        }

        if ($name == 'discount' || strpos($name, 'selling_price') !== false || strpos($name, 'prices') !== false || strpos($name, 'quantity') !== false) {
            $this->calculateTotal();
        }
        if ($name == 'date' && $value != '') {
            $this->delivery_date = Carbon::parse($value)->addDays(2)->toDateString();
        }
        $this->calculateTotal();
    }

    /* legacy generateOrderID removed - securely handled in CreateOrderAction */
    public function generateOrderID()
    {
        // Method intentionally left blank or removed, as Livewire UI should not guess IDs.
    }
    /* calculate service total using enterprise action */
    public function calculateTotal()
    {
        $cartItems = [];
        foreach ($this->selling_price as $key => $value) {
            $cartItems[] = new \App\DTOs\CartItemData(
                service_id: $this->selservices[$key]['service'] ?? 0,
                service_price: (float) $value,
                service_quantity: (int) ($this->quantity[$key] ?? 1),
                service_detail_total: (float) ($value * ($this->quantity[$key] ?? 1)),
                service_name: null,
                color_code: $this->colors[$key] ?? null
            );
        }

        $addonTotal = 0;
        if ($this->selected_addons) {
            foreach ($this->selected_addons as $key => $value) {
                if ($value === true) {
                    $addon = Addon::where('id', $key)->first();
                    if ($addon) {
                        $addonTotal += $addon->addon_price;
                    }
                }
            }
        }

        $totals = \App\Actions\Orders\CalculateCartTotals::execute(
            cartItems: $cartItems,
            addonTotal: $addonTotal,
            discount: (float) ($this->discount ?? 0)
        );

        $this->sub_total = $totals['sub_total'];
        $this->addon_total = $totals['addon_total'];
        $this->discount = $totals['discount'];
        $this->tax_percent = $totals['tax_percentage'];
        $this->tax = $totals['tax_amount'];
        $this->taxable = $totals['taxable_amount'];
        $this->total = $totals['total'];
        
        $this->balance = $this->total - $this->paid_amount;
        
        if (!$this->order && !$this->request_id) {
            $draftPayload = [
                'selservices' => $this->selservices,
                'inputs' => $this->inputs,
                'inputi' => $this->inputi,
                'prices' => $this->prices,
                'selling_price' => $this->selling_price,
                'quantity' => $this->quantity,
                'selected_type' => $this->selected_type,
                'selected_addons' => $this->selected_addons,
                'colors' => $this->colors,
                'customer_id' => $this->selected_customer->id ?? null,
                'discount' => $this->discount,
                'payments' => $this->payments,
                'payment_notes' => $this->payment_notes,
            ];
            \App\Models\PosDraft::updateOrCreate(
                ['user_id' => Auth::id()],
                ['payload' => $draftPayload]
            );
        }
    }
    //add payment
    /* save the order */
    public function save($type = null)
    {
        if ($this->order && !Auth::user()->hasPermission('order_edit')) {
            abort(403);
        }

        $amount = 0;
        if($type === 'cash'){
            $this->payments = [];
            array_push($this->payments,[
                'amount' => $this->total,
                'notes' => $this->payment_notes,
                'payment_type' => $this->payment_type,
                'payment_id' => null
            ]);
        }
        $this->calculateTotal();

        $this->validate([
            'payment_type'  => 'required'
        ]);
        /* if selected services > 0  send error alert*/
        if (count($this->selservices) <= 0) {
            $this->dispatch(
                'alert',
                ['type' => 'error',  'message' => ' You have not added any service to the cart']
            );
            $this->addError('error', 'Select a service');
            return 0;
        }
        $balance = $this->getPaymentBalance();
        /* if balance is <0 send error alert*/
        if ($balance < 0) {
            $this->dispatch(
                'alert',
                ['type' => 'error',  'message' => ' Paid Amount cannot be greater than total.']
            );
            $this->addError('paid_amount', 'Paid Amount cannot be greater than total.');
            return 0;
        }
        /* if customer not exist and has any balance to pay send the error alert */
        if ($balance != 0 && $this->selected_customer == null) {
            $this->addError('paid_amount_customer', 'The customer must be registered to use ledger.');
            return 0;
        }
        $this->generateOrderID();
        
        $payload = [
            'customer_id' => $this->selected_customer->id ?? null,
            'customer_name' => $this->selected_customer->name ?? null,
            'phone_number' => $this->selected_customer->phone ?? null,
            'order_date' => Carbon::parse($this->date)->toDateTimeString(),
            'delivery_date' => Carbon::parse($this->delivery_date)->toDateTimeString(),
            'sub_total' => $this->sub_total,
            'addon_total' => $this->addon_total,
            'discount' => $this->discount ?? 0,
            'tax_percentage' => $this->tax_percent,
            'tax_amount' => $this->tax,
            'tax_type' => getTaxType(),
            'taxable_amount' => $this->taxable,
            'total' => $this->total,
            'note' => $this->payment_notes,
            'details' => [],
            'addons' => [],
            'payments' => []
        ];
        
        foreach ($this->selservices as $key => $value) {
            $service = Service::where('id', $value['service'])->first();
            
            // Support both composite (service_types array) and legacy (service_type single int)
            $typeIds = $value['service_types'] ?? (isset($value['service_type']) ? [$value['service_type']] : []);
            $typeNames = [];
            foreach ($typeIds as $typeId) {
                if ($typeId) {
                    $st = ServiceType::where('id', $typeId)->first();
                    if ($st) {
                        $typeNames[] = $st->service_type_name;
                    }
                }
            }

            $payload['details'][] = [
                'service_id' => $service->id,
                'service_name' => implode(', ', $typeNames),
                'service_type_ids' => $typeIds,
                'service_quantity' => $this->quantity[$key],
                'service_detail_total' => $this->selling_price[$key] * $this->quantity[$key],
                'service_price' => $this->selling_price[$key],
                'color_code' => $this->colors[$key] ?? null,
            ];
        }
        
        if ($this->selected_addons) {
            foreach ($this->selected_addons as $key => $value) {
                if ($value === true) {
                    $addon = Addon::where('id', $key)->first();
                    $payload['addons'][] = [
                        'addon_id' => $addon->id,
                        'addon_name' => $addon->addon_name,
                        'addon_price' => $addon->addon_price,
                    ];
                }
            }
        }
        
        if (count($this->payments) > 0) {
            foreach ($this->payments as $payment) {
                $payload['payments'][] = [
                    'payment_id' => $payment['payment_id'] ?? null,
                    'payment_type' => $payment['payment_type'],
                    'amount' => $payment['amount'],
                    'notes' => $payment['notes'] ?? "Notes"
                ];
            }
        }

        // 1. Build the strictly typed DTO
        $orderDto = \App\DTOs\OrderData::from([
            'customer_id' => $payload['customer_id'],
            'customer_name' => $payload['customer_name'],
            'phone_number' => $payload['phone_number'],
            'order_date' => $payload['order_date'],
            'delivery_date' => $payload['delivery_date'],
            'sub_total' => $payload['sub_total'],
            'addon_total' => $payload['addon_total'],
            'discount' => $payload['discount'],
            'tax_percentage' => $payload['tax_percentage'],
            'tax_amount' => $payload['tax_amount'],
            'tax_type' => $payload['tax_type'],
            'taxable_amount' => $payload['taxable_amount'],
            'total' => $payload['total'],
            'note' => $payload['note'],
            'status' => 0,
            'details' => $payload['details'],
            'addons' => $payload['addons'],
            'payments' => $payload['payments']
        ]);
        
        // Securely recalculate the cart totals based on user permissions
        try {
            $orderDto = \App\Actions\Orders\CalculateSecureOrderMathAction::execute($orderDto, Auth::user());
        } catch (\Exception $e) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $e->getMessage()]);
            return 0;
        }
        
        // Update the raw array payload so OrderRequest receives the secured math
        $payload = $orderDto->toArray();

        $canBypass = Auth::user()->hasPermission('bypass_order_approval') || Auth::user()->hasPermission('accept_reject_order');
        if (!$canBypass && Auth::user()->hasPermission('bypass_approval_under_limit') && $orderDto->total <= getBypassLimit()) {
            $canBypass = true;
        }

        if ($this->flag == 0 && $this->order) {
            try {
                $order = \App\Actions\Orders\UpdateOrderAction::execute($orderDto, $this->order, Auth::id());
                $this->flag = 1;
                $this->dispatch('alert', ['type' => 'success',  'message' => $order->order_number . ' Was Successfully Updated!']);
                if(\Illuminate\Support\Facades\Gate::allows('order_print')){
                    $this->dispatch('printPageOrder', $order->id);
                }
            } catch (\Exception $e) {
                $this->dispatch('alert', ['type' => 'error',  'message' => 'Failed to update order: ' . $e->getMessage()]);
            }
        } else {
            // New Order or Request
            if ($canBypass) {
                try {
                    // 2. Dispatch to the secure Action
                    $order = \App\Actions\Orders\CreateOrderAction::execute($orderDto, Auth::id());
                    
                    $this->order_id = $order->order_number;
                    
                    if ($this->request_id) {
                        \App\Models\OrderRequest::whereId($this->request_id)->delete();
                    }
                    
                    // SMS is now handled completely asynchronously by SendOrderNotifications Event Listener.
                    // We no longer block the main thread or risk rolling back the DB here!
                    
                    $this->dispatch('alert', ['type' => 'success',  'message' => $order->order_number . ' Was Successfully Created!']);
                    
                    if(\Illuminate\Support\Facades\Gate::allows('order_print')){
                        $this->dispatch('printPage', $order->id);
                        $this->clearAll();
                    } else {
                        $this->clearAll();
                    }
                } catch (\Exception $e) {
                    $this->dispatch('alert', ['type' => 'error',  'message' => 'Failed to create order: ' . $e->getMessage()]);
                }
            } else {
                if ($this->request_id) {
                    \App\Models\OrderRequest::whereId($this->request_id)->update([
                        'payload' => $payload,
                        'status' => 0,
                        'rejection_note' => null,
                        'total_amount' => $orderDto->total,
                        'customer_id' => $this->selected_customer->id ?? null,
                        'customer_name' => $this->selected_customer->name ?? null,
                    ]);
                    $this->dispatch('alert', ['type' => 'success',  'message' => 'Order Request Updated!']);
                } else {
                    \App\Models\OrderRequest::create([
                        'created_by' => Auth::id(),
                        'customer_id' => $this->selected_customer->id ?? null,
                        'customer_name' => $this->selected_customer->name ?? null,
                        'total_amount' => $orderDto->total,
                        'payload' => $payload,
                        'status' => 0,
                    ]);
                    $this->dispatch('alert', ['type' => 'success',  'message' => 'Order Request Submitted for Approval!']);
                }
                
                // Notify Managers
                $managers = \App\Models\User::where('user_type', 1)->orWhereHas('role', function($q) {
                    $q->whereHas('permissions', function($p) {
                        $p->where('permission_name', 'accept_reject_order');
                    });
                })->get();
                
                foreach($managers as $manager) {
                    $manager->notify(new \App\Notifications\SystemNotification(
                        'New Online Order Request',
                        "A new online order request requires your approval.",
                        route('orders.requests')
                    ));
                }
                
                $this->clearAll();
            }
        }
    }

    //Reload page on clicking clearall
    public function clearAll()
    {
        \App\Models\PosDraft::where('user_id', Auth::id())->delete();
        $this->dispatch('reloadpage');
    }
}