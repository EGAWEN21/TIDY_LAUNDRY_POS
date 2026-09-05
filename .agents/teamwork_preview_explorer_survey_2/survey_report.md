# Comprehensive Survey Report: Ledger, Sales, and Customer Reports in TidyPOS

**Report Date**: 2026-08-24  
**Author**: Survey Explorer 2  
**Target Scope**:
- `app/Livewire/Reports/LedgerReport.php` & `resources/views/livewire/reports/ledger-report.blade.php`
- `app/Livewire/Reports/SalesReport.php` & `resources/views/livewire/reports/sales-report.blade.php`
- `app/Livewire/Reports/CustomerReport.php` & `resources/views/livewire/reports/customer-report.blade.php`
- Associated PDF/Print views (`account-statement.blade.php`, `download-report/sales-report.blade.php`, `print-report/sales-report.blade.php`)
- Underlying Models (`Customer`, `Order`, `Payment`, `OrderDetail`, `User`, `MasterSettings`, `Translation`), Migrations, and Shared Traits (`CsvExportable`).

---

## Executive Summary

A comprehensive architectural and mathematical audit of the Ledger, Sales, and Customer reporting modules in TidyPOS was performed. These three report modules provide business intelligence across customer account receivables, financial & operational throughput, and customer lifecycle retention.

While the primary accounting arithmetic (debit/credit running balances and lifecycle segmentation) follows sound domain models, several significant issues were identified across raw SQL queries, SoftDeletes handling, ApexCharts lifecycle persistence under Livewire DOM diffing, customer scoping permissions, and null-safety edge cases. Most critically, **`SalesReport.php` uses raw `DB::table()` queries that do not include `whereNull('deleted_at')` filters**, causing soft-deleted orders and payments to be counted in all top-level KPIs and chart aggregations while being excluded from the paginated orders table.

---

## 1. Architectural & Domain Mapping

### 1.1 Overview of Component Relationships

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              TidyPOS Report Section                              │
├────────────────────────┬───────────────────────────────┬────────────────────────┤
│     Ledger Report      │         Sales Report          │    Customer Report     │
│  (Accounts Receivable) │    (Financial & Operations)   │  (Retention & LTV)     │
├────────────────────────┼───────────────────────────────┼────────────────────────┤
│ Component:             │ Component:                    │ Component:             │
│  LedgerReport.php      │  SalesReport.php              │  CustomerReport.php    │
│ Blade View:            │ Blade View:                   │ Blade View:            │
│  ledger-report.blade   │  sales-report.blade           │  customer-report.blade │
│ Trait:                 │ Trait:                        │ Trait:                 │
│  CsvExportable         │  CsvExportable, WithPagination│  CsvExportable         │
│ Exports:               │ Exports:                      │ Exports:               │
│  PDF Statement, CSV    │  PDF Report, CSV              │  CSV                   │
└────────────────────────┴───────────────────────────────┴────────────────────────┘
                                      │
              ┌───────────────────────┴───────────────────────┐
              ▼                                               ▼
   Database / Eloquent Models:                    Shared Helpers & UI:
   - Order (SoftDeletes)                          - getFormattedCurrency($val)
   - Payment (SoftDeletes)                        - getCurrency()
   - Customer (SoftDeletes)                       - sanitize_search($term)
   - User (SoftDeletes, Permissions)              - x-dashboard-card
   - OrderDetail (SoftDeletes)                    - x-chart-container
