<?php

namespace App\Livewire\Reports\PrintReport;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Translation;

class ExpenseReport extends Component
{
    public $expenses;
    public $from_date;
    public $to_date;
    public $lang;

    /* render the page*/
    #[Layout('components.layouts.print-layout')]
    public function render()
    {
        return view('livewire.reports.print-report.expense-report');
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
            $this->lang = Translation::where('default', 1)->first() ?? Translation::where('id', 1)->first();
        }

        $this->expenses = \App\Models\Expense::whereDate('expense_date', '>=', $this->from_date)->whereDate('expense_date', '<=', $this->to_date)->with('expenseCategory')->latest()->get();
    }
}
