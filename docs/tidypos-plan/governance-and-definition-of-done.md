# Governance, Reviewable Batches, Decisions, Evidence, and Definition of Done\n\nLast updated: 2026-09-13\n\n# Reviewable implementation batches\n\n| Batch | Deliverable | Status |\n|---|---|---|\n| 1 | Safe environment, baseline tests, dependency and behavior maps | [~] |\n| 2 | Customer identity reconciliation and durable offline saves | [ ] |\n| 3 | Authorization and shared-device isolation | [ ] |\n| 4 | Financial policies, shared validation, locking, and revisions | [ ] |\n| 5 | Sync outcomes, idempotency, and approval reconciliation | [ ] |
| 6 | Dedicated online editing APIs | [ ] |
| 7 | Vue existing-order editor | [ ] |
| 8 | Vue request editor and Sync Manager integration | [ ] |
| 9 | Downstream, database-upgrade, and PWA compatibility verification | [ ] |
| 10 | Legacy Livewire POS removal | [ ] |
| 11 | Coverage completion, CI, monitoring, and documentation | [ ] |
| 12 | Controlled production rollout and cleanup | [ ] |

Tests accompany every batch. Financial or authorization changes must not be hidden inside a large deletion commit.

# Batch gates

## Batch 1

- [ ] Phase 0 completion gate passes.
- [ ] Baseline evidence is repeatable.
- [ ] Dependency/behavior maps are reviewable.

## Batch 2

- [ ] Phase 1 tests pass.
- [ ] Customer identity and offline work are protected.

## Batch 3

- [ ] Phase 2 negative and shared-device tests pass.

## Batch 4

- [ ] Financial decisions approved.
- [ ] Shared validation, locking, revisions, and concurrency tests pass.

## Batch 5

- [ ] Every sync outcome/state/recovery is defined and tested.

## Batches 6–8

- [ ] Dedicated APIs pass authorization/validation/conflict tests.
- [ ] Existing-order editor passes E2E acceptance.
- [ ] Request editor and Sync Manager integration pass E2E acceptance.

## Batch 9

- [ ] Downstream, upgrade, numbering, and PWA compatibility gates pass.

## Batch 10

- [ ] Both Vue editors were accepted before deletion.
- [ ] Dependency sweep proves no active legacy POS workflow remains.

## Batch 11

- [ ] Coverage, CI, monitoring, documentation, and recovery instructions pass.

## Batch 12

- [ ] Pre-release, pilot, monitoring, deployment, and tested rollback pass.

# Change-control rules

- [ ] Keep commits focused by concern.
- [ ] Tests accompany each implementation batch.
- [ ] Run PHP tests, frontend tests, build, syntax, and diff checks after relevant changes.
- [ ] Do not claim browser behavior from backend tests or route registration.
- [ ] Do not alter infrastructure silently.
- [ ] Do not begin legacy deletion before replacement acceptance.
- [ ] Do not mix broad formatting into functional changes.
- [ ] Update these trackers after each material change/evidence result.
- [ ] Record intentional behavior changes and approval.
- [ ] Record existing failures separately from introduced failures.

# Decision register

| ID | Required decision | Status | Needed before |
|---|---|---|---|
| D-001 | Canonical customer UUID and phone-conflict reconciliation policy | Open | Batch 2 |
| D-002 | Legacy `PosDraft` conversion/export/recovery/retention | Open | Batch 2/removal |
| D-003 | Supported older client and queued payload window | Open | Batches 2/5/9 |
| D-004 | Discount/tax ordering | Open | Batch 4 |
| D-005 | Inclusive/exclusive tax and rounding contract | Open | Batch 4 |
| D-006 | Allowed payment types | Open | Batch 4 |
| D-007 | Refund authorization, limits, and obligation treatment | Open | Batch 4 |
| D-008 | Voided-order behavior | Open | Batch 4 |
| D-009 | Financial-year/accounting-date/customer changes on paid orders | Open | Batch 4 |
| D-010 | Historical line, quantity, new-line, discount, override, and tax pricing | Open | Batch 4 |
| D-011 | Aggregate revision and idempotency mechanism | Open | Batches 4–6 |
| D-012 | Soft-deleted sync replay behavior | Open | Batch 5 |
| D-013 | Local `pending_approval` resolution | Open | Batch 5 |
| D-014 | Order-edit permission/visibility policy | Open | Batches 3/6 |
| D-015 | Request-edit permission/visibility policy | Open | Batches 3/6 |
| D-016 | Notification events and deduplication policy | Open | Batches 4/9/11 |

