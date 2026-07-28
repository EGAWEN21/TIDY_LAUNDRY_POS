<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\Component;

class HomePage extends Component
{
    #[Title('Dashboard')]
    public $pending_count;
    public $processing_count;
    public $ready_count;
    public $delivered_count;
    public $returned_count;
    public $array;
    public $search_query = '';
    public $order_filter = '';
    public $lang;
    private function loadOrderCounts()
    {
        $counts = \Illuminate\Support\Facades\Cache::remember('dashboard_order_counts', 300, function () {
            return Order::select('status', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();
        });

        $this->pending_count = $counts[0] ?? 0;
        $this->processing_count = $counts[1] ?? 0;
        $this->ready_count = $counts[2] ?? 0;
        $this->delivered_count = $counts[3] ?? 0;
        $this->returned_count = $counts[4] ?? 0;
        $this->array = json_encode(array($this->pending_count,$this->processing_count,$this->ready_count,$this->delivered_count,$this->returned_count));
    }

    public function render()
    {
        $this->loadOrderCounts();
        return view('livewire.home-page');
    }

    /* process before mount */
    public function mount()
    {
        $this->loadOrderCounts();
        $this->lang = getSessionTranslation();
    }

    #[Computed]
    public function orders()
    {
        $query = Order::with(['details.service'])->whereDate('delivery_date', \Carbon\Carbon::today()->toDateString());

        if (!empty($this->order_filter)) {
            $query->where('status', $this->order_filter);
        }

        if (!empty($this->search_query)) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . sanitize_search($this->search_query) . '%')
                  ->orWhere('customer_name', 'like', '%' . sanitize_search($this->search_query) . '%');
            });
        }

        return $query->latest()->get();
    }
}