```

---

## 2. Deep Dive: Ledger Report (`LedgerReport.php` & `ledger-report.blade.php`)

### 2.1 Mathematical & Accounting Model
1. **Debit vs. Credit Accounting**:
   - In customer receivables accounting, an **Order** increases customer debt (Debit, Dr), while a **Payment** decreases customer debt (Credit, Cr).
   - **Opening Balance**:
     $$\text{Opening Balance} = \sum_{\text{order\_date} < \text{start\_date}} \text{Order Total} - \sum_{\text{payment\_date} < \text{start\_date}} \text{Payment Received}$$
   - **Transaction Processing**:
     - For each Order: $\text{Running Balance}_{i} = \text{Running Balance}_{i-1} + \text{Order Total}$
     - For each Payment: $\text{Running Balance}_{i} = \text{Running Balance}_{i-1} - \text{Payment Received}$
   - **Balance Direction Indicators**:
     - If $\text{Running Balance} > 0$: Customer has an outstanding debit balance (displayed in red with `(Dr)`).
     - If $\text{Running Balance} < 0$: Customer has an overpaid/credit balance (displayed in green with `(Cr)`).
     - If $\text{Running Balance} = 0$: Settled account.
2. **Receivables Ageing Analysis**:
   - Evaluated across unpaid order balances ($\text{Order Total} - \text{Order Payments} > 0$) for non-voided orders (`status != 4`):
     - `0_30`: Order age $\le 30$ days (Green)
     - `31_60`: Order age $31 - 60$ days (Yellow)
     - `61_90`: Order age $61 - 90$ days (Orange)
     - `90_plus`: Order age $> 90$ days (Critical Red)
   - Total Outstanding KPI $= \sum(\text{Ageing Buckets})$.
3. **Top 10 Debtors Ranking**:
   - Aggregates lifetime orders vs lifetime payments per customer:
     $$\text{Total Owed} = \text{COALESCE}(\text{total\_ordered}, 0) - \text{COALESCE}(\text{total\_paid}, 0)$$
   - Filters `having('total_owed', '>', 0)` and orders by `total_owed DESC LIMIT 10`.
   - Calculates `days_outstanding` based on `last_payment_date ?? last_order_date`.

### 2.2 SQL & Eloquent Query Logic
- **`data()` Query**:
  ```sql
  SELECT order_date as date, 'debit' as type, order_number, total, 0 as received_amount
  FROM orders 
  WHERE customer_id = ? AND DATE(order_date) >= ? AND DATE(order_date) <= ? AND status != 4 AND deleted_at IS NULL
  UNION ALL
  SELECT payment_date as date, 'credit' as type, NULL as order_number, 0 as total, received_amount
  FROM payments 
  WHERE customer_id = ? AND DATE(payment_date) >= ? AND DATE(payment_date) <= ? AND deleted_at IS NULL
  ORDER BY date ASC
  ```
  - Correctly excludes soft-deleted records (`deleted_at IS NULL`).
  - Correctly excludes returned/voided orders (`status != 4`).
- **`firstData()` Query**:
  - Uses Eloquent `Order::where(...)` and `Payment::where(...)`, which automatically inherit soft-delete scopes.

### 2.3 Livewire Component Lifecycle & UI Interaction
- **Search Auto-Complete**: `wire:model.live="customer_query"` triggers `updated('customer_query', $value)`:
  - Sanitizes `%` and `_` via `sanitize_search()`.
  - Enforces viewable customer IDs via `Auth::user()->getViewableCustomerUserIds()`.
  - Limits search results to 5 matches.
- **Customer Selection**: Clicking a search result or a row in the Top 10 Debtors table calls `selectCustomer($id)`.
- **Exports**:
  - `downloadStatement()`: Generates DomPDF stream using `account-statement.blade.php`.
  - `downloadCsv()`: Uses `CsvExportable` trait. If a customer is selected, exports statement lines with opening and closing balances; if no customer is selected, exports the Top 10 Debtors list.

### 2.4 Identified Findings & Discrepancies
1. **ApexCharts Re-rendering / Missing `wire:ignore`**:
   - In `resources/views/livewire/reports/ledger-report.blade.php`, `<div id="ageingChart"></div>` is rendered inside `<x-chart-container>`. When Livewire performs DOM diffing on customer search or date selection, the canvas element is wiped or duplicated because it lacks `wire:ignore` on the chart wrapper.
2. **Top Debtors Permission Bypass**:
   - While `updated('customer_query', ...)` respects `getViewableCustomerUserIds()`, `topDebtors()` executes a raw subquery on `customers` without applying `created_by` filters for non-admin users with scoped customer visibility.
3. **`calculateAgeing()` Lifecycle Call**:
   - `calculateAgeing()` is executed solely in `mount()`. If the component state remains open while background POS transactions alter balances, the global ageing card remains static unless refreshed.
4. **Date Parsing Fallback on Empty Input**:
   - If a user clears the `start_date` or `end_date` text input, `Carbon::parse($this->start_date)` parses an empty string as "today", which may cause unexpected query bounds without explicit null checking.

---

## 3. Deep Dive: Sales Report (`SalesReport.php` & `sales-report.blade.php`)

### 3.1 Mathematical & Metric Model
1. **Financial View Metrics**:
   - **Revenue Billed**: $\sum \text{total}$ for Delivered orders (`status = 3`) within $[ \text{from\_date}, \text{to\_date} ]$.
   - **Discount Total**: $\sum \text{discount}$ for Delivered orders.
   - **Discount Rate %**: $\frac{\text{Discount}}{\text{Revenue Billed} + \text{Discount}} \times 100$.
   - **Cash Collected**: $\sum \text{received\_amount}$ from `payments` within $[ \text{from\_date}, \text{to\_date} ]$.
   - **Collection Rate %**: $\frac{\text{Cash Collected}}{\text{Revenue Billed}} \times 100$.
   - **AOV (Average Order Value)**: $\frac{\text{Revenue Billed}}{\text{Completed Orders Count}}$.
   - **Sales Growth % (Period-over-Period)**:
     - Interval duration $D = \text{diffInDays}(\text{from\_date}, \text{to\_date}) + 1$.
     - Previous period: $[ \text{from\_date} - D, \text{from\_date} - 1 ]$.
     - $\text{Growth} = \frac{\text{Revenue Billed} - \text{Prev Sales}}{\text{Prev Sales}} \times 100$.
2. **Operations View Metrics**:
   - **Total Orders**: Count of all orders in the period.
   - **Average Turnaround Time (TAT)**: $\text{AVG}(\text{DATEDIFF}(\text{updated\_at}, \text{order\_date}))$ for Delivered orders (`status = 3`).
   - **On-Time Delivery %**: $\frac{\text{Delivered Orders with DATE(updated\_at)} \le \text{delivery\_date}}{\text{Delivered Orders Count}} \times 100$.
   - **Return Rate %**: $\frac{\text{Returned Orders (status = 4)}}{\text{Total Orders}} \times 100$.
   - **Overdue Count**: Orders with $\text{delivery\_date} < \text{today}$ where status $\notin \{3 (\text{Delivered}), 4 (\text{Returned})\}$.

### 3.2 SQL & Eloquent Query Logic
- **Critical Architectural Flaw: Missing SoftDeletes in Raw Builder**:
  - `SalesReport.php` lines 65, 78, 90, 112, 140, 153, 170 execute direct `DB::table('orders')`, `DB::table('payments')`, and `DB::table('order_details')` queries without checking `whereNull('deleted_at')`.
  - Migration `2026_07_13_135555_add_soft_deletes_to_order_tables.php` added soft deletes to `orders`, `order_details`, `order_addon_details`, and `payments`.
  - However, line 253 in `orders()` uses Eloquent `\App\Models\Order::whereDate(...)` with `withSum('payments as paid', 'received_amount')`, which DOES automatically exclude soft-deleted records.
  - **Consequence**: Soft-deleted orders are included in Revenue Billed, Cash Collected, Growth %, Order Pipeline chart, and Service Breakdown chart, but disappear from the data table below, creating a direct discrepancy between charts/cards and table rows!

### 3.3 Livewire Component Lifecycle & UI Interaction
- **Dual Tab Architecture**:
  - Uses `#[Url(as: 'tab')] public $activeTab = 'financial'`.
  - Financial Tab: Renders financial KPI cards, Revenue Trend Line Chart, 100% Stacked Service Breakdown Horizontal Bar Chart, and Financial Data Table (with Discount, Total, Paid, Outstanding).
  - Operations Tab: Renders operational KPI cards, Filter by Status dropdown, Order Pipeline Bar Chart, and Operations Data Table (with Status badges, Promised Delivery, and Late flag).
