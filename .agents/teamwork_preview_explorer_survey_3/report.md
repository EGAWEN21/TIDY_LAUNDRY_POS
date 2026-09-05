# Comprehensive Mathematical, Financial, Query & CSV Export Audit Report

**Survey Target**: TidyPOS Report Section (`app/Livewire/Reports/`, `app/Traits/CsvExportable.php`, and associated Blade views)  
**Auditor**: Explorer Survey 3  
**Date**: 2026-08-24  
**Status**: Comprehensive Read-Only Audit Complete  

---

## 1. Executive Summary

This audit conducted an exhaustive mathematical, accounting, database query, and export mechanism analysis across all seven reporting modules of TidyPOS:
1. **Tax Report** (`TaxReport.php`, PDF download, Print, Web view)
2. **Expense Report** (`ExpenseReport.php`, PDF download, Print, Web view)
3. **Daily Report** (`DailyReport.php`, PDF download, Print, Web view)
4. **Ledger Report** (`LedgerReport.php`, Account Statement PDF, Web view)
5. **Sales Report** (`SalesReport.php`, PDF download, Print, Web view)
6. **Customer Report** (`CustomerReport.php`, Web view, CSV export)
7. **Business Insights** (`BusinessInsights.php`, Web view)
8. **CSV Export Trait & Strategy** (`app/Traits/CsvExportable.php`)

### Key Findings Summary
- **Critical Tax Calculation Discrepancy**: While the livewire component and CSV export use stored tax values (`tax_amount`, `taxable_amount`), the PDF download (`download-report/tax-report.blade.php`) and print views (`print-report/tax-report.blade.php`) recompute sales tax as `total * (tax_percentage / 100)`. For tax-exclusive transactions, this creates an over-calculation of tax by the tax percentage itself (e.g. 10% tax on a $110 total shows $11.00 instead of $10.00).
- **Expense Tax Accounting Contradiction**: In `TaxReport.php` and its print/download views, expense tax is calculated as `expense_amount * (tax_percentage / 100)` and before-tax expense is computed as `expense_amount - tax`. This is mathematically invalid (mixes inclusive subtraction with exclusive multiplication).
- **Database Query Join Flaws (Uncategorized Expense Dropping)**: `ExpenseReport.php` uses `INNER JOIN` with `expense_categories` in category breakdown and sorting queries, causing any expense with `expense_category_id = NULL` to be dropped from category breakdowns and sorting lists.
- **Customer Lifecycle & Metric Distortion**: In `CustomerReport.php`, the query specifies `->having('total_orders', '>', 0)`, which completely excludes registered customers with 0 orders from the total customer count, new customer classification, and CSV exports.
- **Blade UI Field Mismatch**: In `sales-report.blade.php`, `$item->customer_phone` is referenced instead of the actual database column `$item->phone_number`, causing phone numbers to display blank in both financial and operational tables.
- **Dialect-Specific SQL in Portable Laravel**: Multiple reports use MySQL-specific functions (`DATEDIFF()`, `MONTH()`, `YEAR()`) in raw SQL strings, which break if running on SQLite or PostgreSQL.
- **CSV Export Formatting & Excel Encoding**: `CsvExportable.php` lacks the UTF-8 Byte Order Mark (`\xEF\xBB\xBF`), causing accented characters and non-USD currency symbols to render corrupted in Microsoft Excel. Furthermore, Daily Report, Ledger Report, and Business Insights lack CSV export capabilities entirely.

---

## 2. In-Depth Mathematical & Accounting Formula Audit

### 2.1 Tax Report Formulas & Logic

#### A. Sales Tax Computation
- **Model / Action Engine (`CalculateCartTotals.php`)**:
  - **Exclusive Tax (`tax_type = 1`)**:
    $$\text{Tax Amount} = (\text{Subtotal} + \text{Addons}) \times \frac{\text{Tax Rate}}{100}$$
    $$\text{Taxable Amount} = \text{Subtotal} + \text{Addons}$$
    $$\text{Total} = (\text{Subtotal} + \text{Addons} + \text{Tax Amount}) - \text{Discount}$$
  - **Inclusive Tax (`tax_type = 2`)**:
    $$\text{Taxable Amount} = (\text{Subtotal} + \text{Addons}) \times \frac{100}{100 + \text{Tax Rate}}$$
    $$\text{Tax Amount} = (\text{Subtotal} + \text{Addons}) - \text{Taxable Amount}$$
    $$\text{Total} = (\text{Subtotal} + \text{Addons}) - \text{Discount}$$
