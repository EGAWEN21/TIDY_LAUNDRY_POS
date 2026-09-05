# Comprehensive Survey & Audit Report: TidyPOS Reports Section

**Auditor:** Explorer Survey 1  
**Target Scope:** `app/Livewire/Reports/`, `app/Traits/CsvExportable.php`, `resources/views/livewire/reports/`, and supporting routes/models  
**Audit Date:** August 24, 2026  
**Status:** Audit Complete — Read-Only Investigation  

---

## 1. Executive Summary & Inventory

This comprehensive audit covers all 15 Livewire report components, 1 trait, 20 report Blade views, and supporting route configurations across the TidyPOS report subsystem:
- **Core Reports (7):** Tax, Expense, Daily, Ledger, Sales, Customer, Business Insights
- **Print Reports (4):** DailyReport, ExpenseReport, SalesReport, TaxReport
- **Download / PDF Reports (4):** DailyReport, ExpenseReport, SalesReport, TaxReport
- **Traits (1):** `App\Traits\CsvExportable`

### Component Inventory Matrix

| Component / Class | Route Name | Gate / Auth | Views Rendered | Export Support |
|---|---|---|---|---|
| `App\Livewire\Reports\TaxReport` | `reports.tax` | `report_tax` | `livewire.reports.tax-report` | PDF, CSV |
| `App\Livewire\Reports\ExpenseReport` | `reports.expense` | `report_expense` | `livewire.reports.expense-report` | PDF, CSV |
| `App\Livewire\Reports\DailyReport` | `reports.daily` | `report_daily` | `livewire.reports.daily-report` | PDF |
| `App\Livewire\Reports\LedgerReport` | `reports.ledger` | `report_ledger` | `livewire.reports.ledger-report` | PDF (Statement) |
| `App\Livewire\Reports\SalesReport` | `reports.sales` | `report_sales` | `livewire.reports.sales-report` | PDF, CSV |
| `App\Livewire\Reports\CustomerReport` | `reports.customer` | `report_customer` | `livewire.reports.customer-report` | CSV |
| `App\Livewire\Reports\BusinessInsights` | `reports.insights` | `report_insights` | `livewire.reports.business-insights` | Print (Browser) |
| `App\Livewire\Reports\PrintReport\DailyReport` | (route: `print-report/daily/{today}`) | `report_print` | `livewire.reports.print-report.daily-report` | Browser Print |
| `App\Livewire\Reports\PrintReport\ExpenseReport` | (route: `print-report/expense/{from}/{to}`) | `report_print` | `livewire.reports.print-report.expense-report` | Browser Print |
| `App\Livewire\Reports\PrintReport\SalesReport` | (route: `print-report/sales/{from}/{to}`) | `report_print` | `livewire.reports.print-report.sales-report` | Browser Print |
| `App\Livewire\Reports\PrintReport\TaxReport` | (route: `print-report/tax/{from}/{to}/{cat}`) | `report_print` | `livewire.reports.print-report.tax-report` | Browser Print |
| `App\Livewire\Reports\DownloadReport\DailyReport` | *(No route registered)* | N/A | `livewire.reports.download-report.daily-report` | PDF View |
| `App\Livewire\Reports\DownloadReport\ExpenseReport` | (route: `download-report/expense/{from}/{to}`) | `admin` | `livewire.reports.download-report.expense-report` | PDF View |
| `App\Livewire\Reports\DownloadReport\SalesReport` | (route: `download-report/sales/{from}/{to}`) | `admin` | `livewire.reports.download-report.sales-report` | PDF View |
| `App\Livewire\Reports\DownloadReport\TaxReport` | (route: `download-report/tax/{from}/{to}/{cat}`) | `admin` | `livewire.reports.download-report.tax-report` | PDF View |
| `App\Traits\CsvExportable` | Trait | N/A | N/A | Streamed CSV |

---

## 2. Deep-Dive Component Mapping