- **Event-Driven Chart Updating**:
  - `report()` dispatches `'update-sales-charts'` event with payload containing `services`, `trendLabels`, `trendData`, `pipelineData`, and `activeTab`.
  - Blade template listens via `Livewire.on('update-sales-charts', ...)` and triggers `initCharts()`.

### 3.4 Identified Findings & Discrepancies
1. **Raw SQL SoftDeletes Leakage**:
   - `DB::table('orders')` and `DB::table('payments')` queries must explicitly add `whereNull('deleted_at')` to match Eloquent query behavior.
2. **Database Portability of TAT Metric**:
   - `AVG(DATEDIFF(updated_at, order_date))` uses MySQL syntax. In testing/SQLite environments where `DATEDIFF` is not natively registered, this query fails if executed.
3. **Late Flag Null Evaluation in Operations Table**:
   - In `sales-report.blade.php`:
     ```blade
     @if(in_array($item->status, [0,1,2]) && \Carbon\Carbon::parse($item->delivery_date)->endOfDay()->isPast())
     ```
     If an order has a `null` `delivery_date`, `Carbon::parse(null)` evaluates to current timestamp. A check `if ($item->delivery_date && ...)` prevents erroneous evaluation.
4. **PDF Download Filter Disconnect**:
   - `downloadFile()` loads `livewire.reports.download-report.sales-report` which hardcodes `where('status', 3)`. If a user is on the Operations tab analyzing Pending or Ready orders, the PDF download outputs only Delivered orders without informing the user.