# Risk and finding register

| ID | Severity | Finding | State/response |
|---|---|---|---|
| R-001 | High | Plain PHP is 8.2.4; project requires 8.4.1+ | Herd PHP 8.4.20 works; document/enforce runtime |
| R-002 | High | PHPUnit DB overrides are commented and `TestCase` has no production guard | Open; resolve before mutation-heavy tests |
| R-003 | Medium | Storage test rewrites tracked PWA icons as a side effect | Open; isolate writes and cleanup |
| R-004 | Medium | Older roadmap/checklist describes two permanent POS systems | Consolidated plan supersedes it; update docs deliberately |
| R-005 | Medium | Initial search found no CI workflow | Confirm and establish CI in Batch 11 |
| R-006 | Low | 20 existing Pint issues across 232 files | Separate hygiene; do not mix into functional batches |
| R-007 | Medium | Composer audit previously used cache after Packagist timeout | Current audit clear; repeat in stable CI |
| R-008 | High | Legacy draft/queue ownership and recovery are not yet proven | Release invariant; address in Batches 1–3 |
| R-009 | High | Financial/historical pricing contract not explicitly approved | Blocks Batch 4 and editors |
| R-010 | High | No aggregate revision/conflict policy yet | Blocks safe online editing |

# Evidence log

| Date | Evidence | Result |
|---|---|---|
| 2026-09-13 | `herd php -v` | PHP 8.4.20 |
| 2026-09-13 | `herd composer check-platform-reqs` | All platform requirements pass |
| 2026-09-13 | `herd php artisan test` | 48 passed, 181 assertions |
| 2026-09-13 | `npm run build` | Vite/PWA production build passed; 57 precache entries |
| 2026-09-13 | `herd php artisan migrate:status` | All listed migrations applied |
| 2026-09-13 | Route list check | Passed |
| 2026-09-13 | `npm audit` | Zero vulnerabilities |
| 2026-09-13 | `herd composer audit` | No known advisories after Livewire 3.8.8 |
| 2026-09-13 | Targeted Pint | Modified tests pass |
| 2026-09-13 | Repository Pint baseline | 20 existing issues across 232 files |

# Final definition of done

The project is complete only when every item below passes.

## Functional

- [ ] Vue is the sole POS interface.
- [ ] Offline creation remains operational.
- [ ] Existing orders can be edited online.
- [ ] Requests can be edited and resubmitted online.
- [ ] Editing preserves identity.
- [ ] Editing does not duplicate payments.
- [ ] Editing does not duplicate orders/requests.

## Safety and security

- [ ] Offline work survives supported failures.
- [ ] Offline work survives supported upgrades.
- [ ] Creation drafts remain isolated from editing.
- [ ] Permissions are enforced on every sensitive operation.
- [ ] Visibility is enforced on every sensitive operation.
- [ ] Concurrent updates are handled safely.
- [ ] Stale updates are handled safely.
- [ ] Historical financial rules are explicit.
- [ ] Historical financial rules are tested.

## Compatibility

- [ ] Reports remain correct.
- [ ] Ledgers remain correct.
- [ ] Approvals remain correct.
- [ ] Refunds remain correct.
- [ ] Receipts remain correct.
- [ ] Notifications remain correct.
- [ ] Back-office workflows remain correct.
- [ ] Existing URLs behave deliberately.
- [ ] Supported older creation queues still synchronize.
- [ ] Database upgrades are verified.
- [ ] PWA releases are verified.

## Maintainability

- [ ] Legacy POS dependencies are removed.
- [ ] Shared domain logic remains centralized.
- [ ] Code follows agreed standards.
- [ ] Tests are in place.
- [ ] CI is in place.
- [ ] Documentation is in place.
- [ ] Monitoring is in place.
- [ ] Rollback procedures are in place and tested.

# Current next actions

1. Enforce test-database isolation and a production fail-fast guard.
2. Audit external notification boundaries and disable real delivery in tests.
3. Run Playwright baseline and classify failures.
4. Build the full legacy dependency map.
5. Capture current behavior and unsafe behavior separately.
6. Establish and test isolated backup/restore and populated upgrade procedures.

Do not delete the Livewire POS before the Vue replacement is verified.