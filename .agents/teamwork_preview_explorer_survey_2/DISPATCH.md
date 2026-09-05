## 2026-08-24T11:35:28Z

Investigate and map the Ledger, Sales, and Customer Reports in TidyPOS:
- `app/Livewire/Reports/LedgerReport.php`
- `app/Livewire/Reports/SalesReport.php`
- `app/Livewire/Reports/CustomerReport.php`
- `resources/views/livewire/reports/ledger-report.blade.php`
- `resources/views/livewire/reports/sales-report.blade.php`
- `resources/views/livewire/reports/customer-report.blade.php`
- Associated models (Customer, Order, Payment, Ledger, Transaction, User, Branch, etc.), migrations, and database relationships.

Analyze:
1. Mathematical accuracy of ledger debit/credit balances, running balances, sales summaries, discount calculations, payment status aggregations, customer lifetime value, debt/receivables, and top customer metrics.
2. SQL / Eloquent query logic, date filters, customer filtering, branch filtering, pagination handling, eager loading, and aggregation queries.
3. Livewire component lifecycle, public properties, wire:model bindings, search inputs, pagination reset logic, sorting, and CsvExportable trait integration.
4. Blade template markup, table columns, debit/credit color indicators, customer transaction history modals/tables, KPI cards, charts, currency formatters, and UI bindings.
5. Identify any existing bugs, inconsistencies, edge cases, balance miscalculations, null reference risks, or compilation/rendering flaws.

Deliverables:
Write a comprehensive survey report to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_explorer_survey_2\survey_report.md` detailing all architectural findings, query structures, calculation formulas, and potential bugs/discrepancies.
Also write `handoff.md` and `progress.md` in your working directory. Send a message to parent when done.
