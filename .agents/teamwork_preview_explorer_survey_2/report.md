# Comprehensive Survey & Audit Report: Reports Blade Templates & UI

**Auditor**: Explorer Survey 2  
**Date**: 2026-08-24  
**Scope**: All Blade templates in `resources/views/livewire/reports/`, component views in `resources/views/components/`, and corresponding Livewire PHP components in `app/Livewire/Reports/`.

---

## 1. Executive Summary

This survey audited 20 Blade templates and 17 Livewire PHP classes across all 7 reporting modules in TidyPOS:
1. **Tax Report** (`tax-report.blade.php`, `download-report/tax-report.blade.php`, `print-report/tax-report.blade.php`)
2. **Expense Report** (`expense-report.blade.php`, `download-report/expense-report.blade.php`, `print-report/expense-report.blade.php`)
3. **Daily Report** (`daily-report.blade.php`, `download-report/daily-report.blade.php`, `print-report/daily-report.blade.php`)
4. **Ledger Report** (`ledger-report.blade.php`, `download-report/account-statement.blade.php`)
5. **Sales Report** (`sales-report.blade.php`, `download-report/sales-report.blade.php`, `print-report/sales-report.blade.php`)
6. **Customer Report** (`customer-report.blade.php`)
7. **Business Insights** (`business-insights.blade.php`)
8. **Shared Components & Partials** (`dashboard-card.blade.php`, `chart-container.blade.php`, `report-granularity.blade.php`, `empty-item.blade.php`, `layouts/print-layout.blade.php`, and legacy `order-report` views).

### Key Audit Highlights:
- **1 Critical Architectural Bug**: Customer Report renders 0 on all KPI cards on initial load due to out-of-order execution between computed properties and Blade rendering.
- **1 Critical JavaScript Crash**: Ledger Report receivables ageing chart crashes with `ReferenceError: getCurrency is not defined` due to missing Blade quotes/delimiters.
- **1 Missing Data Column Bug**: Sales Report fails to render customer phone numbers because the template references `$item->customer_phone` instead of `$item->phone_number`.
- **4 Systemic PHP 8 Fatal Error Bugs**: All 4 `PrintReport` Livewire components fail to initialize `$lang`, causing `Attempt to read property "data" on null` fatal errors when printing.
- **3 Blade Prop Syntax Errors**: Invalid syntax `:trendUp="{{ ... }}"` and missing colon `:trendUp` causing prop type coercion errors in `x-dashboard-card`.
- **6 Badge Styling Visibility Bugs**: Conflicting CSS classes (`text-primary-600` + `text-white`) rendering illegible white-on-pastel badges in Customer and Expense reports.
- **1 Calculation Discrepancy**: Print/Download Tax Reports recalculate tax from gross totals rather than using stored order tax columns, diverging from the main Livewire UI.

---

## 2. Complete Inventory of Blade Templates & Livewire Components

