<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\Attributes\Title;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Translation;

class TaxReport extends Component
{
    public $from_date;
    public $to_date;
    public $granularity = 'daily';
    public $category = 1;
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

    public $salesTaxTotal = 0;
    public $expenseTaxTotal = 0;
    public $salesTotal = 0;
    public $expenseTotal = 0;
    public $salesTaxableTotal = 0;
    public $rateBands = [];
    public $reportData = [];

    #[Title('Tax Report')]
    public function render()
    {
        return view('livewire.reports.tax-report');
    }

    /* processed before render */
    public function mount()
    {
        if (!\Illuminate\Support\Facades\Gate::allows('report_tax')) {
            abort(404);
        }
        $this->from_date = \Carbon\Carbon::today()->toDateString();
        $this->to_date = \Carbon\Carbon::today()->toDateString();
        if (session()->has('selected_language')) {
            $this->lang = Translation::where('id', session()->get('selected_language'))->first();
        } else {
            $this->lang = Translation::where('default', 1)->first() ?? Translation::where('id', 1)->first();
        }
        
        $this->report();
    }

    /*processed on update of the element */
    public function updated($name, $value)
    {
        $this->report();
    }

    public function report()
    {
        $this->salesTaxTotal = 0;
        $this->expenseTaxTotal = 0;
        $this->salesTotal = 0;
        $this->expenseTotal = 0;
        $this->salesTaxableTotal = 0;
        $this->rateBands = [];
        $this->reportData = [];

        // For Sales (category=1)
        $orders = \App\Models\Order::whereDate('order_date', '>=', $this->from_date)
            ->whereDate('order_date', '<=', $this->to_date)
            ->where('status', 3)
            ->select(['id', 'order_number', 'order_date', 'tax_percentage', 'tax_type', 'tax_amount', 'taxable_amount', 'total'])
            ->latest()
            ->get();

        $rateBandsTemp = [];
        foreach ($orders as $order) {
            $this->salesTaxTotal += $order->tax_amount;
            $this->salesTotal += $order->total;
            $taxable = $order->taxable_amount > 0 ? $order->taxable_amount : ($order->total - $order->tax_amount);
            $this->salesTaxableTotal += $taxable;

            $rate = $order->tax_percentage;
            if (!isset($rateBandsTemp[$rate])) {
                $rateBandsTemp[$rate] = [
                    'rate' => $rate,
                    'count' => 0,
                    'taxable' => 0,
                    'tax' => 0,
                    'total' => 0
                ];
            }
            $rateBandsTemp[$rate]['count'] += 1;
            $rateBandsTemp[$rate]['taxable'] += $taxable;
            $rateBandsTemp[$rate]['tax'] += $order->tax_amount;
            $rateBandsTemp[$rate]['total'] += $order->total;

            $order->computed_tax_amount = $order->tax_amount;
            $order->computed_before_tax = $taxable;
        }

        // Convert to sequential array and sort by rate
        $this->rateBands = array_values($rateBandsTemp);
        usort($this->rateBands, function ($a, $b) {
            return $a['rate'] <=> $b['rate'];
        });

        if ($this->category == 1) {
            $this->reportData = $orders;
        }

        // For Expenses (category=2)
        $expenses = \App\Models\Expense::whereDate('expense_date', '>=', $this->from_date)
            ->whereDate('expense_date', '<=', $this->to_date)
            ->with('expenseCategory')
            ->latest()
            ->get();

        foreach ($expenses as $expense) {
            $taxPercentage = (float) ($expense->tax_percentage ?? 0);
            if ($expense->tax_included == 1) {
                $taxAmount = $taxPercentage > 0 ? ($expense->expense_amount - ($expense->expense_amount / (1 + ($taxPercentage / 100)))) : 0;
                $beforeTax = $expense->expense_amount - $taxAmount;
                $rowTotal = $expense->expense_amount;
            } else {
                $taxAmount = $expense->expense_amount * ($taxPercentage / 100);
                $beforeTax = $expense->expense_amount;
                $rowTotal = $beforeTax + $taxAmount;
            }
            $this->expenseTaxTotal += $taxAmount;
            $this->expenseTotal += $rowTotal;

            $expense->computed_tax_amount = $taxAmount;
            $expense->computed_before_tax = $beforeTax;
            $expense->computed_total = $rowTotal;
        }

        if ($this->category == 2) {
            $this->reportData = $expenses;
        }
    }

    use \App\Traits\CsvExportable;

    /* download pdf file */
    public function downloadFile()
    {
        $from_date = $this->from_date;
        $to_date = $this->to_date;
        $category = $this->category;
        $pdfContent = Pdf::loadView('livewire.reports.download-report.tax-report', compact('from_date', 'to_date', 'category'))->output();
        return response()->streamDownload(fn () => print($pdfContent), "TaxReport_from_" . $from_date . ".pdf");
    }

    public function downloadCsv()
    {
        $filename = 'TaxReport_' . ($this->category == 1 ? 'Sales' : 'Expenses') . '_' . $this->from_date . '.csv';
        $headers = [];
        $rows = [];

        if ($this->category == 1) {
            $headers = ['Date', 'Reference No', 'Total Amount', 'Taxable Amount', 'Tax %', 'Tax Amount'];
            foreach ($this->reportData as $row) {
                $rows[] = [
                    \Carbon\Carbon::parse($row->order_date)->format('d/m/Y'),
                    $row->order_number,
                    $row->total,
                    $row->computed_before_tax,
                    $row->tax_percentage,
                    $row->computed_tax_amount,
                ];
            }
        } else {
            $headers = ['Date', 'Reference No', 'Total Amount', 'Before Tax', 'Tax %', 'Tax Amount'];
            foreach ($this->reportData as $row) {
                $rows[] = [
                    \Carbon\Carbon::parse($row->expense_date)->format('d/m/Y'),
                    $row->id,
                    $row->computed_total ?? ($row->computed_before_tax + $row->computed_tax_amount),
                    $row->computed_before_tax,
                    $row->tax_percentage,
                    $row->computed_tax_amount,
                ];
            }
        }

        return $this->exportCsv($headers, $rows, $filename);
    }
}
