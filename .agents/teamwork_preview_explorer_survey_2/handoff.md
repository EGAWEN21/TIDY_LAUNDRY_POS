# Handoff Report: Survey Explorer 2 — Ledger, Sales & Customer Reports Audit

**Status**: Task Complete (Hard Handoff)  
**Agent**: teamwork_preview_explorer_survey_2 (Survey Explorer 2)  
**Date**: 2026-08-24  

---

## 1. Observation

### Exact File Paths & Code Locations Reviewed
1. **`app/Livewire/Reports/LedgerReport.php`**
   - Lines 49–92: `calculateAgeing()` queries `orders` and `payments` tables with `whereNull('payments.deleted_at')` and `whereNull('orders.deleted_at')`.
   - Lines 119–157: `topDebtors()` subquery on `orders` and `payments` computes `total_owed` via `COALESCE(o.total_ordered, 0) - COALESCE(p.total_paid, 0)`. Notice lines 132–136 do not filter by `Auth::user()->getViewableCustomerUserIds()`, whereas lines 100–103 in `updated('customer_query', ...)` do.
   - Lines 270–280: `data()` runs raw `UNION ALL` query between `orders` (debit) and `payments` (credit), explicitly including `deleted_at IS NULL` and `status != 4`.
   - Lines 288–291: `firstData()` computes opening balance totals using Eloquent `Order::where(...)` and `Payment::where(...)`.

2. **`resources/views/livewire/reports/ledger-report.blade.php`**
   - Lines 15–17: `<div id="ageingChart"></div>` inside `<x-chart-container>` without `wire:ignore`.
   - Lines 134–173: Ledger table with opening balance row (`$runningBalance = $this->firstData['debits'] - $this->firstData['credits']`), per-row debit addition / credit subtraction, and Dr/Cr indicators (`{{ $runningBalance > 0 ? '(Dr)' : '(Cr)' }}`).

3. **`app/Livewire/Reports/SalesReport.php`**
   - Lines 65–70:
     ```php
     $finData = DB::table('orders')
         ->whereDate('order_date', '>=', $this->from_date)
         ->whereDate('order_date', '<=', $this->to_date)
         ->where('status', 3)
         ->selectRaw('COALESCE(SUM(total), 0) as revenue_billed, COALESCE(SUM(discount), 0) as discount, COUNT(*) as orders')
         ->first();
     ```
     Observed: Raw `DB::table('orders')` does NOT include `whereNull('deleted_at')`.
   - Lines 78–81: `$cashCollected = DB::table('payments')->whereDate('payment_date', '>=', $this->from_date)->whereDate('payment_date', '<=', $this->to_date)->sum('received_amount');`
     Observed: Missing `whereNull('deleted_at')`.
   - Lines 90–93, 112–130, 140–147, 153–167, 170–178: All use raw `DB::table()` queries omitting soft-delete filters.
   - Lines 253–268: `orders()` computed property uses `\App\Models\Order::whereDate(...)->withSum('payments as paid', 'received_amount')` which uses Eloquent and excludes soft-deleted records.

4. **`resources/views/livewire/reports/sales-report.blade.php`**
   - Line 237: `@if(in_array($item->status, [0,1,2]) && \Carbon\Carbon::parse($item->delivery_date)->endOfDay()->isPast())` parses `$item->delivery_date` without null guard.

5. **`app/Livewire/Reports/CustomerReport.php`**
   - Lines 88–114: `customersAggregates` correctly joins `orders` with `whereNull('orders.deleted_at')` and `where('orders.status', '!=', 4)` and computes `spend_30` / `spend_7` conditional sums.
   - Lines 147–166: Lifecycle classification logic accurately categorizes New (1), Active (2), Lapsing (3), Dormant (4), Lost (5).
   - Lines 60–65: `acquisitionTrend()` builds rolling 12-month customer acquisition histogram without applying viewable user scoping from `getViewableCustomerUserIds()`.

6. **`resources/views/livewire/reports/customer-report.blade.php`**
   - Line 47: `<div id="acquisitionChart"></div>` rendered without `wire:ignore`.
   - Lines 99–138: Table iterates over unpaginated `$this->customersData`.

---

## 2. Logic Chain