| # | Blade Template Path | Livewire PHP Class Path | Type / Function |
|---|---|---|---|
| 1 | `resources/views/livewire/reports/tax-report.blade.php` | `App\Livewire\Reports\TaxReport` | Interactive Tax Dashboard |
| 2 | `resources/views/livewire/reports/download-report/tax-report.blade.php` | `App\Livewire\Reports\DownloadReport\TaxReport` | DomPDF Tax Export View |
| 3 | `resources/views/livewire/reports/print-report/tax-report.blade.php` | `App\Livewire\Reports\PrintReport\TaxReport` | Browser Print Tax View |
| 4 | `resources/views/livewire/reports/expense-report.blade.php` | `App\Livewire\Reports\ExpenseReport` | Interactive Expense Dashboard |
| 5 | `resources/views/livewire/reports/download-report/expense-report.blade.php` | `App\Livewire\Reports\DownloadReport\ExpenseReport` | DomPDF Expense Export View |
| 6 | `resources/views/livewire/reports/print-report/expense-report.blade.php` | `App\Livewire\Reports\PrintReport\ExpenseReport` | Browser Print Expense View |
| 7 | `resources/views/livewire/reports/daily-report.blade.php` | `App\Livewire\Reports\DailyReport` | Interactive Daily Dashboard |
| 8 | `resources/views/livewire/reports/download-report/daily-report.blade.php` | `App\Livewire\Reports\DownloadReport\DailyReport` | DomPDF Daily Export View |
| 9 | `resources/views/livewire/reports/print-report/daily-report.blade.php` | `App\Livewire\Reports\PrintReport\DailyReport` | Browser Print Daily View |
| 10 | `resources/views/livewire/reports/ledger-report.blade.php` | `App\Livewire\Reports\LedgerReport` | Interactive Ledger & Ageing Dashboard |
| 11 | `resources/views/livewire/reports/download-report/account-statement.blade.php` | Called in `LedgerReport::downloadStatement` | DomPDF Account Statement View |
| 12 | `resources/views/livewire/reports/sales-report.blade.php` | `App\Livewire\Reports\SalesReport` | Interactive Financial & Ops Sales Dashboard |
| 13 | `resources/views/livewire/reports/download-report/sales-report.blade.php` | `App\Livewire\Reports\DownloadReport\SalesReport` | DomPDF Sales Export View |
| 14 | `resources/views/livewire/reports/print-report/sales-report.blade.php` | `App\Livewire\Reports\PrintReport\SalesReport` | Browser Print Sales View |
| 15 | `resources/views/livewire/reports/customer-report.blade.php` | `App\Livewire\Reports\CustomerReport` | Customer Lifecycle & LTV Dashboard |
| 16 | `resources/views/livewire/reports/business-insights.blade.php` | `App\Livewire\Reports\BusinessInsights` | Executive Business & Ops Insights |
| 17 | `resources/views/livewire/reports/download-report/order-report.blade.php` | None (Legacy) | Orphaned Legacy View |
| 18 | `resources/views/livewire/reports/print-report/order-report.blade.php` | None (Legacy) | Orphaned Legacy View |
| 19 | `resources/views/components/dashboard-card.blade.php` | Anonymous Blade Component | KPI Summary Card |
| 20 | `resources/views/components/chart-container.blade.php` | Anonymous Blade Component | Chart Wrapper Card |
| 21 | `resources/views/components/report-granularity.blade.php` | Anonymous Blade Component | Granularity Selector Dropdown |
| 22 | `resources/views/components/empty-item.blade.php` | Anonymous Blade Component | Empty State Placeholder |
| 23 | `resources/views/components/layouts/print-layout.blade.php` | Anonymous Blade Component | Minimal Print Layout wrapper |

---

## 3. Detailed Audit Findings by Report Module

### 3.1. Customer Report (`customer-report.blade.php` & `CustomerReport.php`)

#### Issue 1: Zero KPI Cards on Initial Render (Execution Order Side-Effect)
- **Location**: `resources/views/livewire/reports/customer-report.blade.php:6-36` & `app/Livewire/Reports/CustomerReport.php:17-24, 204`
- **Observation**:
  - `CustomerReport.php` initializes `public $kpiSummary = ['total_customers' => 0, 'active_count' => 0, 'at_risk_count' => 0, 'total_outstanding' => 0, 'avg_ltv' => 0];`
  - In `customer-report.blade.php`: Lines 6-36 render the 4 KPI cards using `$kpiSummary['total_customers']`, `$kpiSummary['at_risk_count']`, etc.
  - `$this->kpiSummary` is ONLY populated inside the computed method `customersData()` at line 204 (`$this->kpiSummary = $kpi;`).
  - In Blade, `$this->customersData` is not evaluated until line 99 (`@forelse($this->customersData as $row)`).
  - Because Blade renders top-down, lines 6-36 evaluate `$kpiSummary` before `customersData()` ever executes.
- **Impact**: On initial page load, all 4 KPI cards display 0 / $0.00.
- **Recommended Fix**: Ensure KPI calculation is performed during `mount()` / `render()` or accessed via a dedicated computed property before rendering cards, or call `$this->customersData` at the top of the Blade template / in component render.

