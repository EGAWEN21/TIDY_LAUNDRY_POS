<?php

namespace App\Livewire\Orders;

use Livewire\Component;
use App\Models\OrderRequest;
use App\Models\Translation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;

class OrderRequestsList extends Component
{
    public $requests, $lang, $rejection_note, $reject_id;

    #[Title('Order Requests')]
    public function render()
    {
        return view('livewire.orders.order-requests-list');
    }

    public function mount()
    {
        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }
        $this->loadRequests();
    }

    public function loadRequests()
    {
        if (Auth::user()->hasPermission('accept_reject_order') || Auth::user()->hasPermission('view_all_requests')) {
            $this->requests = OrderRequest::latest()->get();
        } else {
            $this->requests = OrderRequest::where('created_by', Auth::id())->latest()->get();
        }
    }

    public function acceptOrder($id)
    {
        if (!Auth::user()->hasPermission('accept_reject_order')) {
            abort(403);
        }

        $req = OrderRequest::findOrFail($id);
        
        $dto = \App\DTOs\OrderData::from($req->payload);
        $order = \App\Actions\Orders\CreateOrderAction::execute($dto, $req->created_by);
        
        // Preserve UUID for idempotency
        if (!empty($req->uuid)) {
            $order->uuid = $req->uuid;
            $order->save();
        }
        
        $req->delete();
        $this->loadRequests();
        $this->dispatch('alert', ['type' => 'success',  'message' => 'Order Request Accepted!']);
    }

    public function rejectModal($id)
    {
        $this->reject_id = $id;
        $this->rejection_note = '';
    }

    public function rejectOrder()
    {
        if (!Auth::user()->hasPermission('accept_reject_order')) {
            abort(403);
        }

        $this->validate([
            'rejection_note' => 'required|string|max:1000'
        ]);

        $req = OrderRequest::findOrFail($this->reject_id);
        $req->status = 1;
        $req->rejection_note = $this->rejection_note;
        $req->save();

        $this->dispatch('closemodal');
        $this->loadRequests();
        $this->dispatch('alert', ['type' => 'success',  'message' => 'Order Request Rejected.']);
    }

    public function deleteRequest($id)
    {
        $req = OrderRequest::findOrFail($id);
        if (!Auth::user()->hasPermission('delete_order_requests') && !Auth::user()->hasPermission('accept_reject_order') && $req->created_by != Auth::id()) {
            abort(403);
        }
        $req->delete();
        $this->loadRequests();
        $this->dispatch('alert', ['type' => 'success',  'message' => 'Order Request Deleted.']);
    }
}
