# Comprehensive Survey Report: Tax, Expense, and Daily Reports

**Author:** Survey Explorer 1  
**Audit Scope:**  
- `app/Livewire/Reports/TaxReport.php` & `resources/views/livewire/reports/tax-report.blade.php`
- `app/Livewire/Reports/ExpenseReport.php` & `resources/views/livewire/reports/expense-report.blade.php`
- `app/Livewire/Reports/DailyReport.php` & `resources/views/livewire/reports/daily-report.blade.php`
- Associated PDF download views (`resources/views/livewire/reports/download-report/`)
- Associated print views (`resources/views/livewire/reports/print-report/`)
- Underlying Models (`Order`, `Expense`, `ExpenseCategory`, `Payment`, `OrderDetail`, `Translation`), migrations, and `CsvExportable` trait.

---

## 1. Executive Summary

A comprehensive architectural and mathematical investigation was conducted across the **Tax Report**, **Expense Report**, and **Daily Report** modules of TidyPOS. The survey analyzed query integrity, mathematical accuracy, Livewire 3 reactivity, Blade component rendering, CSV/PDF export mechanisms, and multi-currency/localization support.

### Key Summary of Findings:
1. **Mathematical Accuracy**: The core mathematical models in `TaxReport.php` and `ExpenseReport.php` are sound and handle tax inclusive vs exclusive calculations accurately. However, a **critical discrepancy exists in the PDF download and print views for Expense Report** (`download-report/expense-report.blade.php` and `print-report/expense-report.blade.php`), where expenses are blindly multiplied by tax percentage regardless of whether tax is already included (`tax_included == 1`).
2. **Soft Delete Scope Bypassing in Daily Report**: `DailyReport.php` uses raw `DB::table` queries for item volume calculation (`order_details` joined with `orders`) and 7-day payment trend without checking `whereNull('deleted_at')`, potentially aggregating soft-deleted orders and payments.
3. **Database Portability / SQL Compatibility**: `DailyReport.php` utilizes `DATEDIFF(CURRENT_DATE, orders.delivery_date)` in raw SQL for overdue deliveries. While functional on MySQL/MariaDB, this function is non-standard and fails on SQLite environments (such as in-memory automated test runners).
4. **UI & Localization**: All three reports properly leverage `x-dashboard-card`, `x-chart-container`, and `iconify-icon`. Translation strings from `Translation` data JSON are utilized with safe fallbacks.
5. **CSV & PDF Export**: All three Livewire components integrate `App\Traits\CsvExportable` with UTF-8 BOM headers. Minor title and permission discrepancies were identified in helper download/print Livewire wrappers.

---

## 2. Deep-Dive Architecture & Code Mapping

### 2.1 Tax Report (`app/Livewire/Reports/TaxReport.php`)

#### Purpose & Lifecycle
`TaxReport` provides tax aggregation and tax rate band summaries for Sales (Orders) and Expenses across a selectable date range (`from_date` to `to_date`).
- **Mount & Auth**: Verifies `Gate::allows('report_tax')` (`TaxReport.php:34-36`). Defaults date range to today.
- **Reactivity**: `wire:model.live` on `from_date`, `to_date`, and `category` triggers `updated()`, recomputing `$this->report()`.
- **Simultaneous Dual Aggregation**: On every `report()` execution, both sales taxes and expense taxes are calculated simultaneously, allowing the top KPI cards to always display the net tax position regardless of which tab/category is actively displayed in the data table.

#### Mathematical Formulas & Logic Chain
1. **Sales Tax (Category 1)**:
   - Order query: `status = 3` (Delivered) between `from_date` and `to_date`.
   - Taxable amount formula:
     $$\text{Taxable Amount} = \begin{cases} \text{order.taxable\_amount} & \text{if } \text{order.taxable\_amount} > 0 \\ \text{order.total} - \text{order.tax\_amount} & \text{otherwise} \end{cases}$$
   - Rate Bands Grouping:
     $$\text{rateBandsTemp}[rate] = \{\text{count} += 1, \text{taxable} += \text{taxable}, \text{tax} += \text{tax\_amount}, \text{total} += \text{total}\}$$
     Sorted by tax rate ascending via `usort($this->rateBands, fn($a, $b) => $a['rate'] <=> $b['rate'])`.