#### Issue 2: Conflicting Badge CSS Classes (White Text on Light Background)
- **Location**: `resources/views/livewire/reports/customer-report.blade.php:121, 123, 125, 127, 129`
- **Observation**:
  - Line 121: `<span class="badge fw-semibold text-primary-600 bg-primary-100 px-20 py-9 radius-4 text-white">New</span>`
  - Line 123: `<span class="badge fw-semibold text-success-600 bg-success-100 px-20 py-9 radius-4 text-white">Active</span>`
  - Line 125: `<span class="badge fw-semibold text-warning-600 bg-warning-100 px-20 py-9 radius-4 text-white">Lapsing</span>`
  - Line 127: `<span class="badge fw-semibold text-orange-600 bg-orange-100 px-20 py-9 radius-4 text-white">Dormant</span>`
  - Line 129: `<span class="badge fw-semibold text-danger-600 bg-danger-100 px-20 py-9 radius-4 text-white">Lost</span>`
- **Impact**: `text-white` overrides the colored text, producing white text on light pastel backgrounds (e.g. white text on light yellow), rendering status labels unreadable.
- **Recommended Fix**: Remove `text-white` from all 5 badge `<span>` tags.

---

### 3.2. Sales Report (`sales-report.blade.php` & `SalesReport.php`)

#### Issue 1: Missing Customer Phone Number Binding
- **Location**: `resources/views/livewire/reports/sales-report.blade.php:117, 220`
- **Observation**:
  - Lines 117 & 220: `<span class="text-xs text-muted">{{ $item->customer_phone }}</span>`
  - In `App\Models\Order.php`, the database column is `phone_number`. `Order` model does not have a `customer_phone` property or accessor.
- **Impact**: Customer phone numbers are never rendered in either the Financial or Operations sales data tables (rendered as blank).
- **Recommended Fix**: Change `{{ $item->customer_phone }}` to `{{ $item->phone_number }}`.

#### Issue 2: Invalid Blade Prop Syntax on `:trendUp`
- **Location**: `resources/views/livewire/reports/sales-report.blade.php:48`
- **Observation**:
  - Line 48: `trendUp="{{ ($financialKpi['growth'] ?? 0) >= 0 ? true : false }}"`
  - Missing the `:` prefix. The attribute is passed as a string attribute `trendUp="1"` or `trendUp=""`.
- **Impact**: Passing a string attribute to `<x-dashboard-card>` causes boolean type coercion issues in PHP component rendering.
- **Recommended Fix**: Change to `:trendUp="($financialKpi['growth'] ?? 0) >= 0"`.

#### Issue 3: Unguarded Tooltip Formatter in ApexCharts
- **Location**: `resources/views/livewire/reports/sales-report.blade.php:288`
- **Observation**:
  - `tooltip: { y: { formatter: function(val) { return "{{ getCurrency() }}" + val.toFixed(2) } } }`
- **Impact**: If `val` is undefined or null, throws JavaScript TypeError `Cannot read property 'toFixed' of undefined`.
- **Recommended Fix**: Use `(typeof val === 'number' ? val.toFixed(2) : parseFloat(val || 0).toFixed(2))`.

---

### 3.3. Ledger Report (`ledger-report.blade.php` & `LedgerReport.php`)

#### Issue 1: JavaScript `ReferenceError: getCurrency is not defined` (Ageing Chart Crash)
- **Location**: `resources/views/livewire/reports/ledger-report.blade.php:193`
- **Observation**:
  - Line 193: `formatter: function (val) { return getCurrency() + val.toFixed(2); }`
  - `getCurrency()` was written directly without quotes or Blade interpolation `{{ }}`.
  - In contrast, line 200 has `{{ getCurrency() }}` and line 204 has `"{{ getCurrency() }}"`.
- **Impact**: The browser throws `ReferenceError: getCurrency is not defined` when rendering data labels on `#ageingChart`, crashing the chart.
- **Recommended Fix**: Change line 193 to:
  ```javascript
  formatter: function (val) { return "{{ getCurrency() }}" + (val || 0).toFixed(2); }
  ```

---

### 3.4. Expense Report (`expense-report.blade.php` & `ExpenseReport.php`)

#### Issue 1: Invalid Blade Prop Syntax on `:trendUp`
- **Location**: `resources/views/livewire/reports/expense-report.blade.php:64`
- **Observation**:
  - Line 64: `:trendUp="{{ ($kpi['net_cash'] ?? 0) >= 0 ? 'true' : 'false' }}"`
  - Mixing `:` and `{{ }}` syntax is invalid in Blade component props.
