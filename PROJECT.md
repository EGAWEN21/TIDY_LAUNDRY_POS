# Project: TidyPOS Report Section Audit and Auto-Fix

## Architecture
The TidyPOS Reports subsystem provides analytics, auditing, financial tracking, and export capabilities for point-of-sale operations.
It consists of:
- **Livewire Report Controllers** (`app/Livewire/Reports/`): Reactive server-side controllers managing filters, SQL/Eloquent aggregation queries, KPIs, data tables, and pagination.
- **Print & Download Controllers** (`app/Livewire/Reports/PrintReport/` and `app/Livewire/Reports/DownloadReport/`): Controllers generating print views and PDF downloads.
- **Blade Views** (`resources/views/livewire/reports/`): UI templates containing KPI metric cards (`x-dashboard-card`), ApexCharts, Alpine.js reactive bindings, data tables, and modal dialogs.
- **Export System** (`app/Traits/CsvExportable.php`): Streaming CSV generation with chunking and response streaming.

## Feature Inventory
| # | Feature / Area | Description | Milestone | Source |
|---|----------------|-------------|-----------|--------|
| 1 | Tax Report Math & Views | Align inclusive/exclusive tax math in `TaxReport.php`, PDF download, and print views with stored order tax fields. | M1 | Survey |
| 2 | Expense Report & Category Joins | Fix `leftJoin` on expense categories, badge visibility, and `:trendUp` bindings in `ExpenseReport.php` & blade. | M2 | Survey |
| 3 | Daily & Ledger Reports | Fix JS crash in `ledger-report.blade.php`, add null safety to `MasterSettings`, add CSV exports, verify daily reconciliations. | M3 | Survey |
| 4 | Sales & Customer Reports | Fix phone number column binding (`phone_number`), initial zero KPI cards in `CustomerReport`, pagination resets, and customer filter. | M4 | Survey |
| 5 | Business Insights & CSV Trait | Fix Livewire 3 commit hook, overdue comparison logic, `:trendUp` prop syntax, and add UTF-8 BOM to `CsvExportable.php`. | M5 | Survey |
| 6 | E2E Syntax & Compilation Gate | Automated PHP lint (`php -l`), Livewire blade compilation check, and cross-report metric integration verification. | M6 | Survey |
| 7 | Audit Report Generation | Compile and generate `comprehensive_report_audit.md` at workspace root documenting all audits, findings, and applied fixes. | M7 | Request |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| M1 | Tax Report Remediation | `app/Livewire/Reports/TaxReport.php`, `PrintReport/TaxReport.php`, `DownloadReport/TaxReport.php`, and tax blade views. | none | IN_PROGRESS (27e6771c) |
| M2 | Expense Report Remediation | `app/Livewire/Reports/ExpenseReport.php`, `PrintReport/ExpenseReport.php`, `DownloadReport/ExpenseReport.php`, and expense blade views. | none | IN_PROGRESS (ac8ee5ac) |
| M3 | Daily & Ledger Reports Remediation | `app/Livewire/Reports/DailyReport.php`, `LedgerReport.php`, `PrintReport/DailyReport.php`, and daily/ledger blade views. | none | IN_PROGRESS (8d021cd5) |
| M4 | Sales & Customer Reports Remediation | `app/Livewire/Reports/SalesReport.php`, `CustomerReport.php`, `PrintReport/SalesReport.php`, and sales/customer blade views. | none | IN_PROGRESS (c954bdc8) |
| M5 | Business Insights & CSV Export Remediation | `app/Livewire/Reports/BusinessInsights.php`, `business-insights.blade.php`, and `app/Traits/CsvExportable.php`. | none | IN_PROGRESS (fb2b8f4f) |
| M6 | E2E Testing, Syntax & Compilation Verification | Full PHP syntax linting (`php -l`), Livewire view compilation tests, and query verification. | M1, M2, M3, M4, M5 | PLANNED |
| M7 | Comprehensive Audit Report Synthesis | Create `comprehensive_report_audit.md` summarizing all reviewed files, discrepancies, and applied fixes. | M6 | PLANNED |

## Interface Contracts
### Report Controllers ↔ Blade Views
- KPI Cards: Pass associative array with numeric values formatted via `getFormattedCurrency()` or integer count.
- Badges: Use standard Tailwind classes (`text-primary-600 bg-primary-100 dark:bg-primary-900/30`, avoiding conflicting `text-white`).
- Charts: Output JSON-encoded series and labels to Alpine/ApexCharts components.
- Props: Pass boolean literals to blade components using `:trendUp="true"` or `:trendUp="false"`, avoiding string `'true'` / `'false'` or un-bound attribute evaluation.

### Print Controllers ↔ Print Views
- All `PrintReport` controllers must declare `public $lang;` and initialize from `Translation` in `mount()`.

## Code Layout
- Controllers: `app/Livewire/Reports/*.php`
- Print Controllers: `app/Livewire/Reports/PrintReport/*.php`
- Download Controllers: `app/Livewire/Reports/DownloadReport/*.php`
- Traits: `app/Traits/CsvExportable.php`
- Views: `resources/views/livewire/reports/*.blade.php`
- Print Views: `resources/views/livewire/reports/print-report/*.blade.php`
- Download Views: `resources/views/livewire/reports/download-report/*.blade.php`
- Audit Output: `comprehensive_report_audit.md`
