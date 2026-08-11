<?php

namespace App\Livewire\Orders\Traits;

use App\Models\Customer;

trait ManagesCustomers
{
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
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
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
}
