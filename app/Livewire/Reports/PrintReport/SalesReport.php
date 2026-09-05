<?php

namespace App\Livewire\Reports\PrintReport;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Translation;

class SalesReport extends Component
{
    public $lang;
    public $from_date;
    public $to_date;
    public $orders;
    /* render the page */
    #[Layout('components.layouts.print-layout')]
    public function render()
    {
        return view('livewire.reports.print-report.sales-report');
    }
    /* process before render */
    public function mount($from_date = null, $to_date = null)
    {
        if (!\Illuminate\Support\Facades\Gate::allows('report_print')) {
            abort(404);
        }
        $this->from_date = $from_date;
        $this->to_date = $to_date;

        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first();
        }

        $this->orders = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)->whereDate('order_date', '<=', $this->to_date)->where('status', 3)->latest()->get();
    }
}
