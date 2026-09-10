<?php

namespace App\Livewire\Orders;

use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Customer;
use App\Models\MasterSettings;
use App\Models\OrderRequest;
use App\Models\Service;
use App\Models\Translation;
use Illuminate\Support\Facades\Auth;

class ViewOrderRequest extends Component
{
    public $request;
    public $payload;
    public $details = [];
    public $addons = [];
    public $payments = [];
    public $customer;
    public $lang;
    public $sitename;
    public $address;
    public $phone;
    public $zipcode;
    public $tax_number;
    public $store_email;

    #[Title('View Order Request')]
    public function render()
    {
        return view('livewire.orders.view-order-request');
    }

    public function mount($id)
    {
        $this->request = OrderRequest::findOrFail($id);

        // Permission check: same logic as OrderRequestsList.loadRequests()
        // User must be the creator, or have accept_reject_order / view_all_requests permission
        if (
            Auth::id() != $this->request->created_by
            && !Auth::user()->hasPermission('accept_reject_order')
            && !Auth::user()->hasPermission('view_all_requests')
        ) {
            abort(404);
        }

        $this->payload = $this->request->payload;

        // Load details (cart items) from payload
        if (isset($this->payload['details'])) {
            foreach ($this->payload['details'] as $item) {
                $service = Service::find($item['service_id'] ?? null);
                $this->details[] = [
                    'service_name' => $service->service_name ?? 'Unknown Service',
                    'service_icon' => $service->icon ?? null,
                    'type_name' => $item['service_name'] ?? '-',
                    'color_code' => $item['color_code'] ?? '',
                    'price' => $item['service_price'] ?? 0,
                    'quantity' => $item['service_quantity'] ?? 1,
                    'total' => $item['service_detail_total'] ?? 0,
                ];
            }
        }

        // Load addons from payload
        if (isset($this->payload['addons'])) {
            foreach ($this->payload['addons'] as $addon) {
                $this->addons[] = [
                    'name' => $addon['addon_name'] ?? 'Addon',
                    'price' => $addon['addon_price'] ?? 0,
                ];
            }
        }

        // Load payments from payload
        if (isset($this->payload['payments'])) {
            foreach ($this->payload['payments'] as $payment) {
                $this->payments[] = [
                    'type' => $payment['payment_type'] ?? 1,
                    'amount' => $payment['amount'] ?? 0,
                    'notes' => $payment['notes'] ?? '',
                ];
            }
        }

        // Load customer
        $customerId = $this->payload['customer_id'] ?? null;
        if ($customerId) {
            $this->customer = Customer::find($customerId);
        }

        // Load store settings (same pattern as ViewOrder)
        $settings = new MasterSettings();
        $site = $settings->siteData();
        $this->sitename = ($site['default_application_name'] ?? '') ?: 'Tidy LMS';
        $this->phone = ($site['default_phone_number'] ?? '') ?: '';
        $this->address = ($site['default_address'] ?? '') ?: '';
        $this->zipcode = ($site['default_zip_code'] ?? '') ?: '';
        $this->tax_number = ($site['store_tax_number'] ?? '') ?: '';
        $this->store_email = ($site['store_email'] ?? '') ?: '';

        // Load translation
        $this->lang = getSessionTranslation() ?? Translation::where('default', 1)->first();
    }
}
