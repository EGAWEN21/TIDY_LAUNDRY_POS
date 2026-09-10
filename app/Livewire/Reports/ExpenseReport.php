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
    public $from_date;
    public $to_date;
    public $granularity = 'monthly';
    public $lang;

    public function updatedGranularity($value)
    {
        $now = \Carbon\Carbon::now();
        switch ($value) {
            case 'daily':
                $this->from_date = $now->copy()->startOfDay()->toDateString();
                $this->to_date = $now->copy()->endOfDay()->toDateString();
                break;
            case 'weekly':
                $this->from_date = $now->copy()->startOfWeek()->toDateString();
                $this->to_date = $now->copy()->endOfWeek()->toDateString();
                break;
            case 'monthly':
                $this->from_date = $now->copy()->startOfMonth()->toDateString();
                $this->to_date = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'yearly':
                $this->from_date = $now->copy()->startOfYear()->toDateString();
                $this->to_date = $now->copy()->endOfYear()->toDateString();
                break;
        }
        $this->report();
    }

    public $categoryFilter = '';
    public $expenseCategories = [];
    public $trendData = [];
    public $trendLabels = [];
    public $sortBy = 'expense_date';
    public $sortDirection = 'desc';

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
        if (!\Illuminate\Support\Facades\Gate::allows('report_expense')) {
            abort(404);
        }
        $this->from_date = Carbon::now()->startOfMonth()->toDateString();
        $this->to_date = Carbon::now()->endOfMonth()->toDateString();
        
        $this->expenseCategories = \App\Models\ExpenseCategory::all();

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

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
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

        $revenueBilled = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->where('status', 3)
            ->sum('total');

        $this->kpi = [
            'expenses' => $totalExpenses,
            'income' => $totalIncome,
            'net_cash' => $totalIncome - $totalExpenses,
            'revenue_billed' => $revenueBilled,
        ];

        $diffInDays = Carbon::parse($this->from_date)->diffInDays(Carbon::parse($this->to_date));
        $prev_from_date = Carbon::parse($this->from_date)->subDays($diffInDays + 1)->toDateString();
        $prev_to_date = Carbon::parse($this->from_date)->subDays(1)->toDateString();

        $currentCategories = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select(
                DB::raw("COALESCE(expense_categories.expense_category_name, 'Uncategorized') as name"),
                DB::raw('SUM(expenses.expense_amount) as amount')
            )
            ->whereDate('expenses.expense_date', '>=', $this->from_date)
            ->whereDate('expenses.expense_date', '<=', $this->to_date)
            ->groupBy(DB::raw("COALESCE(expense_categories.expense_category_name, 'Uncategorized')"))
            ->pluck('amount', 'name')->toArray();

        $previousCategories = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select(
                DB::raw("COALESCE(expense_categories.expense_category_name, 'Uncategorized') as name"),
                DB::raw('SUM(expenses.expense_amount) as amount')
            )
            ->whereDate('expenses.expense_date', '>=', $prev_from_date)
            ->whereDate('expenses.expense_date', '<=', $prev_to_date)
            ->groupBy(DB::raw("COALESCE(expense_categories.expense_category_name, 'Uncategorized')"))
            ->pluck('amount', 'name')->toArray();

        $allNames = array_unique(array_merge(array_keys($currentCategories), array_keys($previousCategories)));

        $this->categoryBreakdown = [];
        foreach ($allNames as $name) {
            $this->categoryBreakdown[] = [
                'name' => $name,
                'current_amount' => (float)($currentCategories[$name] ?? 0),
                'previous_amount' => (float)($previousCategories[$name] ?? 0),
            ];
        }
        
        // Sort by current amount descending
        usort($this->categoryBreakdown, function($a, $b) {
            return $b['current_amount'] <=> $a['current_amount'];
        });

        // 6 Month Trend Data
        $this->trendLabels = [];
        $this->trendData = ['cash_collected' => [], 'expenses' => []];
        $endDate = Carbon::parse($this->to_date)->endOfMonth();
        $startDate = $endDate->copy()->subMonths(5)->startOfMonth();

        // MySQL DATE_FORMAT alternative for sqlite compatibility if needed, but since instructions just say 'windows' and standard Laravel DB, let's use a more robust way to group by month
        $trendExpenses = \App\Models\Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($val) {
                return Carbon::parse($val->expense_date)->format('Y-m');
            })
            ->map(function($row) {
                return $row->sum('expense_amount');
            })->toArray();

        $trendIncome = \App\Models\Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($val) {
                return Carbon::parse($val->payment_date)->format('Y-m');
            })
            ->map(function($row) {
                return $row->sum('received_amount');
            })->toArray();

        for ($i = 0; $i < 6; $i++) {
            $monthObj = $startDate->copy()->addMonths($i);
            $monthKey = $monthObj->format('Y-m');
            $this->trendLabels[] = $monthObj->format('M Y');
            $this->trendData['expenses'][] = (float)($trendExpenses[$monthKey] ?? 0);
            $this->trendData['cash_collected'][] = (float)($trendIncome[$monthKey] ?? 0);
        }

        $this->dispatch('update-expense-charts', [
            'categories' => $this->categoryBreakdown,
            'trendLabels' => $this->trendLabels,
            'trendData' => $this->trendData
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

    use \App\Traits\CsvExportable;

    public function downloadCsv()
    {
        $expenses = $this->expenses();
        $filename = 'ExpenseReport_' . $this->from_date . '.csv';
        $headers = ['Date', 'Category', 'Amount', 'Note'];
        $rows = [];

        foreach ($expenses as $item) {
            $rows[] = [
                \Carbon\Carbon::parse($item->expense_date)->format('d/m/Y'),
                $item->expenseCategory->expense_category_name ?? 'Uncategorized',
                $item->expense_amount,
                $item->note ?? '',
            ];
        }
        
        return $this->exportCsv($headers, $rows, $filename);
    }

    #[\Livewire\Attributes\Computed]
    public function expenses()
    {
        $query = \App\Models\Expense::whereDate('expense_date', '>=', $this->from_date)
            ->whereDate('expense_date', '<=', $this->to_date)
            ->with('expenseCategory');
            
        if ($this->categoryFilter) {
            $query->where('expense_category_id', $this->categoryFilter);
        }

        if ($this->sortBy === 'category') {
            $query->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                  ->orderBy('expense_categories.expense_category_name', $this->sortDirection)
                  ->select('expenses.*');
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->get();
    }
}