### 2.1 TaxReport (`app/Livewire/Reports/TaxReport.php`)
- **Properties:**
  - `$from_date`, `$to_date`: Date strings (`Y-m-d`), default today.
  - `$category`: 1 for Sales, 2 for Expenses.
  - `$salesTaxTotal`, `$expenseTaxTotal`, `$salesTotal`, `$expenseTotal`, `$salesTaxableTotal`: Numeric aggregates.
  - `$rateBands`: Sequential array of `['rate', 'count', 'taxable', 'tax', 'total']`.
  - `$reportData`: Collection/array of Order or Expense records.
  - `$lang`: Translation model.
- **Methods:**
  - `mount()`: Checks `Gate::allows('report_tax')`, initializes dates, loads language, calls `report()`.
  - `updated($name, $value)`: Calls `report()`.
  - `report()`: Queries `Order` (status 3) and `Expense`, builds `$rateBands` by `$order->tax_percentage`, calculates taxable/tax totals.
  - `downloadFile()`: Generates PDF using `Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.reports.download-report.tax-report')`.
  - `downloadCsv()`: Streams CSV formatted with date, reference no, total, taxable amount, tax %, tax amount.
- **Database Queries:**
  - Orders: `Order::whereDate('order_date', '>=', $this->from_date)->whereDate('order_date', '<=', $this->to_date)->where('status', 3)->select([...])->latest()->get()`
  - Expenses: `Expense::whereDate('expense_date', '>=', $this->from_date)->whereDate('expense_date', '<=', $this->to_date)->with('expenseCategory')->latest()->get()`

### 2.2 ExpenseReport (`app/Livewire/Reports/ExpenseReport.php`)
- **Properties:**
  - `$from_date`, `$to_date`: Date strings, default start/end of current month.
  - `$categoryFilter`: Expense category ID filter.
  - `$expenseCategories`: Collection of all `ExpenseCategory` models.
  - `$sortBy`, `$sortDirection`: Default `'expense_date'`, `'desc'`.
  - `$kpi`: Array `['expenses', 'income', 'net_cash', 'revenue_billed']`.
  - `$categoryBreakdown`: Array of `['name', 'current_amount', 'previous_amount']`.
  - `$trendLabels`, `$trendData`: 6-month historical trend for cash collected vs expenses.
- **Methods:**
  - `mount()`: Checks `Gate::allows('report_expense')`, sets month date range, loads categories and language, calls `report()`.
  - `updated($name, $value)`: Re-executes `report()`.
  - `sortBy($field)`: Toggles asc/desc.
  - `report()`: Computes KPIs, Period-over-Period category breakdown, 6-month trend arrays, and dispatches browser event `update-expense-charts`.
  - `expenses()`: `#[Computed]` property querying expenses with date filter, category filter, and sorting.
  - `downloadFile()`, `downloadCsv()`: PDF & CSV export.
- **Database Queries:**
  - KPI Aggregates: `Expense::sum('expense_amount')`, `Payment::sum('received_amount')`, `Order::where('status', 3)->sum('total')`.
  - Category breakdown: `DB::table('expenses')->join('expense_categories', ...)->select(...)->groupBy(...)->pluck(...)`.
  - 6-Month Trend: `Expense::whereBetween(...)` & `Payment::whereBetween(...)` grouped in PHP by `Y-m`.

### 2.3 DailyReport (`app/Livewire/Reports/DailyReport.php`)
- **Properties:**
  - `$today`: Date string, default today.
  - `$new_order`, `$delivered_orders`, `$total_payment`, `$total_expense`, `$total_sales`, `$cash_collected`, `$pending_orders`.
  - `$unpaidDeliveries`: Array of delivered orders with unpaid balance on `$today`.
  - `$overdueOrders`: Array of orders with promised delivery date before today not delivered/returned.
  - `$paymentSplit`: Array of payment mode breakdown `['name', 'amount']`.
  - `$itemVolume`: Sum of `order_details.service_quantity`.
  - `$trendLabels`, `$trendData`: 7-day payment inflow trend.