---

## 4. Deep Dive: Customer Report (`CustomerReport.php` & `customer-report.blade.php`)

### 4.1 Mathematical & Metric Model
1. **Customer Lifecycle Segmentation Logic**:
   - Evaluates registration date and historical visit frequency:
     - **New (1)**: $\text{regDate} \ge \text{today} - 30\text{d} \lor \text{firstVisit} \ge \text{today} - 30\text{d}$
     - **Active (2)**: Days since last visit $\le 21$ days
     - **Lapsing (3)**: Days since last visit $22 - 40$ days
     - **Dormant (4)**: Days since last visit $41 - 60$ days
     - **Lost (5)**: Days since last visit $> 60$ days (or registered $> 30$ days ago with zero orders)
2. **Financial Aggregations per Customer**:
   - **Total Spend**: $\sum \text{orders.total}$ for all non-voided (`status != 4`) orders.
   - **Total Paid**: $\sum \text{payments.received\_amount}$.
   - **Outstanding Debt**: $\max(\text{Total Spend} - \text{Total Paid}, 0)$.
   - **AOV**: $\frac{\text{Total Spend}}{\text{Total Orders}}$.
   - **Spend (30 Days)**: $\sum \text{orders.total}$ where $\text{order\_date} \ge \text{today} - 30\text{d}$.
   - **Spend (7 Days)**: $\sum \text{orders.total}$ where $\text{order\_date} \ge \text{today} - 7\text{d}$.
3. **KPI Summary Cards**:
   - **Total Customers**: Count of all active customer records in scope.
   - **At-Risk Count**: $\text{Lapsing} + \text{Dormant}$.
   - **Total Outstanding Debt**: Global sum of positive outstanding balances.
   - **Average Lifetime Value (Avg LTV)**: $\frac{\sum \text{Total Spend}}{\text{Total Customers}}$.

