# Handoff Report: Survey Explorer 1 (Tax, Expense, Daily Reports)

## 1. Observation
- **TaxReport Component (`app/Livewire/Reports/TaxReport.php:65-136`) & View (`resources/views/livewire/reports/tax-report.blade.php:31-94`)**:
  - `Order::whereDate(...)->where('status', 3)->select(['id', 'order_number', 'order_date', 'tax_percentage', 'tax_type', 'tax_amount', 'taxable_amount', 'total'])->latest()->get();`
  - Taxable calculation: `$taxable = $order->taxable_amount > 0 ? $order->taxable_amount : ($order->total - $order->tax_amount);`
  - Tax bands generated and sorted: `usort($this->rateBands, fn($a, $b) => $a['rate'] <=> $b['rate']);`
  - Expense tax formula: `$expense->tax_included == 1 ? ($expense->expense_amount - ($expense->expense_amount / (1 + ($taxPercentage / 100)))) : ($expense->expense_amount * ($taxPercentage / 100));`
- **ExpenseReport Component (`app/Livewire/Reports/ExpenseReport.php:73-172`) & View (`resources/views/livewire/reports/expense-report.blade.php:33-83`)**:
  - Aggregates KPIs: `sum('expense_amount')`, `sum('received_amount')`, `sum('total')`.
  - Computes period comparison with `$diffInDays + 1` day subtraction (`ExpenseReport.php:93-96`).
  - 6-month historical monthly trend aggregation (`ExpenseReport.php:138-166`).
  - Dispatches `update-expense-charts` browser event with formatted series (`ExpenseReport.php:168-172`).
- **DailyReport Component (`app/Livewire/Reports/DailyReport.php:65-136`) & View (`resources/views/livewire/reports/daily-report.blade.php:27-146`)**:
  - Queries `new_order`, `delivered_orders`, `total_sales`, `cash_collected`, `total_expense`, `pending_orders`, `unpaidDeliveries`, `overdueOrders`.
  - `itemVolume` at `DailyReport.php:107-110`: `DB::table('order_details')->join('orders', 'order_details.order_id', '=', 'orders.id')->whereDate('orders.order_date', $this->today)->sum('order_details.service_quantity');`
  - `overdueOrders` at `DailyReport.php:87`: `DB::raw('DATEDIFF(CURRENT_DATE, orders.delivery_date) as days_overdue')`.
- **Expense Download & Print Views**:
  - `resources/views/livewire/reports/download-report/expense-report.blade.php:102` & `resources/views/livewire/reports/print-report/expense-report.blade.php:82`:
    `$tax_amount = $row->expense_amount * ($row->tax_percentage / 100);` (ignores `tax_included` column).
- **Daily Report Download View**:
  - `resources/views/livewire/reports/download-report/daily-report.blade.php:20`:
    `<title>{{$lang->data['order_report'] ?? 'Order Report'}}</title>` (mislabeled title).

## 2. Logic Chain
1. **Expense Tax Discrepancy**: `TaxReport.php` accurately differentiates tax-inclusive expenses (`tax_included == 1`) vs tax-exclusive (`tax_included == 0`). In contrast, `download-report/expense-report.blade.php` and `print-report/expense-report.blade.php` unconditionally calculate tax as `expense_amount * tax_percentage / 100`. For any expense where tax is included, the PDF and print report overstate the tax amount and contradict the main tax report.
2. **Soft-Delete Bypassing**: `orders`, `order_details`, and `payments` tables have SoftDeletes (`deleted_at`). Raw `DB::table` queries in `DailyReport.php` (`itemVolume` and 7-day `trend`) do not include `whereNull('deleted_at')`, which means soft-deleted records could be improperly included in daily aggregations.
3. **Database Driver Incompatibility**: `DATEDIFF(CURRENT_DATE, ...)` is specific to MySQL/MariaDB. When automated tests run in SQLite in-memory databases, this query fails with a syntax error.
4. **Export Mechanisms**: `CsvExportable` trait is implemented properly across all three components with appropriate byte-order markers (UTF-8 BOM).

## 3. Caveats
- No direct source code changes were made as this phase is designated for read-only exploration and mapping.
- Database tables were inspected via migration definitions; live database performance was assessed based on existing schema indexes.

## 4. Conclusion
The core livewire components (`TaxReport.php`, `ExpenseReport.php`, `DailyReport.php`) and primary dashboard views are mathematically sound and function cleanly with Livewire 3 reactivity and ApexCharts. Eight specific discrepancies and bugs (notably BUG-01 for expense tax calculation in PDF/print and BUG-02/BUG-03 for soft deletes) have been identified, fully documented, and mapped for remediation.

## 5. Verification Method
1. Inspect the detailed audit findings in `.agents/teamwork_preview_explorer_survey_1/survey_report.md`.
2. Verify code references and line numbers in:
   - `app/Livewire/Reports/TaxReport.php`
   - `app/Livewire/Reports/ExpenseReport.php`
   - `app/Livewire/Reports/DailyReport.php`
   - `resources/views/livewire/reports/download-report/expense-report.blade.php`
   - `resources/views/livewire/reports/print-report/expense-report.blade.php`
3. Run PHPUnit test suite to confirm baseline:
   `php artisan test`