2. **Expense Tax (Category 2)**:
   - Evaluates `tax_included` flag on each `Expense` record:
     - **Inclusive (`tax_included == 1`)**:
       $$\text{Tax Amount} = \text{expense\_amount} - \left(\frac{\text{expense\_amount}}{1 + \frac{\text{tax\_percentage}}{100}}\right)$$
       $$\text{Before Tax} = \text{expense\_amount} - \text{Tax Amount}$$
       $$\text{Row Total} = \text{expense\_amount}$$
     - **Exclusive (`tax_included == 0`)**:
       $$\text{Tax Amount} = \text{expense\_amount} \times \left(\frac{\text{tax\_percentage}}{100}\right)$$
       $$\text{Before Tax} = \text{expense\_amount}$$
       $$\text{Row Total} = \text{Before Tax} + \text{Tax Amount}$$
3. **Net Tax Position (KPI Card 3)**:
   $$\text{Net Tax Due} = \text{salesTaxTotal} - \text{expenseTaxTotal}$$
   - Displays as `primary` color if $\ge 0$ (payable to tax authority) or `warning` if $< 0$ (tax credit).

#### Exports & Downloads
- **CSV Export** (`TaxReport.php:151-184`): Exports date, reference number (`order_number` for sales, `id` for expenses), total amount, before tax/taxable amount, tax percentage, and tax amount.
- **PDF Download** (`TaxReport.php:142-149`): Invokes `livewire.reports.download-report.tax-report`. Math in `download-report/tax-report.blade.php:101-122` exactly mirrors `TaxReport.php`.

---

### 2.2 Expense Report (`app/Livewire/Reports/ExpenseReport.php`)

#### Purpose & Lifecycle
`ExpenseReport` provides high-level financial oversight comparing Cash Collected vs Total Expenses, Category Breakdown comparison against the prior period, a 6-month historical trend, and an interactive sorted/filtered expenses list.
- **Mount & Auth**: Verifies `Gate::allows('report_expense')` (`ExpenseReport.php:37-39`). Defaults date range to `startOfMonth()` to `endOfMonth()`.
- **Reactivity**: `updated()` triggers `report()` which re-calculates KPIs, prior period comparison, 6-month trend, and dispatches the browser event `update-expense-charts` to update ApexCharts without full page reloads.

#### Mathematical Formulas & Metric Definitions
1. **KPI Aggregations**:
   - `totalExpenses`: $\sum \text{expenses.expense\_amount}$ where `expense_date` $\in [\text{from\_date}, \text{to\_date}]$.
   - `totalIncome` (Cash Inflow): $\sum \text{payments.received\_amount}$ where `payment_date` $\in [\text{from\_date}, \text{to\_date}]$.
   - `revenueBilled` (Accrual Sales): $\sum \text{orders.total}$ where `order_date` $\in [\text{from\_date}, \text{to\_date}]$ and `status = 3`.
   - `net_cash`: $\text{totalIncome} - \text{totalExpenses}$.
2. **Prior Period Comparison**:
   - Calculates duration: $D = \text{diffInDays}(\text{from\_date}, \text{to\_date})$.
   - Previous period dates:
     $$\text{prev\_from\_date} = \text{from\_date} - (D + 1) \text{ days}$$
     $$\text{prev\_to\_date} = \text{from\_date} - 1 \text{ day}$$
   - Groups current and previous expenses by category name (`COALESCE(expense_categories.expense_category_name, 'Uncategorized')`).
   - Merges all unique category keys and presents grouped bar chart data.
3. **6-Month Trend Traversal**:
   - Determines 6-month window from $\text{to\_date}.\text{endOfMonth}() - 5 \text{ months}$ to $\text{to\_date}.\text{endOfMonth}()$.
   - Groups payments and expenses by `Y-m` and aligns arrays for dual-line ApexChart.
4. **Computed Expenses List (`expenses`)**:
   - Supports dynamic column sorting: `expense_date`, `category` (via left join), and `expense_amount`.
   - Category filter via `expense_category_id = $categoryFilter`.

---

### 2.3 Daily Report (`app/Livewire/Reports/DailyReport.php`)

#### Purpose & Lifecycle
`DailyReport` is the operational daily dashboard summarizing store throughput, revenue billed, cash collection gap, item processing volume, payment type breakdown, active backlog, delivered-unpaid orders, and overdue deliveries.
- **Mount & Auth**: Verifies `Gate::allows('report_daily')` (`DailyReport.php:42-44`). Defaults date to today.
- **Reactivity**: `wire:model.live="today"` updates `$today`, executes `report()`, and dispatches `update-daily-charts`.

