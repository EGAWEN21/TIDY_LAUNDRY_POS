# Handoff Report — Explorer Survey 3

**Agent**: Explorer Survey 3  
**Target Scope**: TidyPOS Report Section Mathematical Formulas, Financial/Accounting Logic, Database Queries, and CSV Export Audit  
**Artifact File**: `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_explorer_survey_3\report.md`  
**Date**: 2026-08-24  

---

## 1. Observation

Direct evidence gathered across the reporting section:

1. **Sales Tax Recalculation Discrepancy in PDF & Print Views**:
   - `app/Livewire/Reports/TaxReport.php:74-77`:
     ```php
     $this->salesTaxTotal += $order->tax_amount;
     $this->salesTotal += $order->total;
     $taxable = $order->taxable_amount > 0 ? $order->taxable_amount : ($order->total - $order->tax_amount);
     $this->salesTaxableTotal += $taxable;
     ```
   - `resources/views/livewire/reports/download-report/tax-report.blade.php:120-123`:
     ```php
     @if ($category == 1)
         @php
             $tax_amount_sales = $row->total * ($row->tax_percentage / 100);
             $tax_amount_total_sales += $tax_amount_sales;
         @endphp
     @endif
     ```
   - `resources/views/livewire/reports/print-report/tax-report.blade.php:91-94`:
     ```php
     @if ($category == 1)
         @php
             $tax_amount_sales = $row->total * ($row->tax_percentage / 100);
             $tax_amount_total_sales += $tax_amount_sales;
         @endphp
     @endif
     ```
   - Notice that the PDF and Print views do not use `$row->tax_amount` or `$row->taxable_amount`. Instead, they compute `$row->total * ($row->tax_percentage / 100)`.

2. **Expense Tax Accounting Contradiction**:
   - `app/Livewire/Reports/TaxReport.php:116-122`:
     ```php
     $taxAmount = $expense->expense_amount * ($expense->tax_percentage / 100);
     $this->expenseTaxTotal += $taxAmount;
     $this->expenseTotal += $expense->expense_amount;

     $expense->computed_tax_amount = $taxAmount;
     $expense->computed_before_tax = $expense->expense_amount - $taxAmount;
     ```
   - Notice: Tax is calculated via exclusive multiplication, then subtracted from `expense_amount` to obtain `computed_before_tax`.

3. **Uncategorized Expenses Dropped in Expense Report**:
   - `app/Livewire/Reports/ExpenseReport.php:97-103` & `105-111`:
     ```php
     $currentCategories = DB::table('expenses')
         ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
         ->select('expense_categories.expense_category_name as name', DB::raw('SUM(expenses.expense_amount) as amount'))
         ...
     ```
   - `app/Livewire/Reports/ExpenseReport.php:209-212`:
     ```php
     if ($this->sortBy === 'category') {
         $query->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
               ->orderBy('expense_categories.expense_category_name', $this->sortDirection)
               ->select('expenses.*');
     }
     ```
   - Notice: `INNER JOIN` (`join`) drops any expense record where `expense_category_id` is null or points to a non-existent category.

4. **Missing 0-Order Customers in Customer Report**:
   - `app/Livewire/Reports/CustomerReport.php:112-113`:
     ```php
     ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.created_at')
     ->having('total_orders', '>', 0)
     ->get();
     ```
   - Notice: The clause `->having('total_orders', '>', 0)` filters out all registered customers who have not yet placed an order.

5. **Non-Existent Property in Sales Report Blade**:
   - `resources/views/livewire/reports/sales-report.blade.php:117` & `220`:
     ```html
     <span class="text-xs text-muted">{{ $item->customer_phone }}</span>
     ```
   - `app/Models/Order.php:23` and `database/migrations/2022_02_21_094505_create_orders_table.php:21`:
     ```php
     $table->string('phone_number')->nullable();
     ```
   - Notice: The column is `phone_number`, not `customer_phone`.

6. **CSV Export Trait Encoding & Coverage**:
   - `app/Traits/CsvExportable.php:17-31`:
     ```php
     public function exportCsv(array $headers, array $rows, string $filename): StreamedResponse
     {
         $callback = function () use ($headers, $rows) {
             $file = fopen('php://output', 'w');
             fputcsv($file, $headers);
             foreach ($rows as $row) {
                 fputcsv($file, $row);
             }
             fclose($file);
         };
         return response()->streamDownload($callback, $filename, [
             'Content-Type' => 'text/csv',
         ]);
     }
     ```
   - Notice: No UTF-8 BOM (`\xEF\xBB\xBF`) is written. `DailyReport`, `LedgerReport`, and `BusinessInsights` lack CSV export integration.

---

## 2. Logic Chain

