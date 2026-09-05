# BRIEFING — 2026-08-24T11:39:00Z

## Mission
Investigate, audit, and map the Tax, Expense, and Daily Reports in TidyPOS to find all bugs, math discrepancies, query issues, UI flaws, and edge cases.

## 🔒 My Identity
- Archetype: explorer
- Roles: survey, analysis, verification
- Working directory: c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_explorer_survey_1\
- Original parent: 3d370f15-4a00-4dc4-98c6-f85529be93c7
- Milestone: survey_phase_1

## 🔒 Key Constraints
- Read-only investigation — do NOT implement fixes directly in source code.
- Provide comprehensive survey report with exact file paths, line numbers, formulas, queries, bugs, and proposed fixes.

## Current Parent
- Conversation ID: 3d370f15-4a00-4dc4-98c6-f85529be93c7
- Updated: 2026-08-24T11:39:00Z

## Investigation State
- **Explored paths**:
  - `app/Livewire/Reports/TaxReport.php`
  - `app/Livewire/Reports/ExpenseReport.php`
  - `app/Livewire/Reports/DailyReport.php`
  - `resources/views/livewire/reports/tax-report.blade.php`
  - `resources/views/livewire/reports/expense-report.blade.php`
  - `resources/views/livewire/reports/daily-report.blade.php`
  - `resources/views/livewire/reports/download-report/*`
  - `resources/views/livewire/reports/print-report/*`
  - `app/Traits/CsvExportable.php`
  - Associated models and database migrations
- **Key findings**:
  - BUG-01: Discrepancy in expense tax calculations in download-report & print-report (ignoring tax_included).
  - BUG-02: SoftDeletes bypassed in DailyReport `itemVolume` raw query.
  - BUG-03: SoftDeletes bypassed in DailyReport 7-day payment trend raw query.
  - BUG-04: Non-portable MySQL `DATEDIFF` in DailyReport `overdueOrders`.
  - BUG-05: Title mismatch in daily report download view.
  - BUG-06: Missing Gate permissions in download report Livewire wrappers.
  - BUG-07: Incomplete select clause for `latest()` in TaxReport.
  - BUG-08: Potential JS null-reference on `val.toFixed(2)` in expense report charts.
- **Unexplored areas**: None for this survey scope.

## Key Decisions Made
- Fully documented all 8 identified issues in `survey_report.md` and `handoff.md`.

## Artifact Index
- `.agents/teamwork_preview_explorer_survey_1/survey_report.md` — Full survey report
- `.agents/teamwork_preview_explorer_survey_1/handoff.md` — 5-component handoff report
- `.agents/teamwork_preview_explorer_survey_1/progress.md` — Liveness & status tracking
