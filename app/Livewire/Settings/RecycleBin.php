<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\OrderAddonDetail;
use App\Models\Customer;
use App\Models\User;
use App\Models\Translation;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class RecycleBin extends Component
{
    public $orders;
    public $customers;
    public $staff;
    public $search_query;
    public $selectedItems = [];
    public $lang;
    public $currentTab = 'orders';

    #[Title('Recycle Bin')]
    public function render()
    {
        return view('livewire.settings.recycle-bin');
    }

    public function mount()
    {
        $canOrders = Gate::allows('order_restore') || Gate::allows('order_force_delete');
        $canCustomers = Gate::allows('customer_restore') || Gate::allows('customer_force_delete');
        $canStaff = Gate::allows('user_restore') || Gate::allows('user_force_delete');

        if (!$canOrders && !$canCustomers && !$canStaff) {
            abort(404);
        }

        if ($canOrders) {
            $this->currentTab = 'orders';
        } elseif ($canCustomers) {
            $this->currentTab = 'customers';
        } else {
            $this->currentTab = 'staff';
        }

        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }

        $this->loadData();
    }

    public function switchTab($tab)
    {
        $this->currentTab = $tab;
        $this->selectedItems = [];
        $this->search_query = '';
        $this->loadData();
    }

    public function loadData()
    {
        if ($this->currentTab == 'orders') {
            $this->loadTrashedOrders();
        } elseif ($this->currentTab == 'customers') {
            $this->loadTrashedCustomers();
        } elseif ($this->currentTab == 'staff') {
            $this->loadTrashedStaff();
        }
    }

    public function loadTrashedOrders()
    {
        $query = Order::onlyTrashed()->with('details', 'addons', 'deletedBy')->orderBy('deleted_at', 'DESC');

        if ($this->search_query) {
            $searchQuery = $this->search_query;
            $query->where(function ($q) use ($searchQuery) {
                $q->where('order_number', 'like', '%' . sanitize_search($searchQuery) . '%')
                  ->orWhere('customer_name', 'like', '%' . sanitize_search($searchQuery) . '%')
                  ->orWhere('phone_number', 'like', '%' . sanitize_search($searchQuery) . '%');
            });
        }

        $this->orders = $query->get()->map(function ($order) {
            $order->days_remaining = 90 - now()->diffInDays($order->deleted_at);
            $order->paid_amount = Payment::withTrashed()->where('order_id', $order->id)->sum('received_amount');
            return $order;
        });
    }

    public function loadTrashedCustomers()
    {
        $query = Customer::onlyTrashed()->orderBy('deleted_at', 'DESC');
        if ($this->search_query) {
            $query->where('name', 'like', '%' . sanitize_search($this->search_query) . '%')
                  ->orWhere('phone', 'like', '%' . sanitize_search($this->search_query) . '%');
        }
        $this->customers = $query->get()->map(function ($c) {
            $c->days_remaining = 90 - now()->diffInDays($c->deleted_at);
            return $c;
        });
    }

    public function loadTrashedStaff()
    {
        $query = User::onlyTrashed()->with('role')->where('user_type', 2)->orderBy('deleted_at', 'DESC');
        if ($this->search_query) {
            $query->where('name', 'like', '%' . sanitize_search($this->search_query) . '%')
                  ->orWhere('email', 'like', '%' . sanitize_search($this->search_query) . '%');
        }
        $this->staff = $query->get()->map(function ($s) {
            $s->days_remaining = 90 - now()->diffInDays($s->deleted_at);
            return $s;
        });
    }

    public function updated($name, $value)
    {
        if ($name == 'search_query') {
            $this->loadData();
        }
    }

    public function bulkRestore()
    {
        if (empty($this->selectedItems)) {
            return;
        }

        if ($this->currentTab == 'orders') {
            if (!Gate::allows('order_restore')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            DB::transaction(function () {
                foreach ($this->selectedItems as $id) {
                    $order = Order::onlyTrashed()->find($id);
                    if ($order) {
                        $order->restore();
                        OrderDetail::onlyTrashed()->where('order_id', $id)->restore();
                        OrderAddonDetail::onlyTrashed()->where('order_id', $id)->restore();
                        Payment::onlyTrashed()->where('order_id', $id)->restore();
                    }
                }
            });
        } elseif ($this->currentTab == 'customers') {
            if (!Gate::allows('customer_restore')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            Customer::onlyTrashed()->whereIn('id', $this->selectedItems)->restore();
        } elseif ($this->currentTab == 'staff') {
            if (!Gate::allows('user_restore')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            User::onlyTrashed()->whereIn('id', $this->selectedItems)->restore();
        }

        $this->selectedItems = [];
        $this->loadData();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Selected items restored successfully!']);
    }

    public function bulkForceDelete()
    {
        if (empty($this->selectedItems)) {
            return;
        }

        if ($this->currentTab == 'orders') {
            if (!Gate::allows('order_force_delete')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            DB::transaction(function () {
                foreach ($this->selectedItems as $id) {
                    $order = Order::onlyTrashed()->find($id);
                    if ($order) {
                        OrderDetail::onlyTrashed()->where('order_id', $id)->forceDelete();
                        OrderAddonDetail::onlyTrashed()->where('order_id', $id)->forceDelete();
                        Payment::onlyTrashed()->where('order_id', $id)->forceDelete();
                        $order->forceDelete();
                    }
                }
            });
        } elseif ($this->currentTab == 'customers') {
            if (!Gate::allows('customer_force_delete')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            Customer::onlyTrashed()->whereIn('id', $this->selectedItems)->forceDelete();
        } elseif ($this->currentTab == 'staff') {
            if (!Gate::allows('user_force_delete')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            User::onlyTrashed()->whereIn('id', $this->selectedItems)->forceDelete();
        }

        $this->selectedItems = [];
        $this->loadData();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Selected items permanently deleted!']);
    }

    public function emptyRecycleBin()
    {
        if ($this->currentTab == 'orders') {
            if (!Gate::allows('order_force_delete')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            DB::transaction(function () {
                $orders = Order::onlyTrashed()->get();
                foreach ($orders as $order) {
                    OrderDetail::onlyTrashed()->where('order_id', $order->id)->forceDelete();
                    OrderAddonDetail::onlyTrashed()->where('order_id', $order->id)->forceDelete();
                    Payment::onlyTrashed()->where('order_id', $order->id)->forceDelete();
                    $order->forceDelete();
                }
            });
        } elseif ($this->currentTab == 'customers') {
            if (!Gate::allows('customer_force_delete')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            Customer::onlyTrashed()->forceDelete();
        } elseif ($this->currentTab == 'staff') {
            if (!Gate::allows('user_force_delete')) {
                $this->dispatch('alert', ['type' => 'error', 'message' => 'Unauthorized!']);
                return;
            }
            User::onlyTrashed()->where('user_type', 2)->forceDelete();
        }

        $this->selectedItems = [];
        $this->loadData();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Recycle bin emptied for this section!']);
    }

    public function restoreItem($id)
    {
        $this->selectedItems = [$id];
        $this->bulkRestore();
    }

    public function forceDeleteItem($id)
    {
        $this->selectedItems = [$id];
        $this->bulkForceDelete();
    }
}