1. **Premise 1 (Soft Deletes in Laravel Query Builders)**: Migration `2026_07_13_135555_add_soft_deletes_to_order_tables.php` and `2026_07_27_170300_add_soft_deletes_to_users_and_customers_tables.php` added soft deletes to `orders`, `order_details`, `order_addon_details`, `payments`, `customers`, and `users`. Eloquent models automatically apply `WHERE deleted_at IS NULL`, but raw `DB::table()` queries do not.
2. **Premise 2 (Inconsistency in SalesReport)**: In `SalesReport.php`, all top-level KPI numbers, revenue trend records, pipeline counts, and service breakdown queries use raw `DB::table()` without `whereNull('deleted_at')`. Meanwhile, the table rows query uses Eloquent `Order::whereDate(...)`.
3. **Inference 2**: When orders or payments are soft-deleted, the top-level metrics in `SalesReport` will overcount revenue, cash collected, and order counts compared to the actual order rows rendered in the table, violating mathematical integrity.
4. **Premise 3 (Livewire 3 DOM Diffing & Chart Canvases)**: ApexCharts creates dynamic SVG/canvas elements inside target `<div>` containers. When Livewire re-renders the component upon user interactions (e.g. typing into a search input or selecting a filter), Livewire's Morphdom replaces the `<div>` children unless wrapped in `wire:ignore` or re-initialized via event dispatchers.
5. **Inference 3**: In `ledger-report.blade.php` and `customer-report.blade.php`, the `#ageingChart` and `#acquisitionChart` containers lack `wire:ignore` and lack Livewire event listeners to re-render, causing chart loss during live filter updates.
6. **Premise 4 (Scoped Data Access)**: `User::getViewableCustomerUserIds()` provides multi-tenant / staff-level customer isolation.
7. **Inference 4**: While primary tables/searches respect `getViewableCustomerUserIds()`, auxiliary subqueries (`topDebtors` in LedgerReport and `acquisitionTrend` in CustomerReport) omit this scope, allowing data leakage across staff boundaries.

---

## 3. Caveats

- **No Caveats on Core Report Scope**: All 3 specified report components, blade views, and auxiliary download views were inspected in full.
- **Assumptions**:
  - The standard database engine in production is MySQL / MariaDB (supporting `DATEDIFF(expr1, expr2)`). If SQLite is used for unit tests, custom SQLite function registration or Carbon-based PHP calculation is required.
  - The permissions `report_ledger`, `report_sales`, and `report_customer` are seeded in the `permissions` table.

---

## 4. Conclusion

The Ledger, Sales, and Customer reports have solid mathematical and domain foundations. To achieve complete robustness and accuracy:
1. **Fix soft-delete leaks in `SalesReport.php`** by adding `->whereNull('orders.deleted_at')` and `->whereNull('payments.deleted_at')` to all raw `DB::table()` calls.
2. **Add `wire:ignore` or Livewire event update listeners to `#ageingChart` and `#acquisitionChart`** in `ledger-report.blade.php` and `customer-report.blade.php`.
3. **Apply `getViewableCustomerUserIds()` uniformly** in `topDebtors()` and `acquisitionTrend()`.
4. **Add null-safety guards** for `delivery_date` in `sales-report.blade.php`.
5. **Consider adding pagination** to `CustomerReport.php` for high customer volume scalability.

Detailed architectural analysis and remediation steps are fully documented in `survey_report.md`.

---

## 5. Verification Method

### How to Independently Verify
1. **Soft-Delete Leak Verification**:
   - Create a test order and payment, soft delete them (`$order->delete()`), and run `SalesReport::report()`. Check if `revenue_billed` includes the soft-deleted order.
2. **Chart Persistence Verification**:
   - Load Ledger Report in browser, type into `customer_query` input, and verify if the Ageing Chart SVG remains intact or is wiped out.
3. **Staff Scope Verification**:
   - Log in as a non-admin user with restricted `viewable_staff_customers` and check whether `topDebtors` displays debtors created by other staff.
4. **Code Inspection**:
   - Inspect `app/Livewire/Reports/SalesReport.php` lines 65–70, 78–81, 90–93, 112–130, 140–147, 153–167, 170–178.
   - Inspect `resources/views/livewire/reports/ledger-report.blade.php` line 16.
   - Inspect `resources/views/livewire/reports/customer-report.blade.php` line 47.