- **TaxReport Component (`TaxReport.php:74-77`)**:
  - Total Tax Collected: $\sum \text{order.tax\_amount}$
  - Taxable Amount: $\text{taxable\_amount} > 0 \ ?\ \text{taxable\_amount} : (\text{total} - \text{tax\_amount})$
  - Net Tax Due: $\text{salesTaxTotal} - \text{expenseTaxTotal}$
- **Rate Bands Grouping (`TaxReport.php:80-93`)**:
  - Groups orders by `order.tax_percentage` and aggregates `count`, `taxable`, `tax`, and `total`.
- **DISCREPANCY (PDF & Print Views)**:
  In `download-report/tax-report.blade.php:121` and `print-report/tax-report.blade.php:92`:
  $$\text{Tax Amount Sales (PDF/Print)} = \text{order.total} \times \frac{\text{order.tax\_percentage}}{100} \quad \text{[INCORRECT]}$$
  $$\text{Before Tax (PDF/Print)} = \text{order.total} - \text{Tax Amount Sales} \quad \text{[INCORRECT]}$$
  *Impact*: If an order has Subtotal \$100, Tax 10% (\$10), Total \$110:
  - Component/CSV reports: Taxable = \$100, Tax = \$10, Total = \$110.
  - PDF/Print reports: Taxable = \$99, Tax = \$11, Total = \$110.
  *Fix Required*: Align PDF and Print views with stored `tax_amount` and `taxable_amount`.

#### B. Expense Tax Computation
- **In `TaxReport.php:116-121`, `download-report/tax-report.blade.php:128`, `print-report/tax-report.blade.php:99`**:
  ```php
  $taxAmount = $expense->expense_amount * ($expense->tax_percentage / 100);
  $expense->computed_tax_amount = $taxAmount;
  $expense->computed_before_tax = $expense->expense_amount - $taxAmount;
  ```
  *Mathematical Failure*:
  If `expense_amount` is Gross (\$100) with 20% tax:
  $$\text{Computed Tax} = 100 \times 0.20 = \$20$$
  $$\text{Computed Before Tax} = 100 - 20 = \$80$$
  Verification: $80 \times 1.20 = \$96 \neq \$100$. (A 4% mathematical leak).
  *Proper Accounting Formula*:
  - If `expense_amount` is Inclusive (Gross):
    $$\text{Before Tax} = \frac{\text{expense\_amount}}{1 + \frac{\text{tax\_percentage}}{100}}$$
    $$\text{Tax Amount} = \text{expense\_amount} - \text{Before Tax}$$
  - If `expense_amount` is Exclusive (Net):
    $$\text{Tax Amount} = \text{expense\_amount} \times \frac{\text{tax\_percentage}}{100}$$
    $$\text{Gross Total} = \text{expense\_amount} + \text{Tax Amount}$$

---

### 2.2 Expense Report Formulas & Logic

- **Financial KPIs (`ExpenseReport.php:73-91`)**:
  - `totalExpenses`: $\sum \text{expenses.expense\_amount}$ where `expense_date` $\in [\text{from}, \text{to}]$.
  - `totalIncome`: $\sum \text{payments.received\_amount}$ where `payment_date` $\in [\text{from}, \text{to}]$.
  - `revenueBilled`: $\sum \text{orders.total}$ where `order_date` $\in [\text{from}, \text{to}]$ and `status = 3` (Delivered).
  - `net_cash`: $\text{totalIncome} - \text{totalExpenses}$.
- **Category Breakdown Period Comparison (`ExpenseReport.php:93-122`)**:
  - Period Diff: $\text{diffInDays} = \text{from\_date} \rightarrow \text{to\_date}$.
  - Previous Window: $[\text{from\_date} - (\text{diffInDays} + 1), \text{from\_date} - 1]$.
  - Aggregates by category name and compares current vs previous spend.