- **Impact**: May cause Blade compiler errors or evaluate incorrectly.
- **Recommended Fix**: Change to `:trendUp="($kpi['net_cash'] ?? 0) >= 0"`.

#### Issue 2: Conflicting Badge CSS Classes
- **Location**: `resources/views/livewire/reports/expense-report.blade.php:129`
- **Observation**:
  - `<span class="badge fw-semibold text-primary-600 bg-primary-100 px-20 py-9 radius-4 text-white">`
- **Impact**: `text-white` overrides `text-primary-600`, rendering white text on light blue badge.
- **Recommended Fix**: Remove `text-white`.

#### Issue 3: Unguarded ApexCharts Formatters
- **Location**: `resources/views/livewire/reports/expense-report.blade.php:165, 168, 202, 205`
- **Observation**:
  - `val.toFixed(2)` called directly without null check.
- **Recommended Fix**: Guard with `(typeof val === 'number' ? val.toFixed(2) : parseFloat(val || 0).toFixed(2))`.

---

### 3.5. Business Insights (`business-insights.blade.php` & `BusinessInsights.php`)

#### Issue 1: Invalid Blade Prop Syntax on `:trendUp`
- **Location**: `resources/views/livewire/reports/business-insights.blade.php:70`
- **Observation**:
  - Line 70: `:trendUp="{{ ($businessHealth['aov_trend_up'] ?? false) ? 'true' : 'false' }}"`
- **Impact**: Invalid Blade component expression.
- **Recommended Fix**: Change to `:trendUp="($businessHealth['aov_trend_up'] ?? false)"`.

#### Issue 2: Fragile Livewire 3 Hook for Chart Re-render
- **Location**: `resources/views/livewire/reports/business-insights.blade.php:174-182` & `app/Livewire/Reports/BusinessInsights.php`
- **Observation**:
  - Uses `Livewire.hook('commit', ...)` attempting to inspect `snapshot.data.monthlyRevenueTrend`. In Livewire 3, component state is stored in `snapshot.memo.data` or accessible via `$wire`.
  - In contrast, `DailyReport`, `ExpenseReport`, and `SalesReport` dispatch dedicated browser events (`$this->dispatch('update-...-charts')`).
- **Impact**: Revenue trajectory chart does not update when date filters change.
- **Recommended Fix**: Dispatch `update-insights-chart` from `BusinessInsights.php` on date change and listen via `Livewire.on('update-insights-chart', ...)`.

---

### 3.6. Daily Report (`daily-report.blade.php` & `DailyReport.php`)

#### Issue 1: Potential Null Pointer on `delivery_date`
- **Location**: `resources/views/livewire/reports/daily-report.blade.php:130`
- **Observation**:
  - `{{ \Carbon\Carbon::parse($overdue->delivery_date)->format('d/m/Y') }}`
  - If `delivery_date` is null, `Carbon::parse(null)` returns current date/time.
- **Recommended Fix**: Guard with `$overdue->delivery_date ? \Carbon\Carbon::parse($overdue->delivery_date)->format('d/m/Y') : '-'`.

---

### 3.7. Systemic Print Report Fatal Errors (`PrintReport/*`)

#### Issue 1: Missing `$lang` Property in All 4 Print Livewire Components (PHP 8 Fatal Error)
- **Affected Components**:
  1. `app/Livewire/Reports/PrintReport/TaxReport.php`
  2. `app/Livewire/Reports/PrintReport/ExpenseReport.php`
  3. `app/Livewire/Reports/PrintReport/DailyReport.php`
  4. `app/Livewire/Reports/PrintReport/SalesReport.php`
- **Affected Templates**:
  1. `resources/views/livewire/reports/print-report/tax-report.blade.php`
  2. `resources/views/livewire/reports/print-report/expense-report.blade.php`
  3. `resources/views/livewire/reports/print-report/daily-report.blade.php`
  4. `resources/views/livewire/reports/print-report/sales-report.blade.php`
