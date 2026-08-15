<?php

namespace App\Livewire\Customers;

use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Customer;
use App\Models\Translation;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\Cursor;
use Auth;
use App\Exports\CustomersExport;
use App\Models\Order;
use Excel;
use App\DTOs\CustomerData;
use App\Actions\Customers\CreateCustomerAction;
use App\Actions\Customers\UpdateCustomerAction;

class CustomersList extends Component
{
    #[Title('Customers')]
    public $customers;
    public $name;
    public $email;
    public $tax_number;
    public $is_active = 1;
    public $phone;
    public $address;
    public $search;
    public $lang;
    public $customer;
    public $editMode = false;
    public $nextCursor;
    protected $currentCursor;
    public $hasMorePages;
    public $created_by;
    public $staffs;

    /* called before render */
    public function mount()
    {
        if (!\Illuminate\Support\Facades\Gate::allows('customer_list')) {
            abort(404);
        }
        $this->customers = new EloquentCollection();
        $this->staffs = \App\Models\User::latest()->get();
        $this->loadCustomers();

        if (session()->has('selected_language')) { /* if session has selected laugage*/
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }
    }
    /* render the page */
    public function render()
    {
        return view('livewire.customers.customers-list');
    }
    /* reset input file */
    public function resetInputFields()
    {
        $this->customer = '';
        $this->phone = '';
        $this->email = '';
        $this->tax_number = '';
        $this->address = '';
        $this->name = '';
        $this->is_active = 1;
        $this->created_by = \Illuminate\Support\Facades\Auth::id();
        $this->resetErrorBag();
    }
    /* store customer data */
    public function store()
    {
        /* if edit mode is false */

        /* rule settings */
        $this->validate([
            'name' => 'required',
            'email' => 'nullable|email',
            'phone' => 'required|unique:customers,phone,NULL,id,deleted_at,NULL',
        ]);

        $dto = new CustomerData(
            name: $this->name,
            phone: $this->phone,
            email: empty($this->email) ? null : $this->email,
            tax_number: empty($this->tax_number) ? null : $this->tax_number,
            address: empty($this->address) ? null : $this->address,
            is_active: $this->is_active ? 1 : 0
        );
        $adminAssigned = $this->created_by ? (int)$this->created_by : null;
        $userId = \Illuminate\Support\Facades\Auth::user()->user_type == 1 ? $adminAssigned : \Illuminate\Support\Facades\Auth::id();
        CreateCustomerAction::execute($dto, $userId);

        $this->reloadCustomers();
        $this->resetInputFields();
        $this->dispatch('closemodal');
        $this->dispatch(
            'alert',
            ['type' => 'success',  'message' => 'Customer has been created!']
        );
    }
    /* process while update */
    public function updated($name, $value)
    {
        if ($name == 'search') {
            $this->reloadCustomers();
        }
        /*if the updated element is address */
        if ($name == 'address' && $value != '') {
            $this->address = $value;
        }
    }
    /* view customer details to update */
    public function edit($id)
    {
        $this->resetErrorBag();
        $this->editMode = true;
        $this->customer = Customer::where('id', $id)->first();
        if (!$this->customer) {
            return;
        }
        $viewable_ids = \Illuminate\Support\Facades\Auth::user()->getViewableCustomerUserIds();
        if ($viewable_ids !== 'all' && !in_array($this->customer->created_by, $viewable_ids)) {
            abort(403, 'Unauthorized access to edit this customer.');
        }

        $this->phone = $this->customer->phone;
        $this->email = $this->customer->email;
        $this->tax_number = $this->customer->tax_number;
        $this->address = $this->customer->address;
        $this->name = $this->customer->name;
        $this->is_active = $this->customer->is_active;
        $this->created_by = $this->customer->created_by;
    }
    /* update customer details */
    public function update()
    {

        /* rule validation */
        $this->validate([
            'phone' => 'required|unique:customers,phone,' . $this->customer->id . ',id,deleted_at,NULL',
            'email' => 'nullable|email',
        ]);

        $dto = new CustomerData(
            name: $this->name,
            phone: $this->phone,
            email: empty($this->email) ? null : $this->email,
            tax_number: empty($this->tax_number) ? null : $this->tax_number,
            address: empty($this->address) ? null : $this->address,
            is_active: $this->is_active ? 1 : 0
        );
        $adminAssigned = $this->created_by ? (int)$this->created_by : null;
        $userId = \Illuminate\Support\Facades\Auth::user()->user_type == 1 ? $adminAssigned : null;
        UpdateCustomerAction::execute($this->customer, $dto, $userId);
        $this->refresh();
        $this->resetInputFields();
        $this->editMode = false;
        $this->dispatch('closemodal');
        $this->dispatch(
            'alert',
            ['type' => 'success',  'message' => 'Customer has been updated!']
        );
    }
    /* refresh the page */
    public function refresh()
    {
        /* if search query or order filter is empty */
        if ($this->search == '') {
            $this->customers = $this->customers->fresh();
        }
    }
    public function loadCustomers()
    {
        if ($this->hasMorePages !== null  && !$this->hasMorePages) {
            return;
        }
        $customerlist = $this->filterdata();
        $this->customers->push(...$customerlist->items());
        if ($this->hasMorePages = $customerlist->hasMorePages()) {
            $this->nextCursor = $customerlist->nextCursor()->encode();
        }
        $this->currentCursor = $customerlist->cursor();
    }
    public function filterdata()
    {
        $query = \App\Models\Customer::with('creator');

        $viewable_ids = \Illuminate\Support\Facades\Auth::user()->getViewableCustomerUserIds();
        if ($viewable_ids !== 'all') {
            $query->whereIn('created_by', $viewable_ids);
        }

        if ($this->search && $this->search != '') {
            $query->where('name', 'like', '%' . sanitize_search($this->search) . '%');
        }

        return $query->latest()->cursorPaginate(10, ['*'], 'cursor', Cursor::fromEncoded($this->nextCursor));
    }
    public function reloadCustomers()
    {
        $this->customers = new EloquentCollection();
        $this->nextCursor = null;
        $this->hasMorePages = null;
        if ($this->hasMorePages !== null  && !$this->hasMorePages) {
            return;
        }
        $customers = $this->filterdata();
        $this->customers->push(...$customers->items());
        if ($this->hasMorePages = $customers->hasMorePages()) {
            $this->nextCursor = $customers->nextCursor()->encode();
        }
        $this->currentCursor = $customers->cursor();
    }
    public function downloadFile()
    {
        $viewable_ids = \Illuminate\Support\Facades\Auth::user()->getViewableCustomerUserIds();
        return Excel::download(new CustomersExport($viewable_ids), 'customers_list.xlsx');
    }
    /* delete the service */
    public function delete($id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return;
        }

        $viewable_ids = \Illuminate\Support\Facades\Auth::user()->getViewableCustomerUserIds();
        if ($viewable_ids !== 'all' && !in_array($customer->created_by, $viewable_ids)) {
            abort(403, 'Unauthorized access to delete this customer.');
        }

        try {
            $customer->delete();
            $this->reloadCustomers();
            $this->dispatch('alert', ['type' => 'success', 'message' => 'Customer moved to Recycle Bin!']);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('alert', ['type' => 'error', 'message' => 'Cannot remove customer.']);
        }
    }
}