- **6-Month Rolling Trend (`ExpenseReport.php:132-160`)**:
  - Monthly aggregation from $(T_{\text{end}} - 5\text{ months})$ to $T_{\text{end}}$.
  - Accurately tracks Cash Collected vs Cash Spent per month.

---

### 2.3 Daily Report Formulas & Logic

- **Operational KPIs (`DailyReport.php:65-73`)**:
  - `new_order`: Count of orders where `DATE(order_date) = today`.
  - `delivered_orders`: Count of orders where `DATE(order_date) = today` and `status = 3`.
  - `itemVolume`: $\sum \text{order\_details.service\_quantity}$ for orders placed today.
  - `pending_orders`: Count of all active backlog orders (`status IN (0, 1, 2)` and `deleted_at IS NULL`).
- **Cash & Revenue Metrics**:
  - `total_sales`: $\sum \text{orders.total}$ where `DATE(order_date) = today` and `status = 3`.
  - `cash_collected` / `total_payment`: $\sum \text{payments.received\_amount}$ where `DATE(payment_date) = today`.
  - `total_expense`: $\sum \text{expenses.expense\_amount}$ where `DATE(expense_date) = today`.
  - Collection Gap: $\text{total\_sales} - \text{cash\_collected}$.
- **Delivered & Unpaid Today (`DailyReport.php:74-82`)**:
  $$\text{amount\_owed} = \text{orders.total} - \text{COALESCE}(\sum \text{payments.received\_amount}, 0)$$
  Filtered by `DATE(orders.order_date) = today` and `status = 3` and `amount_owed > 0`.
  *Note on Scope*: Filters on `order_date = today`. If an order was placed on a prior day and delivered today, it is not included in this list.
- **Overdue Deliveries (`DailyReport.php:84-91`)**:
  $$\text{days\_overdue} = \text{DATEDIFF}(\text{CURRENT\_DATE}, \text{orders.delivery\_date})$$
  Where `orders.delivery_date < today` and `orders.status NOT IN (3, 4)`.

---

### 2.4 Ledger Report & Double-Entry Accounting Logic

- **Receivables Ageing Analysis (`LedgerReport.php:50-92`)**:
  - Filters active orders (`status != 4`, `deleted_at IS NULL`) with balance remaining:
    $$\text{Balance} = \text{order.total} - \text{COALESCE}(\text{order.total\_paid}, 0) > 0$$
  - Age buckets based on `Carbon::parse(order_date)->diffInDays(today)`:
    - **0–30 Days (Current)**
    - **31–60 Days (Aging)**
    - **61–90 Days (Late)**
    - **90+ Days (Critical / Default Risk)**
  - Total Outstanding: $\sum \text{Ageing Buckets}$.
- **Customer Statement & Sub-Ledger (`LedgerReport.php:184-217`)**:
  - **Opening Balance (`firstData`)**:
    $$\text{Opening Balance} = \sum_{\text{order\_date} < \text{start\_date}} \text{Debit (Orders)} - \sum_{\text{payment\_date} < \text{start\_date}} \text{Credit (Payments)}$$
  - **Period Transactions (`data`)**:
    - Orders are debited as positive ledger entries: $+\text{total}$.
    - Payments are credited as negative ledger entries: $-\text{received\_amount}$.
  - **Running Balance Equation**:
    $$\text{Running Balance}_i = \text{Running Balance}_{i-1} + \text{Debit}_i - \text{Credit}_i$$
  - **Account Statement View (`account-statement.blade.php:64-100`)**:
    - Accurately renders $(Dr)$ for debit balances (customer owes money) and $(Cr)$ for credit balances (customer overpaid/credit on file).
    - Amount Due is properly clamped: $\text{Amount Due} = \max(0, \text{Closing Balance})$.

---

### 2.5 Sales Report Formulas & Logic

