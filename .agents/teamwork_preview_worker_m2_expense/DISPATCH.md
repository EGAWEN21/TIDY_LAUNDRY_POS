# Dispatch for Worker M2 — Expense Report Remediation

You are Worker M2 for the TidyPOS report section audit project.
Working directory: `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m2_expense\`
Original request: `c:\Users\DELL\Herd\tidypos\.agents\ORIGINAL_REQUEST.md`
Project Plan: `c:\Users\DELL\Herd\tidypos\PROJECT.md`

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Exclusive Write Ownership
You exclusively own and may edit ONLY these files:
- `app/Livewire/Reports/ExpenseReport.php`
- `app/Livewire/Reports/PrintReport/ExpenseReport.php`
- `app/Livewire/Reports/DownloadReport/ExpenseReport.php`
- `resources/views/livewire/reports/expense-report.blade.php`
- `resources/views/livewire/reports/print-report/expense-report.blade.php`
- `resources/views/livewire/reports/download-report/expense-report.blade.php`

## Required Fixes & Tasks
1. **Uncategorized Expenses Dropped in `ExpenseReport.php`**:
   - In category breakdown queries (lines 97-103, 105-111) and sorting query (lines 209-212), replace `join('expense_categories', ...)` with `leftJoin('expense_categories', ...)`.
   - Use `COALESCE(expense_categories.expense_category_name, 'Uncategorized') as name` or equivalent so that expenses without a category are included and labeled as 'Uncategorized'.
2. **Missing `$lang` in `app/Livewire/Reports/PrintReport/ExpenseReport.php`**:
   - Add `public $lang;` property and initialize it in `mount()` (e.g. `$this->lang = \App\Models\Translation::where('id', 1)->first();`).
3. **Blade Prop Syntax Error in `expense-report.blade.php`**:
   - Line 64: Change `:trendUp="{{ ($kpi['net_cash'] ?? 0) >= 0 ? 'true' : 'false' }}"` to `:trendUp="($kpi['net_cash'] ?? 0) >= 0"`.
4. **Badge Text Visibility Clashes in `expense-report.blade.php`**:
   - Line 129: Remove conflicting `text-white` from badges with `text-primary-600 bg-primary-100`.
5. **Clean HTML Structure in Print View**:
   - Ensure `print-report/expense-report.blade.php` has valid HTML/CSS and correct variable bindings.
6. **Verification**:
   - Run `php -l` on all modified PHP files.

Write your changes report to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m2_expense\changes.md` and handoff to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m2_expense\handoff.md`.
