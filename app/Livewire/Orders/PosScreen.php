<?php

namespace App\Livewire\Orders;

use App\Livewire\Installer\InstallController;
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
        if(!\Illuminate\Support\Facades\Gate::allows('order_create')){
            abort(404);
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
                    $serviceType = ServiceType::where('service_type_name', $row['service_name'])->first();
                    
                    if ($service) {
                        $this->selservices[$this->inputi]['service'] = $service->id;
                        $this->selservices[$this->inputi]['service_type']  = $serviceType?->id;
                        
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
                    'notes' => $payment->notes
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
            $this->payment_notes = $this->order->notes;
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
        if (session()->has('selected_language')) {
            /* if session has selected language */
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            /* if session has no selected language */
            $this->lang = Translation::where('default', 1)->first();
        }
        $this->service_types = collect();
        $this->calculateTotal();
    }

    public function editItem($row){
        $this->add($this->inputi);
        $service = Service::whereId($row->service_id)->first();
        $servicedetails = ServiceDetail::where('service_id', $service->id)->first();
        $serviceType = ServiceType::where('service_type_name',$row->service_name)->first();
        $servicedetail = $servicedetails->where('service_type_id', $serviceType?->id)->where('service_id', $service->id)->first();
        if ($servicedetail) {
            $this->selservices[$this->inputi]['service'] = $service->id;
            $this->selservices[$this->inputi]['service_type']  = $serviceType?->id;

            if ($this->order->tax_type == 2) {
                $this->selling_price[$this->inputi] =  $servicedetail->service_price;
                $itemtotallocal =   $servicedetail->service_price  * (100 / (100 + $this->tax_percent ?? 0));
                $this->prices[$this->inputi] = number_format($itemtotallocal, 2);
            } else {
                $this->prices[$this->inputi] =  $servicedetail->service_price;
                $this->selling_price[$this->inputi] =  $servicedetail->service_price;
            }

            $this->colors[$this->inputi] = $row->color_code;
            $this->prices[$this->inputi] = $row->service_price;
            $this->quantity[$this->inputi] = $row->service_quantity;
        }
        $this->calculateTotal();
    }

    public function changeColor($id)
    {
        $this->colors[$id] = $this->colors[$id];
    }
    /* process while update element */
    public function updated($name, $value)
    {

        /* if updated value is empty set the value as null */
        if ($value == '') data_set($this, $name, null);
        /* if updated elemtnt is search_query */
        if ($name == 'search_query' && $value != '') {
            $this->services = Service::where('service_name', 'like', '%' . $value . '%')->latest()->get();
        } elseif ($name == 'search_query' && $value == '') {
            $this->services = Service::latest()->get();
        }
        /* if the updated value is customer_query */
        if ($name == 'customer_query' && $value != '') {
            $this->customers = Customer::where(function ($query) use ($value) {
                $query->where('name', 'like', '%' . $value . '%')->orWhere('phone', 'like', '%' . $value . '%');
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


    /* select service */
    public function selectService($id)
    {
        $this->selected_type = [];
        $this->service = Service::where('id', $id)->first();
        $this->service_types = collect();
        if ($this->service) {
            $servicedetails = ServiceDetail::where('service_id', $id)->get();
            $serviceTypeIds = $servicedetails->pluck('service_type_id')->toArray();
            
            $serviceTypes = ServiceType::whereIn('id', $serviceTypeIds)
                ->orderBy('position', 'ASC')
                ->orderBy('id', 'ASC')
                ->get();

            foreach ($serviceTypes as $servicetype) {
                $detail = $servicedetails->where('service_type_id', $servicetype->id)->first();
                if ($detail) {
                    $servicetypeArray = $servicetype->toArray();
                    $servicetypeArray['price'] = getFormattedCurrency($detail->service_price);
                    $this->service_types->push($servicetypeArray);
                }
            }
        }
        if ($this->service_types) {
            if (count($this->service_types) > 0) {
                $first = $this->service_types->first();
                if ($first) {
                    $this->selected_type [$first['id']] = true;
                }
            }
        }
        $this->calculateTotal();
    }
    /* select services*/
    public function addItem()
    {

        if ($this->service) {
            $anyTicked = false;
            foreach($this->selected_type as $item){
                if($item == true){
                    $anyTicked = true;
                }
            }
            if (count($this->selected_type) > 0 && $anyTicked) {
                $tax_type = getTaxType();
                foreach($this->selected_type as $item => $value){
                    if($value === true){
                        $this->add($this->inputi);
                        $this->selservices[$this->inputi]['service'] = $this->service->id;
                        $this->selservices[$this->inputi]['service_type']  = $item;
                        $servicedetail = ServiceDetail::where('service_id', $this->service->id)->where('service_type_id', $item)->first();
                        /* if service details is not empty */
                        if ($servicedetail) {
                            if ($tax_type == 2) {
                                $this->selling_price[$this->inputi] =  $servicedetail->service_price;
                                $itemtotallocal =   $servicedetail->service_price  * (100 / (100 + $this->tax_percent ?? 0));
                                $this->prices[$this->inputi] = number_format($itemtotallocal, 2);
                            } else {
                                $this->prices[$this->inputi] =  $servicedetail->service_price;
                                $this->selling_price[$this->inputi] =  $servicedetail->service_price;
                            }
                        }
                    }
                }
                $this->service_types = collect();
                $this->dispatch('closemodal');
                $this->calculateTotal();
            } else {
                $this->addError('service_error', 'Select a service type');
                return 0;
            }
        }
    }
    /* add the item to array */
    public function add($i)
    {
        $this->inputi = $i + 1;
        $this->inputs[$this->inputi] = 1;
        $this->prices[$this->inputi] = 100;
        $this->service_types[$this->inputi] = '';
        $this->quantity[$this->inputi]  = 1;
        $this->colors[$this->inputi]  = '#000000';
    }
    /* increase the count */
    public function increase($key)
    {
        /* if quantity of key is exist */
        if (isset($this->quantity[$key])) {
            $this->quantity[$key]++;
            $this->calculateTotal();
        }
    }

    public function priceChange($key)
    {
        $this->calculateTotal();
    }
    /* decrease the count */
    public function decrease($key)
    {
        /* is quantity of key is exist */
        if (isset($this->quantity[$key])) {
            if ($this->quantity[$key] > 1) {
                /* if quantity of key is >1 */
                $this->quantity[$key]--;
            } else {
                /* unset the details if quantity of key is 1 */
                unset($this->quantity[$key]);
                unset($this->prices[$key]);
                unset($this->service_types[$key]);
                unset($this->selservices[$key]);
                unset($this->selling_price[$key]);
            }
            $this->calculateTotal();
        }
    }
    public function removeItem($key)
    {
        unset($this->quantity[$key]);
        unset($this->prices[$key]);
        unset($this->service_types[$key]);
        unset($this->selservices[$key]);
        unset($this->selling_price[$key]);
        $this->calculateTotal();
    }

    public function duplicateItem($key)
    {
        if (isset($this->selservices[$key])) {
            $this->add($this->inputi);
            $newKey = $this->inputi;
            $this->selservices[$newKey] = $this->selservices[$key] ?? [];
            $this->prices[$newKey] = $this->prices[$key] ?? 0;
            $this->selling_price[$newKey] = $this->selling_price[$key] ?? 0;
            $this->colors[$newKey] = $this->colors[$key] ?? '#000000';
            $this->quantity[$newKey] = $this->quantity[$key] ?? 1;
            $this->service_types = collect();
            $this->calculateTotal();
        }
    }
    /* create customer */
    public function createCustomer()
    {   /* validation */
        $this->validate([
            'customer_name'  => 'required',
            'customer_phone'    => 'required',
            'email' => 'nullable|email'

        ]);
        $customer = Customer::create([
            'name'  => $this->customer_name,
            'phone' => $this->customer_phone,
            'email' => empty($this->email) ? null : $this->email,
            'tax_number'    => empty($this->tax_no) ? null : $this->tax_no,
            'address'   => empty($this->address) ? null : $this->address,
            'is_active' => $this->is_active ?? 0,
        ]);
        $this->selected_customer = $customer;
        $this->dispatch('closemodal');
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->email    = '';
        $this->tax_no = '';
        $this->address = '';
        $this->is_active = 1;
    }
    /* select customer */
    public function selectCustomer($id)
    {
        $this->selected_customer = Customer::where('id', $id)->first();
        $this->customer_query = '';
        $this->customers = collect();
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
    public function add_payment(){
        $this->validate([
            'payment_type'  => 'required',
            'payment_amount' => 'lte:'.$this->getPaymentBalance()
        ]);

        $payment = [
            'amount' => (float)$this->payment_amount,
            'notes' => $this->notes,
            'payment_type' => $this->payment_type,
            'payment_id' => null
        ];
        $this->payment_amount = '';
        $this->notes = '';
        $this->payment_type = 1;
        array_push($this->payments,$payment);
        $this->dispatch(
            'alert',
            ['type' => 'success',  'message' => ' Payment has been created']
        );
    }

    #[Computed()]
    public function currentBalance(){
        return $this->getPaymentBalance();
    }

    /* save the order */
    public function save($type = null)
    {
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
            $service_type = ServiceType::where('id', $value['service_type'])->first();
            $payload['details'][] = [
                'service_id' => $service->id,
                'service_name' => $service_type->service_type_name,
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

    public function getPaymentBalance(){
        $orderBalance = $this->total;
        $paymentsTotal = 0;
        foreach($this->payments as $payment){
            $paymentsTotal += $payment['amount'];
        }
        return $orderBalance - $paymentsTotal;
    }

    public function magicFill()
    {
        if ($this->total) {
            $this->paid_amount = $this->total;
        } else {
            $this->paid_amount = 0;
        }
    }
    //Reload page on clicking clearall
    public function clearAll()
    {
        \App\Models\PosDraft::where('user_id', Auth::id())->delete();
        $this->dispatch('reloadpage');
    }

    //remove payment
    public function removePayment($paymentIndex){
        if (isset($this->payments[$paymentIndex]['payment_id'])) {
            $this->dispatch('alert', ['type' => 'error', 'message' => 'Historical payments cannot be deleted. Please issue a refund/void instead.']);
            return;
        }
        array_splice($this->payments,$paymentIndex,1);
    }
}