# Phase 0 Status Addendum

Date: 2026-09-15

This addendum records evidence newer than the original literal-escaped `phase-0-baseline.md` tracker. It does not mark Phase 0 complete.

## Completed

- Test execution is forced to `database/testing.sqlite`.
- Base tests fail fast unless the application is in `testing` and uses the exact isolated SQLite path.
- Queue, mail, notifications, and stray HTTP delivery are isolated in the test base.
- SMS delivery returns without external delivery in the testing environment.
- Branding upload tests use a temporary public path and remove generated files during teardown.
- Focused storage test passes: 2 tests, 11 assertions.
- Full PHP baseline passes: 48 tests, 184 assertions.
- Repeatable `composer baseline` command passes locally under PHP 8.4.20 and covers platform requirements, PHP tests, route enumeration, all 58 migrations, the production frontend build, Playwright, Composer audit, and npm audit.
- The baseline rejects PHP older than 8.4.1, forces database-aware checks to `database/testing.sqlite`, and retries only network-dependent audits.
- Developer runtime and baseline instructions are documented in `README.md`.
- GitHub Actions baseline workflow is configured with PHP 8.4, Node.js 20, isolated databases, and Playwright Chromium; first hosted CI execution remains to be observed.
- Isolated Playwright baseline passes: 7 tests, 0 failures.
- Browser coverage verifies authenticated online and offline POS access, offline queue persistence and successful replay, retry recovery, five-item batching with permanent item-failure isolation, token-expiry re-authentication, and service-worker registration.
- Playwright provisions and resets only `database/e2e.sqlite`; the temporary server exits after the suite.
- Production database file hash remained unchanged during test execution.
- Legacy dependency map completed: `legacy-pos-dependency-map.md`.
- Current behavior contract completed: `current-behavior-specification.md`.
- Unsafe behavior and decisions separated: `current-behavior-risk-register.md`.

## Still open

- Confirm the first hosted GitHub Actions baseline run passes.
- Production database engine identification without exposing secrets.
- Approved backup procedure and tested isolated restoration.
- Sanitized production-like clean-install and populated-upgrade tests.
- Production-engine concurrency and migration verification.
- Explicit approval for customer, financial, audit, notification, draft-retention, and editing-conflict decisions.

## Phase 0 gate

| Gate | Status |
|---|---|
| Repeatable baseline | Complete locally; hosted CI run pending |
| Complete dependency map | Complete |
| Tested backup and restoration | Open |
| Workflow specification | Documented; approval open |
| Existing defects separated from new failures | Complete |
| No business behavior changed during characterization | Complete |

Phase 0 remains **in progress** because hosted CI confirmation, backup/restore, production-like database verification, and decision approval are not complete.
