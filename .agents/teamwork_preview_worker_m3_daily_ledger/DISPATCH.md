# Dispatch for Worker M3 — Daily & Ledger Reports Remediation

You are Worker M3 for the TidyPOS report section audit project.
Working directory: `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m3_daily_ledger\`
Original request: `c:\Users\DELL\Herd\tidypos\.agents\ORIGINAL_REQUEST.md`
Project Plan: `c:\Users\DELL\Herd\tidypos\PROJECT.md`

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Exclusive Write Ownership
You exclusively own and may edit ONLY these files:
- `app/Livewire/Reports/DailyReport.php`
- `app/Livewire/Reports/LedgerReport.php`
- `app/Livewire/Reports/PrintReport/DailyReport.php`
- `resources/views/livewire/reports/daily-report.blade.php`
- `resources/views/livewire/reports/ledger-report.blade.php`
- `resources/views/livewire/reports/print-report/daily-report.blade.php`

## Required Fixes & Tasks
1. **Critical JavaScript Crash in `ledger-report.blade.php`**:
   - Line 193: Replace unquoted `formatter: function (val) { return getCurrency() + val.toFixed(2); }` with `formatter: function (val) { return "{{ getCurrency() }}" + val.toFixed(2); }` so it matches line 204 and doesn't throw `ReferenceError: getCurrency is not defined`.
2. **Unsafe Null Access in `LedgerReport.php`**:
   - Line 166: Change `$master_settings = MasterSettings::first()->siteData();` to `$master_settings = MasterSettings::first()?->siteData() ?? [];` (or safe null navigation).
3. **Missing `$lang` in `app/Livewire/Reports/PrintReport/DailyReport.php`**:
   - Add `public $lang;` property and initialize it in `mount()` (e.g. `$this->lang = \App\Models\Translation::where('id', 1)->first();`).
4. **CSV Export Support**:
   - In `DailyReport.php` and `LedgerReport.php`, ensure `exportCsv()` is implemented using `App\Traits\CsvExportable` and export buttons in views hook up correctly.
5. **Verification**:
   - Run `php -l` on all modified PHP files.

Write your changes report to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m3_daily_ledger\changes.md` and handoff to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m3_daily_ledger\handoff.md`.