#### Mathematical Formulas & Metric Definitions
1. **Operations Metrics**:
   - `new_order`: Total orders with `order_date = today` ($\text{count}$).
   - `delivered_orders`: Total orders with `order_date = today` and `status = 3`.
   - `itemVolume`: $\sum \text{order\_details.service\_quantity}$ for orders with `order_date = today`.
   - `pending_orders`: Cumulative backlog $\text{count}(\text{orders})$ where `status` $\in [0, 1, 2]$ (Pending, Processing, Ready to Deliver).
2. **Financial Flow & Gap**:
   - `total_sales` (Revenue Billed): $\sum \text{orders.total}$ for `status = 3` and `order_date = today`.
   - `cash_collected`: $\sum \text{payments.received\_amount}$ for `payment_date = today`.
   - `Collection Gap`: $\text{total\_sales} - \text{cash\_collected}$.
   - `total_expense`: $\sum \text{expenses.expense\_amount}$ for `expense_date = today`.
3. **Operational Risk Tables**:
   - **Delivered & Unpaid Today**:
     $$\text{amount\_owed} = \text{orders.total} - \text{COALESCE}\left(\sum \text{payments.received\_amount}, 0\right) > 0$$
     Filtered by `orders.order_date = today` and `orders.status = 3`.
   - **Overdue Deliveries**:
     $$\text{days\_overdue} = \text{DATEDIFF}(\text{CURRENT\_DATE}, \text{orders.delivery\_date})$$
     Filtered by `delivery_date < today` and `status NOT IN (3, 4)`.
4. **7-Day Inflow Trend**:
   - Traverses past 7 days ($\text{today} - 6 \text{ days} \to \text{today}$) aggregating daily cash collections from `payments`.
5. **Payment Method Split**:
   - Aggregates payments grouped by `payment_type` (CASH, UPI, CARD, CHEQUE, BANK TRANSFER) using `getpaymentMode()`.

---

## 3. Discrepancy & Bug Matrix

| ID | Module / File | Severity | Issue Description | Root Cause | Impact | Recommended Fix |
|:---|:---|:---|:---|:---|:---|:---|
| **BUG-01** | `download-report/expense-report.blade.php:102` & `print-report/expense-report.blade.php:82` | **High** | Tax calculation ignores `tax_included` flag on expenses. | Unconditionally calculates `$row->expense_amount * ($row->tax_percentage / 100)` regardless of whether tax is inclusive or exclusive. | Overstates tax amount for tax-inclusive expenses in generated PDF and print preview, causing numerical mismatch with `TaxReport`. | Implement `if ($row->tax_included == 1)` inclusive calculation matching `TaxReport.php:117-125`. |
| **BUG-02** | `DailyReport.php:107-110` | **Medium** | `itemVolume` raw query bypasses SoftDeletes on `orders` and `order_details`. | Uses `DB::table('order_details')->join('orders', ...)` without adding `whereNull('orders.deleted_at')` and `whereNull('order_details.deleted_at')`. | Soft-deleted orders or items will be erroneously included in the daily item volume count. | Add `->whereNull('orders.deleted_at')->whereNull('order_details.deleted_at')` or use Eloquent relationship. |
| **BUG-03** | `DailyReport.php:114-121` | **Medium** | 7-day payment trend raw query bypasses SoftDeletes on `payments`. | Uses `DB::table('payments')` without `whereNull('deleted_at')`. | Soft-deleted payment records will still appear in the 7-day chart inflow calculation. | Add `->whereNull('deleted_at')` or use `\App\Models\Payment::whereDate(...)`. |
| **BUG-04** | `DailyReport.php:87` | **Low / Env** | SQL portability issue with MySQL-specific `DATEDIFF()`. | `DB::raw('DATEDIFF(CURRENT_DATE, orders.delivery_date) as days_overdue')` is not supported in SQLite. | Automated test suites running in SQLite in-memory mode will crash with SQL syntax errors when rendering DailyReport. | Use database driver-agnostic calculation or compute `days_overdue` in PHP using `Carbon::parse($order->delivery_date)->diffInDays(now())`. |
| **BUG-05** | `download-report/daily-report.blade.php:20` | **Low** | Incorrect `<title>` tag in Daily Report PDF view. | Title tag is hardcoded as `<title>{{$lang->data['order_report'] ?? 'Order Report'}}</title>`. | PDF metadata shows "Order Report" instead of "Daily Report". | Update title tag to use `$lang->data['daily_report'] ?? 'Daily Report'`. |
| **BUG-06** | `DownloadReport/TaxReport.php:25-30` & `DownloadReport/ExpenseReport.php:23-28` | **Low** | Missing Gate authorization checks in Download Report Livewire wrappers. | Unlike `PrintReport` controllers which check `Gate::allows('report_print')`, `DownloadReport` components omit `Gate::allows('report_download')` or respective report permission check. | Direct route access relies solely on `admin` middleware without verifying granular role permissions. | Add permission checks in `mount()` methods. |
| **BUG-07** | `TaxReport.php:68-70` | **Low** | `latest()` called on select clause without `created_at`. | Select clause is `['id', 'order_number', 'order_date', 'tax_percentage', 'tax_type', 'tax_amount', 'taxable_amount', 'total']`. `latest()` defaults to `orderBy('created_at', 'desc')`. | Can trigger SQL column missing warnings or unexpected sorting behavior on strict database configs. | Change to `->orderBy('order_date', 'desc')->orderBy('id', 'desc')`. |
| **BUG-08** | `expense-report.blade.php:165,168,202,205` | **Low** | ApexCharts tooltip/axis formatter potential null reference. | JavaScript calls `val.toFixed(2)` directly in ApexCharts formatters. | If ApexCharts passes a null/undefined value for an empty data point, JS throws `TypeError: val.toFixed is not a function`. | Use `(parseFloat(val) || 0).toFixed(2)` for safe number formatting. |

