<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Translation;
use Livewire\Attributes\Title;
use Livewire\Component;

class HomePage extends Component
{
    #[Title('Dashboard')]
    public $pending_count,$processing_count,$ready_count,$delivered_count,$returned_count,$orders,$array,$search_query,$order_filter,$lang;
    public function render()
    {
        $counts = \Illuminate\Support\Facades\Cache::remember('dashboard_order_counts', 300, function() {
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
        return view('livewire.home-page');
    }

    /* process before mount */
    public function mount()
    {
        $counts = \Illuminate\Support\Facades\Cache::remember('dashboard_order_counts', 300, function() {
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
        $this->orders = Order::with(['details.service'])->whereDate('delivery_date',\Carbon\Carbon::today()->toDateString())->get();
        if(session()->has('selected_language'))
        {
            /* if the session has selected language */
            $this->lang = Translation::where('id',session()->get('selected_language'))->first();
        }
        else{
            /* if the session has no selected language */
            $this->lang = Translation::where('default',1)->first();
        }
        $this->array = json_encode(array($this->pending_count,$this->processing_count,$this->ready_count,$this->delivered_count,$this->returned_count));
    }
    /* process while update the element */
    public function updated($name,$value)
    {
        /*if the updated element is search_query and value is not empty */
        if($name == 'search_query' && $value != '')
        {
            if($this->order_filter == '')
            {
                $this->orders = \App\Models\Order::with(['details.service'])->whereDate('delivery_date',\Carbon\Carbon::today()->toDateString())
                                            ->where(function($q) use ($value) {
                                                $q->where('order_number','like','%'.$value.'%')
                                                    ->orwhere('customer_name','like','%'.$value.'%');
                                                })
                                            ->latest()
                                            ->get();
            }
            else{
                $this->orders = \App\Models\Order::with(['details.service'])->where('status',$this->order_filter)
                                            ->whereDate('delivery_date',\Carbon\Carbon::today()->toDateString())
                                            ->where(function($q) use ($value) {
                                                $q->where('order_number','like','%'.$value.'%')
                                                ->orwhere('customer_name','like','%'.$value.'%');
                                            })
                                            ->latest()
                                            ->get();
            }
        }
        elseif($name == 'search_query' && $value == '')
        {
            /* if the updated element is search_query and value is empty */
            if($this->order_filter == '')
            {  /* if the order filter value is empty */
                $this->orders = \App\Models\Order::with(['details.service'])->whereDate('delivery_date',\Carbon\Carbon::today()->toDateString())->latest()->get();
            }
            else{
                /* if the order filter value is not empty */
                $this->orders = \App\Models\Order::with(['details.service'])->whereDate('delivery_date',\Carbon\Carbon::today()->toDateString())->where('status',$value)->latest()->get();

            }
        }
        /* if the updated value is order filter */
        if($name == 'order_filter')
        {
            $this->search_query = '';
            if($value == '')
            {    /* if the order filter value is empty */
                $this->orders = \App\Models\Order::with(['details.service'])->whereDate('delivery_date',\Carbon\Carbon::today()->toDateString())->latest()->get();
            }
            else{
                /* if the order filter value is empty */
                $this->orders = \App\Models\Order::with(['details.service'])->whereDate('delivery_date',\Carbon\Carbon::today()->toDateString())->where('status',$value)->latest()->get();
            }
        }
    }
}