- **Financial Tab Metrics (`SalesReport.php:64-108`)**:
  - `revenue_billed`: $\sum \text{orders.total}$ for delivered orders (`status = 3`).
  - `discount`: $\sum \text{orders.discount}$ for delivered orders.
  - `completedOrders`: $\text{COUNT}(*)$ of delivered orders.
  - **Average Order Value (AOV)**:
    $$\text{AOV} = \frac{\text{revenue\_billed}}{\text{completedOrders}}$$
  - **Discount Rate %**:
    $$\text{Discount Rate} = \frac{\text{discount}}{\text{revenue\_billed} + \text{discount}} \times 100$$
  - **Collection Rate %**:
    $$\text{Collection Rate} = \frac{\text{cash\_collected}}{\text{revenue\_billed}} \times 100$$
  - **Period-over-Period Sales Growth**:
    $$\text{Growth \%} = \frac{\text{revenue\_billed} - \text{prevSales}}{\text{prevSales}} \times 100$$
- **Operations Tab Metrics (`SalesReport.php:111-136`)**:
  - `total_orders`: Total orders created in date range.
  - **Average Turnaround Time (TAT)**:
    $$\text{Avg TAT} = \text{AVG}(\text{DATEDIFF}(\text{orders.updated\_at}, \text{orders.order\_date}))$$
  - **On-Time Delivery Rate %**:
    $$\text{On-Time \%} = \frac{\text{Delivered Orders where } \text{DATE}(\text{updated\_at}) \le \text{delivery\_date}}{\text{Total Delivered Orders}} \times 100$$
  - **Return Rate %**:
    $$\text{Return Rate} = \frac{\text{Returned Orders (status 4)}}{\text{total\_orders}} \times 100$$
  - **Overdue Count**: Orders where `delivery_date < today` and `status NOT IN (3, 4)`.
- **Service Breakdown**:
  $$\text{Service Revenue} = \sum \text{order\_details.service\_detail\_total}$$
  Grouped by `order_details.service_name` for delivered orders.

---

### 2.6 Customer Report Metrics & Customer Lifetime Value (LTV)

- **Customer Aggregations (`CustomerReport.php:95-115`)**:
  - `total_orders`: $\text{COUNT}(\text{orders.id})$ (excluding status 4).
  - `total_spend`: $\sum \text{orders.total}$ (excluding status 4).
  - `spend_30`: $\sum \text{orders.total}$ for orders placed in the last 30 days.
  - `spend_7`: $\sum \text{orders.total}$ for orders placed in the last 7 days.
  - `outstanding`: $\max(0, \text{total\_spend} - \text{total\_paid})$.
  - `aov`: $\frac{\text{total\_spend}}{\text{total\_orders}}$.
- **Average Customer Lifetime Value (LTV)**:
  $$\text{Avg LTV} = \frac{\text{total\_lifetime\_spend}}{\text{total\_customers}}$$
- **Customer Lifecycle Stage Taxonomy**:
  1. **New (Status 1)**: Customer `registration_date >= 30 days ago` OR `first_visit >= 30 days ago`.
  2. **Active (Status 2)**: Days since last visit $\le 21$ days.
  3. **Lapsing (Status 3)**: Days since last visit between $22$ and $40$ days.
  4. **Dormant (Status 4)**: Days since last visit between $41$ and $60$ days.
  5. **Lost (Status 5)**: Days since last visit $> 60$ days.
- **At-Risk Metric**: $\text{at\_risk\_count} = \text{Lapsing Count} + \text{Dormant Count}$.

---

### 2.7 Business Insights Metrics

- **Operations Health**:
  - `avg_tat`: $\frac{\sum \text{TAT Days}}{\text{Delivered Orders Count}}$.
  - `on_time_pct`: $\frac{\text{On-Time Delivered Count}}{\text{Delivered Orders Count}} \times 100$.
  - `return_rate`: $\frac{\text{Returned Orders Count}}{\text{Total Orders Count}} \times 100$.
  - `overdue_count`: Orders where `delivery_date` is past and `status NOT IN (3, 4)`.
- **Business Health**:
  - `collection_rate`: $\frac{\text{totalPayments}}{\text{totalSales}} \times 100$.
  - `aov`: $\frac{\text{totalSales}}{\text{salesCount}}$.
  - `net_cash_position`: $\text{totalPayments} - \text{totalExpenses}$.
  - `new_customers_count`: $\text{COUNT}(\text{customers created between from\_date and to\_date})$.
