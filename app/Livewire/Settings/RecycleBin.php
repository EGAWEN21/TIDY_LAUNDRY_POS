<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Order;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\OrderAddonDetail;
use App\Models\Translation;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class RecycleBin extends Component
{
    public $orders;
    public $search_query;
    public $selectedOrders = [];
    public $lang;

    #[Title('Recycle Bin')]
    public function render()
    {
        return view('livewire.settings.recycle-bin');
    }

    public function mount()
    {
        if(!Gate::allows('order_restore')){
            abort(404);
        }

        if(session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }

        $this->loadTrashedOrders();
    }

    public function loadTrashedOrders()
    {
        $query = Order::onlyTrashed()->with('details', 'addons', 'deletedBy')->orderBy('deleted_at', 'DESC');

        if ($this->search_query) {
            $searchQuery = $this->search_query;
            $query->where(function($q) use ($searchQuery) {
                $q->where('order_number', 'like', '%' . $searchQuery . '%')
                  ->orWhere('customer_name', 'like', '%' . $searchQuery . '%')
                  ->orWhere('phone_number', 'like', '%' . $searchQuery . '%');
            });
        }

        $this->orders = $query->get()->map(function($order) {
            $order->days_remaining = 90 - now()->diffInDays($order->deleted_at);
            $order->paid_amount = Payment::withTrashed()->where('order_id', $order->id)->sum('received_amount');
            return $order;
        });
    }

    public function updated($name, $value)
    {
        if ($name == 'search_query') {
            $this->loadTrashedOrders();
        }
    }

    public function restoreOrder($orderId)
    {
        if(!Gate::allows('order_restore')){
            abort(404);
        }

        DB::transaction(function () use ($orderId) {
            $order = Order::onlyTrashed()->find($orderId);
            if ($order) {
                $order->restore();
                OrderDetail::onlyTrashed()->where('order_id', $orderId)->restore();
                OrderAddonDetail::onlyTrashed()->where('order_id', $orderId)->restore();
                Payment::onlyTrashed()->where('order_id', $orderId)->restore();
            }
        });

        $this->loadTrashedOrders();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Order restored successfully!']);
    }

    public function bulkRestore()
    {
        if(empty($this->selectedOrders)) return;

        if(!Gate::allows('order_restore')){
            $this->dispatch('alert', ['type' => 'error', 'message' => 'You do not have permission to restore orders!']);
            return;
        }

        DB::transaction(function () {
            foreach ($this->selectedOrders as $orderId) {
                $order = Order::onlyTrashed()->find($orderId);
                if ($order) {
                    $order->restore();
                    OrderDetail::onlyTrashed()->where('order_id', $orderId)->restore();
                    OrderAddonDetail::onlyTrashed()->where('order_id', $orderId)->restore();
                    Payment::onlyTrashed()->where('order_id', $orderId)->restore();
                }
            }
        });

        $this->selectedOrders = [];
        $this->loadTrashedOrders();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Selected orders restored successfully!']);
    }

    public function forceDeleteOrder($orderId)
    {
        if(!Gate::allows('order_force_delete')){
            abort(404);
        }

        DB::transaction(function () use ($orderId) {
            $order = Order::onlyTrashed()->find($orderId);
            if ($order) {
                OrderDetail::onlyTrashed()->where('order_id', $orderId)->forceDelete();
                OrderAddonDetail::onlyTrashed()->where('order_id', $orderId)->forceDelete();
                Payment::onlyTrashed()->where('order_id', $orderId)->forceDelete();
                $order->forceDelete();
            }
        });

        $this->loadTrashedOrders();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Order permanently deleted!']);
    }

    public function bulkForceDelete()
    {
        if(empty($this->selectedOrders)) return;

        if(!Gate::allows('order_force_delete')){
            $this->dispatch('alert', ['type' => 'error', 'message' => 'You do not have permission to permanently delete orders!']);
            return;
        }

        DB::transaction(function () {
            foreach ($this->selectedOrders as $orderId) {
                $order = Order::onlyTrashed()->find($orderId);
                if ($order) {
                    OrderDetail::onlyTrashed()->where('order_id', $orderId)->forceDelete();
                    OrderAddonDetail::onlyTrashed()->where('order_id', $orderId)->forceDelete();
                    Payment::onlyTrashed()->where('order_id', $orderId)->forceDelete();
                    $order->forceDelete();
                }
            }
        });

        $this->selectedOrders = [];
        $this->loadTrashedOrders();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Selected orders permanently deleted!']);
    }

    public function emptyRecycleBin()
    {
        if(!Gate::allows('order_force_delete')){
            $this->dispatch('alert', ['type' => 'error', 'message' => 'You do not have permission to permanently delete orders!']);
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

        $this->loadTrashedOrders();
        $this->dispatch('alert', ['type' => 'success', 'message' => 'Recycle bin emptied!']);
    }
}