- **Methods:**
  - `mount()`: Gate check `report_daily`, default `$today`, calls `report()`.
  - `updated($name, $value)`: Updates `$today` and calls `report()`.
  - `report()`: Computes counts, sums, unpaid deliveries, overdue orders, payment split, item volume, 7-day trend, dispatches `update-daily-charts`.
  - `downloadFile()`: PDF stream.
- **Database Queries:**
  - `Order::whereDate('order_date', $this->today)->count()`.
  - `Payment::whereDate('payment_date', $this->today)->sum('received_amount')`.
  - Subquery in raw SQL for `unpaidDeliveries` calculating `orders.total - COALESCE((SELECT SUM(received_amount) FROM payments WHERE order_id = orders.id), 0)`.
  - `DATEDIFF(CURRENT_DATE, orders.delivery_date)` for overdue orders.
  - `order_details` joined to `orders` for item quantity.

### 2.4 LedgerReport (`app/Livewire/Reports/LedgerReport.php`)
- **Properties:**
  - `$selected_customer`: Currently selected `Customer` model.
  - `$customers`: Customer search result collection.
  - `$customer_query`: Search term.
  - `$start_date`, `$end_date`: Date range, default start/end of month.
  - `$ageingData`: 4-bucket array `[0_30, 31_60, 61_90, 90_plus]`.
  - `$totalOutstanding`: Sum of ageing buckets.
- **Methods:**
  - `mount()`: Gate check `report_ledger`, sets default date range, calls `calculateAgeing()`.
  - `calculateAgeing()`: Computes global accounts receivable ageing buckets using `leftJoinSub` of payments against orders.
  - `updated($name, $value)`: Live search for customers by name or phone with `sanitize_search` and `getViewableCustomerUserIds()`.
  - `selectCustomer($id)`: Sets `$selected_customer` and clears search.
  - `topDebtors()`: `#[Computed]` returning top 10 debtors with `total_owed > 0` and `days_outstanding`.
  - `data()`: `#[Computed]` returning chronological union of debits (orders) and credits (payments) for `$selected_customer`.
  - `firstData()`: `#[Computed]` returning opening debit/credit sums prior to `$start_date`.
  - `downloadStatement()`: Loads `download-report.account-statement` PDF.

### 2.5 SalesReport (`app/Livewire/Reports/SalesReport.php`)
- **Properties:**
  - `$from_date`, `$to_date`: Date range.
  - `$activeTab`: `'financial'` or `'operations'` (bound to URL `?tab=`).
  - `$status`: Filter by status (-1 for all).
  - `$financialKpi`: `['revenue_billed', 'cash_collected', 'collection_rate_pct', 'aov', 'discount_rate_pct', 'growth']`.
  - `$operationalKpi`: `['total_orders', 'avg_tat', 'on_time_pct', 'return_rate', 'overdue_count']`.
  - `$trendLabels`, `$trendData`: Daily revenue trend for delivered orders.
  - `$pipelineData`: Status distribution `[Pending, Processing, Ready, Delivered, Returned]`.
  - `$serviceBreakdown`: Revenue grouped by service name.
- **Methods:**
  - `mount()`: Gate check `report_sales`, default date range, calls `report()`.
  - `updated($name, $value)`: Calls `report()`.
  - `report()`: Computes financial and operational KPIs, growth vs previous period, daily trend, pipeline counts, service breakdown, dispatches `update-sales-charts`.
  - `orders()`: `#[Computed]` paginating 50 orders with payment sums and calculated `outstanding`.
  - `downloadFile()`, `downloadCsv()`: Exports.

### 2.6 CustomerReport (`app/Livewire/Reports/CustomerReport.php`)
- **Properties:**
  - `$statusFilter`: `'0'` (All), `'1'` (New), `'2'` (Active), `'3'` (Lapsing), `'4'` (Dormant), `'5'` (Lost).
  - `$kpiSummary`: `['total_customers', 'active_count', 'at_risk_count', 'lost_count', 'total_outstanding', 'avg_ltv']`.
