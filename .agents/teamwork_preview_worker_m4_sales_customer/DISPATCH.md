# Dispatch for Worker M4 — Sales & Customer Reports Remediation

You are Worker M4 for the TidyPOS report section audit project.
Working directory: `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m4_sales_customer\`
Original request: `c:\Users\DELL\Herd\tidypos\.agents\ORIGINAL_REQUEST.md`
Project Plan: `c:\Users\DELL\Herd\tidypos\PROJECT.md`

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Exclusive Write Ownership
You exclusively own and may edit ONLY these files:
- `app/Livewire/Reports/SalesReport.php`
- `app/Livewire/Reports/CustomerReport.php`
- `app/Livewire/Reports/PrintReport/SalesReport.php`
- `resources/views/livewire/reports/sales-report.blade.php`
- `resources/views/livewire/reports/customer-report.blade.php`
- `resources/views/livewire/reports/print-report/sales-report.blade.php`

## Required Fixes & Tasks
1. **Wrong Customer Phone Field in `sales-report.blade.php`**:
   - Lines 117 & 219: Replace `$item->customer_phone` with `$item->phone_number` (or `$item->phone_number ?? $item->customer_phone`).
2. **Missing Pagination Reset in `SalesReport.php`**:
   - In `updated($name, $value)`, add `$this->resetPage();` when filters change (such as `activeTab`, `startDate`, `endDate`, `selectedStatus`, `paymentStatus`).
3. **Missing `$lang` in `app/Livewire/Reports/PrintReport/SalesReport.php`**:
   - Add `public $lang;` and initialize in `mount()`.
4. **Customer Report Initial Zero KPIs**:
   - In `CustomerReport.php`, ensure `$kpiSummary` is calculated and available before the view renders top cards (e.g. initialize/compute in `render()` or `mount()`, or compute `$this->kpiSummary` as part of getting customer metrics).
5. **Customer Report 0-Order Customers**:
   - In `CustomerReport.php` query, remove `->having('total_orders', '>', 0)` (or make it include 0-order customers with `0` total spend/orders and 'New / Unengaged' status) so all registered customers are accounted for.
6. **Badge Text Visibility Clashes**:
   - In `customer-report.blade.php` lines 121-129, remove `text-white` conflicting with `text-*-600` on pastel backgrounds (`bg-*-100`).
7. **Blade Prop Syntax in `sales-report.blade.php`**:
   - Line 48: Change `trendUp="{{ ($financialKpi['growth'] ?? 0) >= 0 ? true : false }}"` to `:trendUp="($financialKpi['growth'] ?? 0) >= 0"`.
8. **Overdue Comparison in `sales-report.blade.php`**:
   - Line 236: Ensure overdue condition checks against end of delivery day (`Carbon::parse($item->delivery_date)->endOfDay()->isPast()`).
9. **Verification**:
   - Run `php -l` on all modified PHP files.

Write your changes report to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m4_sales_customer\changes.md` and handoff to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m4_sales_customer\handoff.md`.