- **Staff Performance Leaderboard**:
  - `total_revenue`: $\sum \text{orders.total}$ for status 3 created by staff member.
  - `aov_per_staff`: $\frac{\text{total\_revenue}}{\text{delivered orders by staff}}$.
  - `return_rate_per_staff`: $\frac{\text{returned orders by staff}}{\text{total orders by staff}} \times 100$.
  - `trend_up`: $\text{total\_revenue} \ge \text{prevTotalRevenue}$.

---

## 3. SQL & Eloquent Query Audit

| Query Location | Table(s) Involved | Issue / Finding | Severity | Recommended Fix |
|---|---|---|---|---|
| `ExpenseReport.php:97-111` | `expenses`, `expense_categories` | `INNER JOIN` drops uncategorized expenses (`expense_category_id = NULL`) from category breakdown | Medium | Change `join('expense_categories', ...)` to `leftJoin('expense_categories', ...)` and default null category name to 'Uncategorized'. |
| `ExpenseReport.php:210` | `expenses`, `expense_categories` | `INNER JOIN` on sort by category drops uncategorized expenses from table | Medium | Change to `leftJoin('expense_categories', ...)`. |
| `CustomerReport.php:113` | `customers`, `orders` | `->having('total_orders', '>', 0)` excludes all newly registered customers with 0 orders | High | Remove `->having('total_orders', '>', 0)` or move to optional filter so new 0-order customers are counted in Total Customers & New lifecycle stage. |
| `DailyReport.php:87`, `SalesReport.php:117` | `orders` | Uses raw MySQL function `DATEDIFF()` | Low-Medium | In portable migrations/queries, use Carbon date diff in PHP or cross-platform SQL date diff expressions. |
| `CustomerReport.php:62` | `customers` | Uses raw MySQL `MONTH(created_at)` and `YEAR(created_at)` | Low-Medium | Use portable grouping or Carbon date extraction in PHP collection mapping. |
| `DailyReport.php:107-110` | `order_details`, `orders` | Missing `whereNull('orders.deleted_at')` and `whereNull('order_details.deleted_at')` | Low | Add soft delete checks to item volume aggregation query. |
| `SalesReport.php:210`, `254` | `orders`, `payments` | Uses `withSum('payments as paid', 'received_amount')` | Clean | Correct Eloquent aggregation query. |
| `LedgerReport.php:195-204` | `orders`, `payments` | UNION ALL raw query correctly filters `deleted_at IS NULL` and `status != 4` | Clean | Valid query for debit/credit ledger aggregation. |

---

## 4. CSV Export Implementation & Architecture Audit

### 4.1 Trait Analysis (`app/Traits/CsvExportable.php`)

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

### 4.2 Deficiencies & Opportunities

1. **UTF-8 Byte Order Mark (BOM) Missing**:
   - `fputs($file, "\xEF\xBB\xBF");` is omitted before writing headers.
   - Without the BOM, Microsoft Excel default-opens the CSV in ANSI/Windows-1252 mode, corrupting non-ASCII currency signs (e.g. `₹`, `€`, `£`, `¥`) and international characters in customer names.
2. **In-Memory Buffering**:
   - The method signature requires `array $rows` to be pre-built in memory.
   - For components like `SalesReport` or `TaxReport` spanning multi-year date ranges with tens of thousands of rows, hydrating all Eloquent models and mapping them into an array before streaming consumes significant RAM.
   - Recommendation: Support an `iterable` (such as a `Generator` or `LazyCollection` with `cursor()`).
3. **Report Feature Matrix**:

| Report Module | Has PDF Download? | Has Print View? | Has CSV Export? | Notes |
|---|---|---|---|---|
| **Tax Report** | Yes (`downloadFile`) | Yes (`print-report/tax`) | Yes (`downloadCsv`) | Separate Sales vs Expense CSV schemas |
| **Expense Report** | Yes (`downloadFile`) | Yes (`print-report/expense`) | Yes (`downloadCsv`) | Date, Category, Amount, Note |
| **Daily Report** | Yes (`downloadFile`) | Yes (`print-report/daily`) | **No** (Missing) | Needs CSV export for daily settlement breakdown |
| **Ledger Report** | Yes (`downloadStatement`) | Yes (`print`) | **No** (Missing) | Needs CSV export for customer statement & debtor ledger |
| **Sales Report** | Yes (`downloadFile`) | Yes (`print`) | Yes (`downloadCsv`) | Respects active tab (financial vs operational) |
| **Customer Report**| No | Yes (`print`) | Yes (`downloadCsv`) | Full customer metric export |
| **Business Insights**| No | Yes (`print`) | **No** (Missing) | Needs CSV export for monthly trajectory & staff leaderboard |