### 4.2 SQL & Eloquent Query Logic
- **Aggregated Query Structure**:
  ```php
  $customersAggregates = $query->select(
          'customers.id',
          'customers.name',
          'customers.phone',
          'customers.created_at as registration_date',
          DB::raw('COUNT(orders.id) as total_orders'),
          DB::raw('COALESCE(SUM(orders.total), 0) as total_spend'),
          DB::raw('MAX(orders.order_date) as last_visit'),
          DB::raw('MIN(orders.order_date) as first_visit')
      )
      ->selectRaw("COALESCE(SUM(CASE WHEN orders.order_date >= ? THEN orders.total ELSE 0 END), 0) as spend_30", [$thirtyDaysAgo->toDateString()])
      ->selectRaw("COALESCE(SUM(CASE WHEN orders.order_date >= ? THEN orders.total ELSE 0 END), 0) as spend_7", [Carbon::today()->subDays(7)->toDateString()])
      ->leftJoin('orders', function ($join) {
          $join->on('customers.id', '=', 'orders.customer_id')
               ->where('orders.status', '!=', 4)
               ->whereNull('orders.deleted_at');
      })
      ->groupBy('customers.id', 'customers.name', 'customers.phone', 'customers.created_at')
      ->get();
  ```
- **Payments Lookup**:
  - Efficiently queries `payments` grouped by `customer_id`, keyed by `customer_id` into a collection map, avoiding $N+1$ queries.
- **Acquisition Trend**:
  - Dynamically builds a 12-month rolling histogram from `created_at` grouped by Year and Month.

### 4.3 Livewire Component Lifecycle & UI Interaction
- **Rendering Hook**:
  - `render()` method explicitly invokes `$this->customersData;` prior to returning the blade view, ensuring `$this->kpiSummary` is calculated and populated before blade component evaluation.
- **Filtering**:
  - `statusFilter` bound via `wire:model.live="statusFilter"`.
  - Filters table rows while preserving global KPI calculations across all customers.
- **CSV Export**:
  - Exports filtered/sorted customer dataset with lifecycle stage, orders, spend, and debt.

### 4.4 Identified Findings & Discrepancies
1. **Missing `wire:ignore` on Acquisition Trend Chart**:
   - In `customer-report.blade.php`, `<div id="acquisitionChart"></div>` is rendered without `wire:ignore`. Changing the `statusFilter` dropdown re-renders the component DOM, causing ApexCharts to disappear because there is no Livewire re-init listener.
2. **Missing Scoped Customer Permission on Acquisition Trend**:
   - `customersData()` filters by `Auth::user()->getViewableCustomerUserIds()`, but `acquisitionTrend()` queries all customers globally without the `created_by` constraint.
3. **Lack of Table Pagination**:
   - Unlike `SalesReport` (which paginates 50 per page), `CustomerReport` renders all customers in a single unpaginated table. In production systems with thousands of customers, this will cause high memory usage and slow DOM rendering.
4. **Hardcoded Sort Order**:
   - The table is statically sorted by `total_spend DESC` in PHP memory (`sortByDesc('total_spend')`) without interactive column sorting controls.

---

## 5. Comparative Trait & Helper Integration Analysis

| Feature / Trait | Ledger Report | Sales Report | Customer Report | Standard / Consistency Evaluation |
| :--- | :--- | :--- | :--- | :--- |
| **`CsvExportable` Trait** | `use \App\Traits\CsvExportable;` | `use \App\Traits\CsvExportable;` | `use \App\Traits\CsvExportable;` | Consistent across all 3 reports with UTF-8 BOM. |
| **`WithPagination` Trait** | Not used | `use WithPagination;` (50/page) | Not used | Inconsistent: Large customer bases require pagination. |
| **Permission Gate** | `Gate::allows('report_ledger')` | `Gate::allows('report_sales')` | `Gate::allows('report_customer')` | Consistent gate authorization in `mount()`. |
| **Currency Formatter** | `getFormattedCurrency()` | `getFormattedCurrency()` | `getFormattedCurrency()` | Consistent helper usage. |
| **SoftDeletes Handling** | Fully handled (`whereNull`) | **BUG: Raw queries leak soft deletes** | Fully handled (`whereNull`) | Sales report needs immediate remediation. |
| **Chart Re-render Safety** | Missing `wire:ignore` | Event listener with timeout | Missing `wire:ignore` | Ledger and Customer reports lack re-init listeners / `wire:ignore`. |
| **User Scoping (`created_by`)**| Handled in search, missing in topDebtors | Not applied | Handled in table, missing in trend | Scoping should be uniformly applied across sub-queries. |