- **Methods:**
  - `mount()`: Gate check `report_customer`, language load.
  - `acquisitionTrend()`: `#[Computed]` returning 12-month customer sign-up counts.
  - `customersData()`: `#[Computed]` calculating RFM lifecycle classification (New, Active, Lapsing, Dormant, Lost), spend metrics (spend_30, spend_7, lifetime spend, outstanding balance, AOV), and populates `$this->kpiSummary`.
  - `downloadCsv()`: CSV export of filtered customer list.

### 2.7 BusinessInsights (`app/Livewire/Reports/BusinessInsights.php`)
- **Properties:**
  - `$from_date`, `$to_date`: Date range.
  - `$operationsHealth`: `['avg_tat', 'on_time_pct', 'return_rate', 'overdue_count']`.
  - `$businessHealth`: `['collection_rate', 'aov', 'aov_trend_up', 'net_cash_position', 'new_customers_count']`.
  - `$staffPerformance`: List of staff metrics (revenue generated, AOV, returns %, revenue trend up/down, total orders).
  - `$monthlyRevenueTrend`: 6-month monthly revenue array.
- **Methods:**
  - `mount()`: Gate check `report_insights`, calls `generateInsights()`.
  - `updated($name, $value)`: Calls `generateInsights()` if `from_date` or `to_date` changed.
  - `generateInsights()`: Evaluates operations metrics, business metrics, staff leaderboard, and monthly revenue trajectory.

### 2.8 Trait: `App\Traits\CsvExportable`
- Contains `exportCsv(array $headers, array $rows, string $filename): StreamedResponse`.
- Uses `response()->streamDownload()` with `fputcsv()`.
- Used by `TaxReport`, `ExpenseReport`, `SalesReport`, and `CustomerReport`.

---

## 3. Discovered Bugs, Vulnerabilities, and Flaws

### Category A: Critical & High Priority Bugs

#### 1. JavaScript Runtime Crash in Ledger Report
- **File:** `resources/views/livewire/reports/ledger-report.blade.php:193`
- **Code:**
  ```javascript
  dataLabels: { 
      enabled: true,
      formatter: function (val) { return getCurrency() + val.toFixed(2); }
  },
  ```
- **Flaw:** `getCurrency()` is a backend PHP helper (`app/helper.php:139`), NOT a JavaScript function.
- **Impact:** Causes `Uncaught ReferenceError: getCurrency is not defined` in the browser console when rendering `ageingChart`. Crashes ApexCharts rendering for the Receivables Ageing chart on page load.
- **Remedy:** Replace with Blade interpolation `formatter: function (val) { return "{{ getCurrency() }}" + val.toFixed(2); }` or define currency symbol as JS variable.

#### 2. Missing Public `$lang` Property in All Print Report Components
- **Files:**
  - `app/Livewire/Reports/PrintReport/ExpenseReport.php`
  - `app/Livewire/Reports/PrintReport/SalesReport.php`
  - `app/Livewire/Reports/PrintReport/TaxReport.php`
  - `app/Livewire/Reports/PrintReport/DailyReport.php`
- **Flaw:** All four print Blade templates reference `$lang->data[...]` (e.g. `{{$lang->data['expense_report'] ?? 'Expense Report'}}`). However, none of these 4 Livewire components declare `public $lang;` or populate `$lang = getSessionTranslation();` in `mount()`.
- **Impact:** In PHP, `$lang` is `null` in the view. Multi-language translations never apply to print reports, and PHP 8.2+ deprecation warnings/notices may occur on null property access (`$lang->data`).
- **Remedy:** Add `public $lang;` and initialize `$this->lang = getSessionTranslation();` in `mount()` across all four print components.

#### 3. Customer Report Initial KPI Zero-State (Lifecycle Execution Order Bug)
- **Files:** `app/Livewire/Reports/CustomerReport.php:204` & `resources/views/livewire/reports/customer-report.blade.php:7-36`
- **Flaw:** In `CustomerReport.php`, `$this->kpiSummary` is updated inside `customersData()` (`$this->kpiSummary = $kpi;`). In `customer-report.blade.php`, the KPI summary cards (`$kpiSummary['total_customers']`, `$kpiSummary['at_risk_count']`, etc.) are rendered on lines 7-36 **before** the table loop calls `$this->customersData` on line 99.
- **Impact:** On initial page render, the KPI cards render with initial `0` values because `customersData()` has not been evaluated yet when the top cards are rendered.
- **Remedy:** Make `kpiSummary` a computed property `#[Computed] public function kpiSummary()` or calculate the summary during `mount()` / evaluate `$this->customersData` prior to rendering cards.

