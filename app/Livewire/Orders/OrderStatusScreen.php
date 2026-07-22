<?php

namespace App\Livewire\Orders;

use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Order;
use App\Models\Translation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class OrderStatusScreen extends Component
{
    public $orders, $pending_orders, $processing_orders, $ready_orders, $lang,$dateFilter='today';
    #[Title('Order Status Screen')]
    public function render()
    {
        $pending_orders = Order::where('status', 0)->latest();
        $processing_orders = Order::where('status', 1)->latest();
        $ready_orders = Order::where('status', 2)->latest();
        if (Auth::user()->user_type == 1 || Auth::user()->viewable_staff_orders === 'all') {
           
        } else {
            $viewable_ids = [Auth::user()->id];
            if (!empty(Auth::user()->viewable_staff_orders)) {
                $extra_ids = explode(',', Auth::user()->viewable_staff_orders);
                $viewable_ids = array_merge($viewable_ids, $extra_ids);
            }
            $pending_orders->whereIn('created_by', $viewable_ids)->where('status', 0);
            $processing_orders->whereIn('created_by', $viewable_ids)->where('status', 1);
            $ready_orders->whereIn('created_by', $viewable_ids)->where('status', 2);
        }
    
        switch($this->dateFilter){
            case 'today':
                $startDate = Carbon::today()->startOfDay()->toDateString();
                $endDate = Carbon::today()->endOfDay()->toDateString();
                $pending_orders->whereDate('order_date', $startDate);
                $processing_orders->whereDate('order_date', $startDate);
                $ready_orders->whereDate('order_date', $startDate);
                break;
            case 'weekly':
                $startDate = Carbon::now()->startOfWeek()->startOfDay();
                $endDate = Carbon::now()->endOfWeek()->endOfDay();
                $pending_orders->whereDate('order_date','>=', $startDate)->whereDate('order_date','<=', $endDate);
                $processing_orders->whereDate('order_date','>=', $startDate)->whereDate('order_date','<=', $endDate);
                $ready_orders->whereDate('order_date','>=', $startDate)->whereDate('order_date','<=', $endDate);
                break;
            case 'monthly':
                $startDate = Carbon::now()->startOfMonth()->startOfDay();
                $endDate = Carbon::now()->endOfMonth()->endOfDay();
                $pending_orders->whereDate('order_date','>=', $startDate)->whereDate('order_date','<=', $endDate);
                $processing_orders->whereDate('order_date','>=', $startDate)->whereDate('order_date','<=', $endDate);
                $ready_orders->whereDate('order_date','>=', $startDate)->whereDate('order_date','<=', $endDate);
                break;
        }
        $this->pending_orders = $pending_orders->get();
        $this->processing_orders = $processing_orders->get();
        $this->ready_orders = $ready_orders->get();
        return view('livewire.orders.order-status-screen');
    }

    /* process before render */
    public function mount()
    {
        if(!\Illuminate\Support\Facades\Gate::allows('order_status_change')){
            abort(404);
        }
        if (session()->has('selected_language')) {  /* if session has selected language */
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            /* if session has no selected language */
            $this->lang = Translation::where('default', 1)->first();
        }

      
    }
    public function changestatus($order, $status)
    {
        $statusInt = 0;
        switch ($status) {
            case 'processing': $statusInt = 1; break;
            case 'ready': $statusInt = 2; break;
            case 'pending': $statusInt = 0; break;
        }

        $result = \App\Actions\Orders\ChangeOrderStatusAction::execute($order, $statusInt);

        if (!$result['success']) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $result['message']]);
            return;
        }

        if (isset($result['open_url'])) {
            $this->dispatch('open-url', [['url' => $result['open_url']]]);
        }

        if (isset($result['sms_error'])) {
            $this->dispatch('alert', ['type' => 'error', 'message' => $result['sms_error'], 'title' => 'SMS Error']);
        }
    }
}