---

## 6. Detailed Bug & Discrepancy Registry

### Finding 1: Soft-Deleted Orders & Payments Leaking into Sales Report KPIs
- **File**: `app/Livewire/Reports/SalesReport.php`
- **Lines**: 65-70, 78-81, 90-93, 112-130, 140-147, 153-167, 170-178
- **Severity**: High (Mathematical Inaccuracy)
- **Description**: Raw `DB::table('orders')`, `DB::table('payments')`, and `DB::table('order_details')` queries do not check `whereNull('deleted_at')`. Soft-deleted orders/payments are aggregated into Revenue Billed, Cash Collected, Discount Rate, TAT, and Charts, whereas the table query in `orders()` uses Eloquent and excludes them.
- **Recommended Remediation**: Add `->whereNull('orders.deleted_at')` and `->whereNull('payments.deleted_at')` across all `DB::table()` query builders in `SalesReport.php`.

### Finding 2: Missing `wire:ignore` on ApexCharts in Ledger and Customer Reports
- **File**: `resources/views/livewire/reports/ledger-report.blade.php` (line 16), `resources/views/livewire/reports/customer-report.blade.php` (line 47)
- **Severity**: Medium (UI / Rendering Flaw)
- **Description**: Chart containers lack `wire:ignore`. When Livewire performs DOM updates upon user input (such as searching a customer in Ledger Report or changing status filter in Customer Report), the ApexCharts SVG DOM tree is wiped out or corrupted.
- **Recommended Remediation**: Wrap chart container divs with `wire:ignore` or implement Livewire chart update dispatch events identical to `SalesReport.php`.

### Finding 3: Top Debtors and Acquisition Trend Customer Scoping Bypass
- **File**: `app/Livewire/Reports/LedgerReport.php` (line 132), `app/Livewire/Reports/CustomerReport.php` (line 60)
- **Severity**: Medium (Data Isolation / Consistency)
- **Description**: When a staff user with limited customer access (`getViewableCustomerUserIds()`) views the Ledger Report or Customer Report, the main customer search/table is scoped to their accessible IDs, but `topDebtors()` and `acquisitionTrend()` query the entire database without scoping.
- **Recommended Remediation**: Apply `whereIn('created_by', $viewable_ids)` when `$viewable_ids !== 'all'` in both `topDebtors()` and `acquisitionTrend()`.

### Finding 4: Null Safety on Promised Delivery Date in Sales Report Operations View
- **File**: `resources/views/livewire/reports/sales-report.blade.php`
- **Line**: 237
- **Severity**: Low (Edge Case Flaw)
- **Description**: Evaluates `\Carbon\Carbon::parse($item->delivery_date)->endOfDay()->isPast()`. If `delivery_date` is `null`, `Carbon::parse(null)` defaults to the current datetime, leading to ambiguous evaluation.
- **Recommended Remediation**: Guard with `$item->delivery_date && \Carbon\Carbon::parse($item->delivery_date)->endOfDay()->isPast()`.

### Finding 5: Unpaginated Customer Report Table
- **File**: `app/Livewire/Reports/CustomerReport.php`
- **Line**: 206
- **Severity**: Low (Performance Bottleneck)
- **Description**: Customer report loads all customers into memory and renders an unpaginated table. In stores with $>1,000$ customers, this increases memory consumption and response latency.
- **Recommended Remediation**: Implement `WithPagination` or virtual scroll chunking.

---

## 7. Conclusion & Next Steps

The mathematical models for the Ledger, Sales, and Customer reports are well-designed and align with standard retail POS and accounting requirements. However, the identified discrepancy regarding soft deletes in `SalesReport.php`, chart persistence issues under Livewire DOM diffing, and permission scoping inconsistencies should be addressed in the implementation phase to achieve 100% data integrity and UI stability.