#### 4. Tax Calculation Flaws (Inclusive Tax Ignored & Stored Tax Recomputed)
- **Files:**
  - `app/Livewire/Reports/TaxReport.php:116-121`
  - `resources/views/livewire/reports/download-report/tax-report.blade.php:25, 121, 128`
  - `resources/views/livewire/reports/print-report/tax-report.blade.php:92, 99`
  - `resources/views/livewire/reports/download-report/expense-report.blade.php:101`
- **Flaw:**
  1. For expenses: The system supports `tax_included = 1`. When tax is inclusive in an expense amount $A$ with rate $r$, the tax amount is $A - \frac{A}{1 + r/100} = A \times \frac{r}{100 + r}$, NOT $A \times \frac{r}{100}$. Current code multiplies `expense_amount * (tax_percentage / 100)` unconditionally.
  2. For download/print tax views: The templates compute `$tax_amount_sales = $row->total * ($row->tax_percentage / 100)`. This is mathematically invalid because `$row->total` is already the gross amount (after discount and taxable calculations), and the exact `tax_amount` is already calculated and stored on the `Order` model in the database (`$row->tax_amount`).
  3. `download-report/tax-report.blade.php` explicitly contains an unaddressed `TODO` comment: `{{-- TODO: Tax calculation does not account for inclusive tax (tax_type=1) ... --}}`.
- **Impact:** Misstated tax liabilities on tax reports, discrepancies between dashboard figures and exported PDF figures.

---

### Category B: Medium Priority Logic & UI Flaws

#### 5. Date-Time Due Today Falsely Flagged as Overdue / Late
- **Files:**
  - `app/Livewire/Reports/BusinessInsights.php:86`
  - `resources/views/livewire/reports/sales-report.blade.php:236`
- **Code:**
  - `BusinessInsights.php`: `Carbon::parse($o->delivery_date)->isPast() && !in_array($o->status, [3, 4])`
  - `sales-report.blade.php`: `in_array($item->status, [0,1,2]) && \Carbon\Carbon::parse($item->delivery_date)->isPast()`
- **Flaw:** `Carbon::parse('2026-08-24')->isPast()` parses `'2026-08-24 00:00:00'`, which is considered in the past at any point during the business day of August 24.
- **Impact:** Orders promised for delivery *today* are falsely marked as "Late: Yes" and counted in "Overdue Now" KPI cards before the day has ended.
- **Remedy:** Check `Carbon::parse($delivery_date)->endOfDay()->isPast()` or `< Carbon::today()`.

#### 6. Missing Livewire Pagination Reset on Filter/Date Changes in Sales Report
- **File:** `app/Livewire/Reports/SalesReport.php`
- **Flaw:** Uses `WithPagination` for `$this->orders` (50 items/page). When the user changes `$from_date`, `$to_date`, `$status`, or `$activeTab`, `updated()` calls `$this->report()` but never calls `$this->resetPage()`.
- **Impact:** If the user is on page 4 of "Delivered" orders and switches status filter to "Processing" (which only has 1 page of results), the table shows "No orders found" because Livewire remains on page 4.
- **Remedy:** Add `$this->resetPage();` inside `updated()` when updating filter properties, or implement `updatingFromDate()`, `updatingToDate()`, `updatingStatus()`, `updatingActiveTab()`.

#### 7. Wrong Model Attribute Name for Customer Phone in Sales Report Table
- **File:** `resources/views/livewire/reports/sales-report.blade.php:117, 219`
- **Code:** `<span class="text-xs text-muted">{{ $item->customer_phone }}</span>`
- **Flaw:** `Order` model does not have a `customer_phone` column or accessor. The column name in the `orders` database schema is `phone_number`.
- **Impact:** Customer phone numbers are never rendered in the Sales Report orders table (renders blank).
- **Remedy:** Change `{{ $item->customer_phone }}` to `{{ $item->phone_number ?? $item->customer?->phone }}`.