- **Observation**:
  - In all 4 Print PHP components, `public $lang;` is not defined and not populated in `mount()`.
  - All 4 print blade templates access `{{ $lang->data['...'] ?? '...' }}` multiple times.
  - In PHP 8+, accessing property `->data` on `null` throws `Attempt to read property "data" on null` error.
- **Impact**: All print views for Tax, Expense, Daily, and Sales fail with fatal errors.
- **Recommended Fix**:
  1. Add `public $lang;` to all 4 PrintReport classes and initialize in `mount()`:
     ```php
     if (session()->has('selected_language')) {
         $this->lang = \App\Models\Translation::where('id', session()->get('selected_language'))->first();
     } else {
         $this->lang = \App\Models\Translation::where('default', 1)->first();
     }
     ```
  2. In print Blade views, use nullsafe operator `$lang?->data['...'] ?? '...'`.

#### Issue 2: Typo in Translation Fallback in Expense Print View
- **Location**: `resources/views/livewire/reports/print-report/expense-report.blade.php:47`
- **Observation**: `<th class="text-uppercase text-white text-xs ">{{$lang->data['date'] ?? 'End'}}</th>`
- **Impact**: Column header shows "End" instead of "Date" when translation is missing.
- **Recommended Fix**: Change fallback `'End'` to `'Date'`.

---

### 3.8. Tax Calculation Inconsistencies in Print & Download Views

- **Location**: `resources/views/livewire/reports/download-report/tax-report.blade.php:121, 150` & `resources/views/livewire/reports/print-report/tax-report.blade.php:92, 121`
- **Observation**:
  - Main UI (`TaxReport.php`) aggregates `$order->tax_amount` and `$order->taxable_amount`.
  - Print & Download views recalculate `$tax_amount_sales = $row->total * ($row->tax_percentage / 100);` and before tax as `$row->total - $tax_amount_sales`.
- **Impact**: Inconsistent tax totals between screen display and generated PDF/print output whenever orders have discounts or inclusive taxes.
- **Recommended Fix**: Standardize print/download views to use `$row->tax_amount` and `$row->taxable_amount > 0 ? $row->taxable_amount : ($row->total - $row->tax_amount)`.

---

## 4. Variable & Directives Mapping Matrix

