# Changes Report — Worker M3 (Daily & Ledger Reports Remediation)

## Summary of Changes

### 1. `resources/views/livewire/reports/ledger-report.blade.php`
- **Fix Critical JavaScript Crash**: Replaced unquoted `formatter: function (val) { return getCurrency() + val.toFixed(2); }` on line 193 with `formatter: function (val) { return "{{ getCurrency() }}" + val.toFixed(2); }`. In the original template, `getCurrency()` was evaluated as an undefined JavaScript function in the browser, causing a `ReferenceError: getCurrency is not defined` crash when rendering ApexCharts. Quoting it correctly interpolates the PHP Blade helper string.
- **CSV Export Button**: Added the "Download CSV" action button (`wire:click="downloadCsv()"`) next to "Download Statement" in the ledger data table toolbar.

### 2. `app/Livewire/Reports/LedgerReport.php`
- **Safe Null Access for MasterSettings**: In `downloadStatement()`, changed `$master_settings = MasterSettings::first()->siteData();` to `$master_settings = MasterSettings::first()?->siteData() ?? [];`. This prevents fatal runtime `Error: Call to a member function siteData() on null` when `MasterSettings` table has no records or during unseeded/test environments.
- **CSV Export Implementation**: Implemented `downloadCsv()` using `\App\Traits\CsvExportable`. When a customer is selected, it streams a structured CSV ledger statement (`Date`, `Type`, `Reference`, `Debit`, `Credit`, `Running Balance`) with opening and closing balances. When no customer is selected, it exports the Top 10 Debtors list (`Customer`, `Phone`, `Total Owed`, `Last Payment Date`, `Days Outstanding`).

### 3. `app/Livewire/Reports/PrintReport/DailyReport.php`
- **Missing `$lang` Declaration & Initialization**: Declared `public $lang;` property and initialized `$this->lang = Translation::where('id', session()->get('selected_language'))->first() ?? Translation::where('default', 1)->first() ?? Translation::where('id', 1)->first();` in `mount()`. This fixes undefined variable/property warnings and ensures proper localization strings in the print template.

### 4. `app/Livewire/Reports/DailyReport.php`
- **CSV Export Implementation**: Implemented `downloadCsv()` using `\App\Traits\CsvExportable`. Exports daily business KPIs and operational metrics (`Report Date`, `Orders Received`, `Orders Delivered`, `Items Processed`, `Pending Now`, `Total Sales`, `Cash Collected`, `Collection Gap`, `Total Expense`) to a CSV file.

### 5. `resources/views/livewire/reports/daily-report.blade.php`
- **CSV Export Button**: Added the "Download CSV" button (`wire:click="downloadCsv()"`) under the `@can('report_download')` gate alongside the existing PDF download button.

## Verification
- Ran PHP lint (`php -l`) on all modified files:
  - `app/Livewire/Reports/DailyReport.php` -> No syntax errors detected.
  - `app/Livewire/Reports/LedgerReport.php` -> No syntax errors detected.
  - `app/Livewire/Reports/PrintReport/DailyReport.php` -> No syntax errors detected.