#### 8. Category Sort & Grouping in Expense Report Drops Uncategorized Expenses
- **File:** `app/Livewire/Reports/ExpenseReport.php:98, 210`
- **Code:**
  - `report()`: `DB::table('expenses')->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')`
  - `expenses()`: `$query->join('expense_categories', ...)`
- **Flaw:** In the migration `2022_02_21_092944_create_expenses_table.php`, `expense_category_id` is nullable (`$table->unsignedBigInteger('expense_category_id')->nullable();`). Using an inner `join` instead of `leftJoin` silently drops all uncategorized expenses from category breakdowns and from the data table when sorted by category.
- **Remedy:** Use `leftJoin` and `COALESCE(expense_categories.expense_category_name, 'Uncategorized')`.

#### 9. Potential Fatal Error on Null MasterSettings in Ledger Report
- **File:** `app/Livewire/Reports/LedgerReport.php:166`
- **Code:** `$master_settings = MasterSettings::first()->siteData();`
- **Flaw:** Chaining `->siteData()` directly on `MasterSettings::first()` without null check.
- **Impact:** If `MasterSettings` table is empty, throws fatal `Error: Call to a member function siteData() on null`.
- **Remedy:** Use `$master_settings = MasterSettings::first()?->siteData() ?? [];`.

---

### Category C: Performance & Architectural Bottlenecks

#### 10. In-Memory Aggregation over Large Datasets in ExpenseReport Trend
- **File:** `app/Livewire/Reports/ExpenseReport.php:136-153`
- **Flaw:** Fetches all 6 months of Eloquent `Expense` and `Payment` models into memory and runs PHP `groupBy` and `sum`.
- **Impact:** Unnecessary memory consumption and slow response times as transaction volume grows.
- **Remedy:** Replace with database-level monthly aggregation:
  ```php
  DB::table('expenses')
      ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as m, SUM(expense_amount) as total")
      ->whereBetween('expense_date', [$startDate, $endDate])
      ->groupBy('m')
      ->pluck('total', 'm');
  ```

#### 11. Database Queries Embedded Directly in Blade Views & N+1 Queries
- **Files:**
  - `resources/views/livewire/reports/download-report/daily-report.blade.php:1-13`
  - `resources/views/livewire/reports/download-report/expense-report.blade.php:58-61`
  - `resources/views/livewire/reports/download-report/sales-report.blade.php:1-6`
  - `resources/views/livewire/reports/download-report/tax-report.blade.php:1-16`
- **Flaw:** PDF Blade templates execute Eloquent database queries inside `@php ... @endphp` tags without eager-loading relations (e.g. `expenseCategory`), resulting in N+1 query overhead and violating MVC separation.
- **Remedy:** Compute and pass data directly from the calling component into `Pdf::loadView($view, $data)`.

#### 12. Missing UTF-8 BOM in CSV Exports
- **File:** `app/Traits/CsvExportable.php:20`
- **Flaw:** Streams CSV data without writing a UTF-8 Byte Order Mark (`\xEF\xBB\xBF`).
- **Impact:** Non-ASCII currency symbols (such as ₹, €, £, Arabic/CJK characters) appear corrupted (mojibake) when opened in Microsoft Excel.
- **Remedy:** Write `fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));` before outputting headers.

---

### Category D: UI & Blade Syntax Flaws

#### 13. Invalid Prop Binding Syntax in Dashboard Cards
- **Files:**
  - `resources/views/livewire/reports/expense-report.blade.php:64` (`:trendUp="{{ ($kpi['net_cash'] ?? 0) >= 0 ? 'true' : 'false' }}"`)
  - `resources/views/livewire/reports/business-insights.blade.php:70` (`:trendUp="{{ ($businessHealth['aov_trend_up'] ?? false) ? 'true' : 'false' }}"`)
  - `resources/views/livewire/reports/sales-report.blade.php:48` (`trendUp="{{ ($financialKpi['growth'] ?? 0) >= 0 ? true : false }}"`)
