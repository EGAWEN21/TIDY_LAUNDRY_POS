<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Translation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseReport extends Component
{
    public $from_date, $to_date, $expenses, $lang;
    
    // New Metrics
    public $kpi = [];
    public $categoryBreakdown = [];
    /* render the page */
    #[Title('Expense Report')]
    public function render()
    {
        return view('livewire.reports.expense-report');
    }
     /* processed before render */
     public function mount()
     {
        if(!\Illuminate\Support\Facades\Gate::allows('report_expense')){
            abort(404);
        }
         $this->from_date = Carbon::now()->startOfMonth()->toDateString();
         $this->to_date = Carbon::now()->endOfMonth()->toDateString();
         
         if (session()->has('selected_language')) {
             $this->lang = Translation::where('id', session()->get('selected_language'))->first();
         } else {
             $this->lang = Translation::where('default', 1)->first();
         }
         
         $this->report();
     }
     /*processed on update of the element */
     public function updated($name, $value)
     {
         $this->report();
     }
     /* report section */
     public function report()
     {
         // Calculate KPIs at the database level
         $totalExpenses = \App\Models\Expense::whereDate('expense_date', '>=', $this->from_date)
             ->whereDate('expense_date', '<=', $this->to_date)
             ->sum('expense_amount');
         
         $totalIncome = \App\Models\Payment::whereDate('payment_date', '>=', $this->from_date)
             ->whereDate('payment_date', '<=', $this->to_date)
             ->sum('received_amount');
             
         $this->kpi = [
             'expenses' => $totalExpenses,
             'income' => $totalIncome,
             'net_profit' => $totalIncome - $totalExpenses
         ];
         
         // Fetch expenses for the table display
         $this->expenses = \App\Models\Expense::whereDate('expense_date', '>=', $this->from_date)
             ->whereDate('expense_date', '<=', $this->to_date)
             ->latest()
             ->get();
         
         $categories = DB::table('expenses')
             ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
             ->select('expense_categories.expense_category_name', DB::raw('SUM(expenses.expense_amount) as amount'))
             ->whereDate('expenses.expense_date', '>=', $this->from_date)
             ->whereDate('expenses.expense_date', '<=', $this->to_date)
             ->groupBy('expense_categories.expense_category_name')
             ->orderByDesc('amount')
             ->get();
             
         $this->categoryBreakdown = [];
         foreach($categories as $c) {
             $this->categoryBreakdown[] = [
                 'name' => $c->expense_category_name,
                 'amount' => $c->amount
             ];
         }
         
         $this->dispatch('update-expense-charts', [
             'categories' => $this->categoryBreakdown
         ]);
     }
     /* download pdf file */
     public function downloadFile()
     {
         $from_date = $this->from_date;
         $to_date = $this->to_date;
         $pdfContent = Pdf::loadView('livewire.reports.download-report.expense-report', compact('from_date', 'to_date'))->output();
         return response()->streamDownload(fn () => print($pdfContent), "ExpenseReport_from_" . $from_date . ".pdf");
     }
}