---

## 5. UI Rendering & Blade Discrepancy Matrix

| Template Path | Line(s) | Element | Discrepancy Description |
|---|---|---|---|
| `resources/views/livewire/reports/sales-report.blade.php` | 117, 220 | Customer Phone | Renders `$item->customer_phone` which does not exist on `Order`. Database column is `phone_number`. Phone displays blank. |
| `resources/views/livewire/reports/download-report/tax-report.blade.php` | 120–123, 150, 161 | Sales Tax Amount & Before Tax | Recomputes tax as `total * rate%` instead of using stored `tax_amount` and `taxable_amount`. Overcharges tax on exclusive orders. |
| `resources/views/livewire/reports/print-report/tax-report.blade.php` | 91–94, 121, 132 | Sales Tax Amount & Before Tax | Recomputes tax as `total * rate%` instead of using stored `tax_amount` and `taxable_amount`. |
| `resources/views/livewire/reports/download-report/tax-report.blade.php` | 127–129 | Expense Tax Amount | Multiplies `expense_amount * rate%` and subtracts from `expense_amount`, resulting in mathematical inconsistency. |
| `resources/views/livewire/reports/print-report/tax-report.blade.php` | 98–100 | Expense Tax Amount | Multiplies `expense_amount * rate%` and subtracts from `expense_amount`. |
| `resources/views/livewire/reports/download-report/sales-report.blade.php` | 77 | Gross Total Header | Table header displays "Gross Total" for column `$row->total` (which is actually Net Billed Total after discounts/tax). |
| `resources/views/livewire/reports/print-report/sales-report.blade.php` | 54 | Gross Total Header | Table header displays "Gross Total" for column `$row->total`. |
| `resources/views/livewire/reports/business-insights.blade.php` | 82 | New Customers KPI | Card displays count of new customers registered in period, correctly linked to `businessHealth['new_customers_count']`. |

---

## 6. Actionable Recommendations for Implementation Phase

1. **Fix Tax Report Calculation in PDF & Print Views**:
   - Modify `resources/views/livewire/reports/download-report/tax-report.blade.php` and `resources/views/livewire/reports/print-report/tax-report.blade.php` to use the stored `$row->tax_amount` and `$row->taxable_amount` (or `$row->total - $row->tax_amount`) rather than recalculating `$row->total * ($row->tax_percentage / 100)`.
2. **Correct Expense Tax Formula**:
   - In `TaxReport.php`, `download-report/tax-report.blade.php`, and `print-report/tax-report.blade.php`, clarify whether `expense_amount` is inclusive or exclusive. If inclusive:
     `$taxAmount = $row->expense_amount - ($row->expense_amount / (1 + ($row->tax_percentage / 100)));`
     `$beforeTax = $row->expense_amount - $taxAmount;`
3. **Fix Phone Field in Sales Report Blade**:
   - In `resources/views/livewire/reports/sales-report.blade.php` lines 117 and 220, change `$item->customer_phone` to `$item->phone_number`.
4. **Fix Uncategorized Expenses in Expense Report**:
   - In `app/Livewire/Reports/ExpenseReport.php`, replace `join('expense_categories', ...)` with `leftJoin('expense_categories', ...)` in both `report()` and `expenses()` methods to ensure uncategorized expenses are preserved.
5. **Fix Customer Report Filtering**:
   - In `app/Livewire/Reports/CustomerReport.php`, remove `->having('total_orders', '>', 0)` so registered customers with 0 orders are included in the customer count, lifecycle stages (as "New"), and CSV export.
6. **Enhance `CsvExportable.php` with UTF-8 BOM**:
   - Add `fputs($file, "\xEF\xBB\xBF");` immediately after opening `php://output` in `exportCsv()`.
7. **Add CSV Export to Remaining Reports**:
   - Add `downloadCsv()` methods to `DailyReport.php`, `LedgerReport.php`, and `BusinessInsights.php`.