- **Flaw:** Mixing Blade curly braces `{{ ... }}` inside colon-prefixed `:prop` attributes passes a string rather than a PHP boolean.
- **Remedy:** Use clean boolean expressions without interpolation: `:trendUp="($kpi['net_cash'] ?? 0) >= 0"`.

#### 14. Malformed HTML / Missing Closing Tags in Report Templates
- **Files:**
  - `resources/views/livewire/reports/download-report/tax-report.blade.php:146-156`: Missing closing `</p>` tag inside table cells.
  - `resources/views/livewire/reports/print-report/tax-report.blade.php:118-127`: Missing closing `</p>` tag inside table cells.
  - `resources/views/livewire/reports/print-report/expense-report.blade.php:47`: Fallback string is `'End'` instead of `'Date'` (`{{$lang->data['date'] ?? 'End'}}`).
  - `resources/views/livewire/reports/print-report/expense-report.blade.php:117-120`: Extra closing `</div>` tags.

---

## 4. Route Architecture & Orphaned Classes

1. **Orphaned Class:** `app/Livewire/Reports/DownloadReport/DailyReport.php` has no matching route in `routes/web.php` and no `mount()` method. Daily report PDF generation is handled via `DailyReport::downloadFile()` calling `Pdf::loadView('livewire.reports.download-report.daily-report', ...)`.
2. **Middleware Inconsistency:**
   - In `routes/web.php:73-84`: Print and Download report route groups specify `middleware => 'admin'`, whereas the main Livewire reports (`routes/web.php:61-71`) are under `middleware => ['auth', 'single.session']` and rely on Livewire component Gate checks.

---

## 5. Summary of Recommended Fixes for Worker Agents

| ID | File | Component | Priority | Recommended Action |
|---|---|---|---|---|
| **F-01** | `resources/views/livewire/reports/ledger-report.blade.php` | LedgerReport | **Critical** | Fix line 193 JS chart formatter to use `"{{ getCurrency() }}"`. |
| **F-02** | `app/Livewire/Reports/PrintReport/*.php` (4 files) | PrintReports | **Critical** | Add `public $lang;` and initialize in `mount()`. |
| **F-03** | `app/Livewire/Reports/CustomerReport.php` & Blade | CustomerReport | **High** | Calculate `$kpiSummary` in `mount()` / make it a `#[Computed]` property so cards render correctly on initial load. |
| **F-04** | `TaxReport.php`, `download-report/tax-report.blade.php`, `print-report/tax-report.blade.php` | TaxReport | **High** | Support inclusive tax formula for expenses and use stored `Order` tax amounts for sales. |
| **F-05** | `BusinessInsights.php`, `sales-report.blade.php` | Sales & Insights | **Medium** | Fix overdue delivery date check to not falsely flag orders due today. |
| **F-06** | `app/Livewire/Reports/SalesReport.php` | SalesReport | **Medium** | Add pagination reset `$this->resetPage()` on filter updates. |
| **F-07** | `resources/views/livewire/reports/sales-report.blade.php` | SalesReport | **Medium** | Change `$item->customer_phone` to `$item->phone_number`. |
| **F-08** | `app/Livewire/Reports/ExpenseReport.php` | ExpenseReport | **Medium** | Change `join` to `leftJoin` for `expense_categories`. |
| **F-09** | `app/Livewire/Reports/LedgerReport.php` | LedgerReport | **Medium** | Use null-safe operator on `MasterSettings::first()?->siteData() ?? []`. |
| **F-10** | `app/Traits/CsvExportable.php` | CsvExportable | **Low** | Add UTF-8 BOM (`\xEF\xBB\xBF`) for multi-language Excel compatibility. |
| **F-11** | `resources/views/livewire/reports/*.blade.php` | Multiple Blade Views | **Low** | Fix unclosed `<p>` tags, typo in expense header, and `:trendUp` prop syntax. |
