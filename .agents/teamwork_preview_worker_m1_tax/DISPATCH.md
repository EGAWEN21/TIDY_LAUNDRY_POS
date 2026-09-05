# Dispatch for Worker M1 — Tax Report Remediation

You are Worker M1 for the TidyPOS report section audit project.
Working directory: `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m1_tax\`
Original request: `c:\Users\DELL\Herd\tidypos\.agents\ORIGINAL_REQUEST.md`
Project Plan: `c:\Users\DELL\Herd\tidypos\PROJECT.md`

## Mandatory Integrity Warning
DO NOT CHEAT. All implementations must be genuine. DO NOT hardcode test results, create dummy/facade implementations, or circumvent the intended task. A teamwork_preview_auditor will independently verify your work. Integrity violations WILL be detected and your work WILL be rejected.

## Exclusive Write Ownership
You exclusively own and may edit ONLY these files:
- `app/Livewire/Reports/TaxReport.php`
- `app/Livewire/Reports/PrintReport/TaxReport.php`
- `app/Livewire/Reports/DownloadReport/TaxReport.php`
- `resources/views/livewire/reports/tax-report.blade.php`
- `resources/views/livewire/reports/print-report/tax-report.blade.php`
- `resources/views/livewire/reports/download-report/tax-report.blade.php`

## Required Fixes & Tasks
1. **Tax Calculations in `TaxReport.php`**:
   - For Sales Tax: Ensure sales tax logic uses the stored order fields: `$order->tax_amount` and taxable amount: `$order->taxable_amount > 0 ? $order->taxable_amount : ($order->total - $order->tax_amount)`.
   - For Expense Tax: Handle `tax_included == 1` vs exclusive tax properly.
     If `tax_included == 1` (inclusive):
     $taxAmount = expense_amount - (expense_amount / (1 + (tax_percentage / 100)))$
     $beforeTax = expense_amount - taxAmount$
     If exclusive ($tax_included == 0$ or null):
     $taxAmount = expense_amount * (tax_percentage / 100)$
     $beforeTax = expense_amount$
     $expenseTotal = beforeTax + taxAmount$
2. **Tax Calculations in PDF & Print Views**:
   - In `resources/views/livewire/reports/download-report/tax-report.blade.php` and `resources/views/livewire/reports/print-report/tax-report.blade.php`:
     Replace `$row->total * ($row->tax_percentage / 100)` with the actual stored `$row->tax_amount` and taxable amount.
     Ensure expense tax in print/pdf matches the inclusive/exclusive logic.
3. **Missing `$lang` in `app/Livewire/Reports/PrintReport/TaxReport.php`**:
   - Add `public $lang;` property and initialize it in `mount()` (e.g. `$this->lang = \App\Models\Translation::where('id', 1)->first();`).
4. **HTML Structure in `print-report/tax-report.blade.php`**:
   - Ensure clean HTML structure (valid closing tags, UTF-8 charset).
5. **Verification**:
   - Run `php -l` on all modified PHP files.
   - Verify views have no syntax errors.

Write your changes report to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m1_tax\changes.md` and handoff to `c:\Users\DELL\Herd\tidypos\.agents\teamwork_preview_worker_m1_tax\handoff.md`.