---

## 4. Query & Performance Analysis

### 4.1 Indexing & Query Efficiency
- `orders` table has indexed `order_date`, `delivery_date`, `status`, and `deleted_at`.
- `expenses` table has indexed `expense_date` and foreign key on `expense_category_id`.
- `payments` table has indexed `payment_date` and `deleted_at`.
- All `whereDate` queries on these tables leverage existing indexes efficiently.

### 4.2 Aggregation Volume & Memory Optimization
- In `TaxReport.php`, orders are loaded into a collection to compute rate bands and format rows. For ordinary date ranges (daily/monthly), collection memory usage is negligible.
- In `ExpenseReport.php`, top KPI cards execute direct SQL aggregate functions (`sum('expense_amount')`, `sum('received_amount')`, `sum('total')`) which executes in $O(1)$ database memory.
- In `DailyReport.php`, payment split and overdue queries utilize indexed lookups with aggregations.

---

## 5. UI, Blade & Responsive Layout Verification

### 5.1 Component Hierarchy
- **KPI Cards**: Standardized on `<x-dashboard-card>` across all 3 reports.
  - Supports `title`, `value`, `icon`, `color`, `trend`, and `trendUp`.
  - Icon rendering uses `<iconify-icon>` with circle backgrounds.
- **Charts**: Encapsulated in `<x-chart-container>`.
  - ApexCharts are rendered via client-side JavaScript.
  - Chart options cleanly handle empty datasets with zero-fallbacks (`[0]` or `['No Payments']`).
- **Data Tables**:
  - Bordered tables with scrollable containers (`tw-min-h-[calc(100vh-16rem)]`).
  - Interactive sorting carets on `ExpenseReport` headers (`<iconify-icon icon="ph:caret-up-fill">`).
  - Currency formatting via `getFormattedCurrency()` consistently respects symbol, decimal precision (2 places), and left/right alignment settings.
- **Print & PDF Styling**:
  - Print layout components use `@media print` CSS rules in `layouts/app.blade.php` to hide navigation, sidebars, and action buttons, ensuring clean hardcopy printouts.
  - DomPDF views (`download-report/*`) use inline table styling suitable for DomPDF XHTML rendering engine.

---

## 6. Synthesis & Next Steps for Fix Phase

1. **Auto-Fix Priority 1 (Math Bug in Expense PDF/Print)**:
   Update `download-report/expense-report.blade.php` and `print-report/expense-report.blade.php` to calculate tax amounts with inclusive/exclusive branching matching `TaxReport.php`.
2. **Auto-Fix Priority 2 (SoftDelete Scopes in DailyReport)**:
   Add explicit `whereNull('deleted_at')` constraints to raw queries in `DailyReport.php` (`itemVolume` and `trend`).
3. **Auto-Fix Priority 3 (Database Agnostic Overdue Calculation)**:
   Refactor `overdueOrders` in `DailyReport.php` to compute `days_overdue` in PHP or with database-driver agnostic expressions.
4. **Auto-Fix Priority 4 (UI & Wrapper Cleanups)**:
   - Correct `<title>` in `download-report/daily-report.blade.php`.
   - Update `val.toFixed(2)` formatters in `expense-report.blade.php` to `(parseFloat(val) || 0).toFixed(2)`.
   - Add Gate permission checks to `DownloadReport` components.
   - Adjust `->latest()` to explicit `orderBy('order_date', 'desc')` in `TaxReport.php`.
