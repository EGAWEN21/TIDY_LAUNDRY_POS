# Dispatch for Worker M5 — Business Insights & CSV Export Remediation

You are Worker M5 for the TidyPOS report section audit project.
Working directory: `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m5_insights_csv\`
Original request: `c:\Users\DELL\Herd\tidypos\.agents\ORIGINAL_REQUEST.md`
Project Plan: `c:\Users\DELL\Herd\tidypos\PROJECT.md`

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Exclusive Write Ownership
You exclusively own and may edit ONLY these files:
- `app/Livewire/Reports/BusinessInsights.php`
- `resources/views/livewire/reports/business-insights.blade.php`
- `app/Traits/CsvExportable.php`

## Required Fixes & Tasks
1. **UTF-8 BOM in `CsvExportable.php`**:
   - In `app/Traits/CsvExportable.php`, write the UTF-8 BOM (`fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));` or `fwrite($file, "\xEF\xBB\xBF");`) before writing CSV headers so that Excel correctly renders UTF-8 characters and currency symbols.
2. **Business Insights Overdue Comparison**:
   - In `BusinessInsights.php:86`, update `Carbon::parse($o->delivery_date)->isPast()` to `Carbon::parse($o->delivery_date)->endOfDay()->isPast()` so orders due today are not falsely flagged as overdue before the day ends.
3. **Blade Prop Syntax Error in `business-insights.blade.php`**:
   - Line 70: Change `:trendUp="{{ ($businessHealth['aov_trend_up'] ?? false) ? 'true' : 'false' }}"` to `:trendUp="!empty($businessHealth['aov_trend_up'])"`.
4. **Livewire 3 Commit Hook / Chart Updates**:
   - In `business-insights.blade.php`, ensure the ApexChart update script properly listens to Livewire updates/events or safely extracts component properties.
5. **Verification**:
   - Run `php -l` on all modified PHP files.

Write your changes report to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m5_insights_csv\changes.md` and handoff to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m5_insights_csv\handoff.md`.
