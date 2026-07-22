<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Translation;

class TaxReport extends Component
{
    public $from_date, $to_date, $category = 1, $lang;
    #[Title('Tax Report')]
    public function render()
    {
        return view('livewire.reports.tax-report');
    }
     /* processed before render */
     public function mount()
     {
        if(!\Illuminate\Support\Facades\Gate::allows('report_tax')){
            abort(404);
        }
         $this->from_date = \Carbon\Carbon::today()->toDateString();
         $this->to_date = \Carbon\Carbon::today()->toDateString();
         if (session()->has('selected_language')) {
             $this->lang = Translation::where('id', session()->get('selected_language'))->first();
         } else {
             $this->lang = Translation::where('default', 1)->first();
         }
     }
     
     /*processed on update of the element */
     public function updated($name, $value)
     {
         // Computed properties are automatically updated
     }
 
     /* download pdf file */
     public function downloadFile()
     {
         $from_date = $this->from_date;
         $to_date = $this->to_date;
         $category = $this->category;
         $pdfContent = Pdf::loadView('livewire.reports.download-report.tax-report', compact('from_date', 'to_date', 'category'))->output();
         return response()->streamDownload(fn () => print($pdfContent), "TaxReport_from_" . $from_date . ".pdf");
     }

     #[\Livewire\Attributes\Computed]
     public function reports()
     {
         if ($this->category == 1) {
             return \App\Models\Order::whereDate('order_date', '>=', $this->from_date)->whereDate('order_date', '<=', $this->to_date)->where('status', 3)->latest()->get();
         }
         
         if ($this->category == 2) {
             return \App\Models\Expense::whereDate('expense_date', '>=', $this->from_date)->whereDate('expense_date', '<=', $this->to_date)->latest()->get();
         }

         return collect();
     }
}