1. **Tax Discrepancy**:
   - In `CalculateCartTotals.php`, when tax is exclusive, `total = grossTotal + taxAmount - discount`. For example, with Subtotal 100 and 10% Tax, Total = 110 and `tax_amount = 10`.
   - The web view and CSV export read `$order->tax_amount` (10.00) and `$order->taxable_amount` (100.00).
   - The PDF view (`download-report/tax-report.blade.php`) and Print view (`print-report/tax-report.blade.php`) calculate `$tax_amount_sales = $row->total * ($row->tax_percentage / 100)`, yielding $110 \times 0.10 = \$11.00$ tax and \$99.00 before tax.
   - Therefore, the PDF and Print reports misrepresent both the tax liability and the taxable base.

2. **Expense Tax Contradiction**:
   - An expense is recorded with `expense_amount` and `tax_percentage`.
   - If tax is calculated as `expense_amount * rate%`, it treats `expense_amount` as net (before tax).
   - If before tax is calculated as `expense_amount - tax`, it treats `expense_amount` as gross (inclusive of tax).
   - Doing both simultaneously violates the basic accounting identity $\text{Net} \times (1 + \text{Rate}) = \text{Gross}$.

3. **Data Loss via INNER JOIN**:
   - Expenses in POS systems can be entered without an assigned category (`expense_category_id = NULL`).
   - Using `DB::table('expenses')->join('expense_categories', ...)` eliminates all rows with null foreign keys.
   - Therefore, total expenses in the KPI summary will not match the sum of categorized expenses, and sorting by category hides uncategorized expenses from the user.

4. **Customer Funnel Incompleteness**:
   - A customer is created at registration time.
   - When viewing a customer report, operators need to see newly acquired customers, including those who registered recently but have not yet placed an order.
   - Filtering with `->having('total_orders', '>', 0)` removes these customers, creating a mismatch with `BusinessInsights` which counts total customer registrations via `created_at`.

5. **UI Rendering Failures**:
   - Blade templates access model attributes dynamically.
   - Because `$item->customer_phone` does not match the database column `phone_number` or an Eloquent accessor, PHP returns null and the customer's phone number is left blank in the sales report table.

---

## 3. Caveats

1. **Tax Model Assumption**: Assumed tax configuration follows POS standard modes where `tax_type = 1` is Exclusive and `tax_type = 2` is Inclusive (as explicitly coded in `CalculateCartTotals.php`).
2. **Database Engine Scope**: Assumed primary deployment runs on standard relational engines (MySQL/MariaDB), while noting that raw `DATEDIFF()` and `MONTH()` calls limit SQLite/PostgreSQL portability.
3. **No Code Modification Executed**: Per the Explorer role guidelines, this investigation was strictly read-only; no production files were modified.

---

## 4. Conclusion

The TidyPOS reporting section has solid architectural foundations (clean Livewire components, sub-ledger running balances in Ledger Report, and DTO-driven cart totals in POS). However, six critical logic, formula, query, and UI discrepancies require targeted remediation:
1. **Fix Sales Tax in PDF/Print Views**: Replace `total * rate%` in `download-report/tax-report.blade.php` and `print-report/tax-report.blade.php` with stored `tax_amount` and `taxable_amount`.
2. **Fix Expense Tax Formula**: Correct inclusive vs exclusive tax calculations in `TaxReport.php` and its subviews.
3. **Fix Category Joins**: Replace `join()` with `leftJoin()` in `ExpenseReport.php`.
4. **Fix Customer Report Having Clause**: Remove `having('total_orders', '>', 0)` in `CustomerReport.php`.
5. **Fix Phone Column Reference**: Change `$item->customer_phone` to `$item->phone_number` in `sales-report.blade.php`.
6. **Enhance CSV Trait**: Add UTF-8 BOM (`\xEF\xBB\xBF`) in `CsvExportable.php` and implement CSV downloads in `DailyReport`, `LedgerReport`, and `BusinessInsights`.

---

## 5. Verification Method

To independently verify these findings:

1. **Verify Tax Calculation Discrepancy**:
   - Inspect `app/Livewire/Reports/TaxReport.php` lines 74–77 vs `resources/views/livewire/reports/download-report/tax-report.blade.php` lines 120–123.
   - Create a sample order with Subtotal \$100 and Exclusive Tax 10% (\$10). Check that the screen displays Tax = \$10.00 while the downloaded PDF displays Tax = \$11.00.

2. **Verify Field Name Mismatch**:
   - Inspect `resources/views/livewire/reports/sales-report.blade.php` lines 117 and 220.
   - Inspect `app/Models/Order.php` line 23 (`phone_number`).

3. **Verify Uncategorized Expenses Join Drop**:
   - Inspect `app/Livewire/Reports/ExpenseReport.php` lines 97–103.
   - Insert an expense with `expense_category_id = NULL` and observe that it appears in KPI `totalExpenses` but is absent from `categoryBreakdown`.

4. **Verify Customer Exclusion**:
   - Inspect `app/Livewire/Reports/CustomerReport.php` line 113.
   - Insert a customer record with 0 orders and observe that `total_customers` in the report does not increment.