| Blade View | Livewire Property / Method Accessed | Directives Verified | Status & Discrepancies |
|---|---|---|---|
| `tax-report.blade.php` | `$from_date`, `$to_date`, `$category`, `$salesTaxTotal`, `$expenseTaxTotal`, `$salesTaxableTotal`, `$salesTotal`, `$expenseTotal`, `$rateBands`, `$reportData`, `downloadFile()`, `downloadCsv()` | `wire:model.live`, `wire:click`, `@can('report_download')`, `@can('report_print')` | **OK** (All variables match component) |
| `print-report/tax-report.blade.php` | `$from_date`, `$to_date`, `$category`, `$reports`, `$lang` | Standalone view | **BUG**: `$lang` undefined in PHP class; tax calculation discrepancy |
| `download-report/tax-report.blade.php` | `$from_date`, `$to_date`, `$category` | Standalone view | **DISCREPANCY**: Tax calculation formula |
| `expense-report.blade.php` | `$from_date`, `$to_date`, `$kpi`, `$categoryBreakdown`, `$trendLabels`, `$trendData`, `$categoryFilter`, `$expenseCategories`, `$sortBy`, `$sortDirection`, `$this->expenses`, `downloadFile()`, `downloadCsv()`, `sortBy()` | `wire:model.live`, `wire:click`, `x-dashboard-card`, `x-chart-container` | **BUGS**: `:trendUp` syntax error on line 64, `text-white` badge clash line 129, ApexCharts unguarded formatters |
| `print-report/expense-report.blade.php` | `$from_date`, `$to_date`, `$expenses`, `$lang` | Standalone view | **BUG**: `$lang` undefined in PHP class; typo `'End'` on line 47 |
| `download-report/expense-report.blade.php` | `$from_date`, `$to_date` | Standalone view | **NOTE**: N+1 query on `expenseCategory` |
| `daily-report.blade.php` | `$today`, `$new_order`, `$delivered_orders`, `$itemVolume`, `$pending_orders`, `$total_sales`, `$cash_collected`, `$total_expense`, `$trendData`, `$trendLabels`, `$paymentSplit`, `$unpaidDeliveries`, `$overdueOrders`, `downloadFile()` | `wire:model.live`, `wire:click`, `x-dashboard-card`, `x-chart-container` | **OK**: Formatters need null guard, `delivery_date` null guard |
| `print-report/daily-report.blade.php` | `$today`, `$new_order`, `$delivered_orders`, `$total_sales`, `$total_payment`, `$total_expense`, `$lang` | Standalone view | **BUG**: `$lang` undefined in PHP class |
| `download-report/daily-report.blade.php` | `$today` | Standalone view | **OK** |
| `ledger-report.blade.php` | `$totalOutstanding`, `$ageingData`, `$this->topDebtors`, `$customer_query`, `$customers`, `$selected_customer`, `$start_date`, `$end_date`, `$this->data`, `$this->firstData`, `selectCustomer()`, `downloadStatement()` | `wire:model.live`, `wire:click`, `x-chart-container` | **CRITICAL BUG**: `getCurrency()` unquoted JS crash in line 193 |
| `download-report/account-statement.blade.php` | `$master_settings`, `$transactions`, `$firstData`, `$customer`, `$start_date`, `$end_date`, `$lang` | Standalone view | **OK** |
| `sales-report.blade.php` | `$from_date`, `$to_date`, `$activeTab`, `$financialKpi`, `$operationalKpi`, `$status`, `$this->orders`, `$serviceBreakdown`, `$trendLabels`, `$trendData`, `$pipelineData`, `downloadFile()`, `downloadCsv()` | `wire:model.live`, `wire:click`, `x-dashboard-card`, `x-chart-container` | **BUGS**: `:trendUp` missing colon line 48, `$item->customer_phone` missing column lines 117 & 220 |
| `print-report/sales-report.blade.php` | `$from_date`, `$to_date`, `$orders`, `$lang` | Standalone view | **BUG**: `$lang` undefined in PHP class |
| `download-report/sales-report.blade.php` | `$from_date`, `$to_date` | Standalone view | **OK** |
| `customer-report.blade.php` | `$statusFilter`, `$kpiSummary`, `$this->customersData`, `$this->acquisitionTrend`, `downloadCsv` | `wire:model.live`, `wire:click`, `x-dashboard-card` | **CRITICAL BUG**: Zero KPI cards on initial load; bad badge classes |
| `business-insights.blade.php` | `$from_date`, `$to_date`, `$operationsHealth`, `$businessHealth`, `$staffPerformance`, `$monthlyRevenueTrend` | `wire:model.live`, `x-dashboard-card`, `x-chart-container` | **BUGS**: `:trendUp` syntax line 70; fragile `Livewire.hook('commit')` chart update |

---

## 5. Prioritized Action Items for Workers / Implementation Agents

1. **P0 (Critical Bugs & Crashes)**:
   - Fix unquoted `getCurrency()` in `ledger-report.blade.php:193`.
   - Fix zero KPI card render in `customer-report.blade.php` / `CustomerReport.php`.
   - Add `$lang` initialization to all 4 `PrintReport` PHP classes (`TaxReport.php`, `ExpenseReport.php`, `DailyReport.php`, `SalesReport.php`) and nullsafe checks in print Blade views.
2. **P1 (Data & Binding Fixes)**:
   - Replace `$item->customer_phone` with `$item->phone_number` in `sales-report.blade.php:117, 220`.
   - Fix `:trendUp` prop bindings in `sales-report.blade.php:48`, `expense-report.blade.php:64`, and `business-insights.blade.php:70`.
   - Replace fragile `Livewire.hook('commit')` with event dispatching for chart updates in `BusinessInsights.php` & `business-insights.blade.php`.
   - Align tax calculation formulas in print/download tax reports with `TaxReport.php`.
3. **P2 (UI Polish & Defensiveness)**:
   - Remove `text-white` from status badges in `customer-report.blade.php` and `expense-report.blade.php`.
   - Add null guards `(typeof val === 'number' ? val.toFixed(2) : parseFloat(val || 0).toFixed(2))` to all ApexCharts formatters.
   - Fix typo `'End'` -> `'Date'` in `print-report/expense-report.blade.php:47`.
