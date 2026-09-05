# Changes Report — Worker M1 (Tax Report Remediation)

## 1. `app/Livewire/Reports/TaxReport.php`
- **Translation Fallback**: Added fallback translation initialization in `mount()` (`Translation::where('default', 1)->first() ?? Translation::where('id', 1)->first()`).
- **Expense Tax Inclusive vs Exclusive Calculation**: Updated `report()` method to properly handle inclusive (`tax_included == 1`) vs exclusive tax (`tax_included == 0` or null).
  - *Inclusive*: `$taxAmount = $taxPercentage > 0 ? ($expense->expense_amount - ($expense->expense_amount / (1 + ($taxPercentage / 100)))) : 0;`
    `$beforeTax = $expense->expense_amount - $taxAmount;`
    `$rowTotal = $expense->expense_amount;`
  - *Exclusive*: `$taxAmount = $expense->expense_amount * ($taxPercentage / 100);`
    `$beforeTax = $expense->expense_amount;`
    `$rowTotal = $beforeTax + $taxAmount;`
  - Computed values `$expense->computed_tax_amount`, `$expense->computed_before_tax`, and `$expense->computed_total` attached to model instance for UI rendering.
- **CSV Export**: Updated expense total amount export to output `$row->computed_total ?? ($row->computed_before_tax + $row->computed_tax_amount)`.

## 2. `app/Livewire/Reports/PrintReport/TaxReport.php`
- **Imported Translation Model**: Added `use App\Models\Translation;`.
- **Added `$lang` Property**: Added `public $lang;` property.
- **Initialized `$lang` in `mount()`**: Populated `$this->lang` using session language, falling back to default translation or id 1.
- **Query Optimization & Defaults**: Initialized `$this->reports = collect();` and defaulted `$this->category = $category ?? 1;`. Added `with('expenseCategory')` eager loading for expense queries.

## 3. `app/Livewire/Reports/DownloadReport/TaxReport.php`
- **Component Properties & Mount**: Added `public $from_date;`, `public $to_date;`, `public $category = 1;` and implemented `mount($from_date = null, $to_date = null, $category = null)` to support both direct Livewire routing and PDF rendering.
- **View Variable Passing**: Passed parameters explicitly to view in `render()`.

## 4. `resources/views/livewire/reports/tax-report.blade.php`
- **Expense Row Total Display**: Updated table cell total amount rendering for expenses to use `$row->computed_total ?? ($row->computed_before_tax + $row->computed_tax_amount)` so exclusive tax totals correctly reflect before-tax plus tax.

## 5. `resources/views/livewire/reports/print-report/tax-report.blade.php`
- **Sales Tax Calculation**: Replaced incorrect percentage-based computation with stored order `$row->tax_amount` and taxable amount `$row->taxable_amount > 0 ? $row->taxable_amount : ($row->total - $row->tax_amount)`.
- **Expense Tax Inclusive/Exclusive Logic**: Integrated inclusive vs exclusive tax calculations matching Livewire component logic.
- **HTML Structure & Closing Tags**: Fixed unclosed `<p>` tag in table cells and cleaned up markup with UTF-8 charset.
- **Summary Totals**: Updated summary footer cards to display accumulated total amounts and tax amounts.

## 6. `resources/views/livewire/reports/download-report/tax-report.blade.php`
- **Sales & Expense Tax Calculations**: Replaced percentage formula with stored order fields for sales and inclusive/exclusive calculation for expenses.
- **Eager Loading**: Added `with('expenseCategory')` to prevent N+1 queries.
- **HTML & Cleanup**: Fixed missing closing tags, removed obsolete TODO comment, and corrected summary footer totals.
