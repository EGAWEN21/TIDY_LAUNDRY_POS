# Current Behavior Risk and Decision Register

Date: 2026-09-13  

Companion to: `current-behavior-specification.md`

These are observed baseline issues. Do not silently preserve them as requirements or change them without tests and approval where financial/audit behavior is involved.

## Critical integrity and concurrency risks

| ID | Area | Observed risk | Required resolution |
|---|---|---|---|
| CBR-001 | Existing-order edit | No order lock or version check; concurrent edits can overwrite each other. | Define optimistic/pessimistic conflict contract and test stale writes. |
| CBR-002 | Edit payments | `UpdateOrderAction` appends payments without aggregate overpayment validation or locking. | Lock order/payment set and enforce authoritative balance atomically. |
| CBR-003 | Post-creation payments | `ProcessPaymentAction` reads balance and inserts without transaction/row lock. Concurrent requests can overpay. | Transaction, lock, and concurrency tests. |
| CBR-004 | Offline idempotency | Locking existing UUID rows does not protect concurrent first inserts when no row exists. | Verify/add unique constraints and handle duplicate-key replay deterministically. |
| CBR-005 | UUID persistence | Direct sync assigns order UUID after `CreateOrderAction` completes. | Persist UUID in the creation transaction. |
| CBR-006 | Request acceptance | Acceptance trusts stored request math instead of visibly rerunning secure math against current authority. | Decide snapshot-vs-current pricing and enforce explicitly. |
| CBR-007 | Notifications | Creation event is dispatched inside the DB transaction despite comments claiming after-commit behavior. | Use after-commit/outbox semantics and idempotency keys. |

## Financial decisions

| ID | Observed behavior | Decision needed |
|---|---|---|
| CBR-008 | Money uses floating-point arithmetic. | Approve decimal/minor-unit strategy and rounding points. |
| CBR-009 | Discount is subtracted after tax; total clamps to zero while discount remains unchanged. | Accounting approval and boundary tests. |
| CBR-010 | Current tax settings can affect historical edits, split, and merge. | Define snapshot pricing/tax rules for historical orders. |
| CBR-011 | Delivered order may remain editable; edit action does not enforce status restrictions. | Define editable statuses and privileged exceptions. |
| CBR-012 | Refunds are negative payment rows and only allowed when the order is already overpaid. | Define refund/void policy, reason/audit requirements, and authorization. |
| CBR-013 | Request acceptance changes order date to acceptance time. | Confirm reporting and financial-year expectations. |
| CBR-014 | Split keeps all add-ons and discount on original order. | Approve allocation behavior. |
| CBR-015 | Merge sums discounts and permanently deletes secondary orders. | Approve audit and financial behavior. |

## Customer identity and visibility

| ID | Observed risk | Required resolution |
|---|---|---|
| CBR-016 | Phone matching is global without an explicit normalization/uniqueness contract. | Define canonical phone identity and database constraints. |
| CBR-017 | Standalone customer sync rejects unauthorized overwrite; graph sync continues with an audit note. | Define one conflict contract. |
| CBR-018 | Livewire customer creation bypasses shared customer actions. | Route all surfaces through authorized shared behavior. |
| CBR-019 | Livewire `selectCustomer(id)` does not reapply visibility filtering. | Enforce scoped lookup server-side. |
| CBR-020 | Pending customer reconciliation and duplicate merge rules are incomplete. | Approve UUID/phone precedence and conflict UI. |

## Draft and synchronization risks

| ID | Observed risk | Required resolution |
|---|---|---|
| CBR-021 | Legacy unowned IndexedDB rows are claimed by the next authenticated POS user. | Define safe migration/recovery on shared devices. |
| CBR-022 | Vue draft does not persist order/delivery dates. | Decide full draft schema and version it. |
| CBR-023 | Draft persistence ignores note/payment/discount-only state when cart/add-on/customer is empty. | Define meaningful-empty draft behavior. |
| CBR-024 | `pending_approval` rows remain local, but no observed reconciliation endpoint removes them after acceptance. | Add accepted/rejected reconciliation contract. |
| CBR-025 | Some server-side unexpected item errors are returned as HTTP 200 item failures and become permanent. | Define retryable vs permanent server error codes. |
| CBR-026 | Empty queue and attention state both return `success=false`; callers must inspect `outcome`. | Stabilize documented sync result schema. |
| CBR-027 | Livewire `PosDraft` and Vue IndexedDB drafts have no conversion/recovery policy. | Resolve D-002 before legacy removal. |

## Approval and audit risks

| ID | Observed risk | Required resolution |
|---|---|---|
| CBR-028 | Accepted requests are deleted rather than retained as immutable audit records. | Define request retention/soft-delete/audit policy. |
| CBR-029 | Bypass conversion of rejected request deletes the request. | Define audit linkage to resulting order. |
| CBR-030 | `OrderRequest` numbering assumes its sequence row exists. | Add transactional fresh-install fallback and test. |
| CBR-031 | Request notifications differ between Livewire and offline batch paths. | Define one event and deduplication contract. |
| CBR-032 | Inline request permissions lack a dedicated policy matrix. | Create policy and negative tests for owner/editor/approver cases. |

## Deletion, split, and merge risks

| ID | Observed risk | Required resolution |
|---|---|---|
| CBR-033 | Edit force-deletes old details/add-ons, losing row-level history. | Decide audit/history retention. |
| CBR-034 | Merge force-deletes secondary orders. | Preserve merge lineage and approve permanent deletion. |
| CBR-035 | Split uses a separate “last order + 1” numbering algorithm, not the locked sequence. | Use the canonical sequence and concurrency tests. |
| CBR-036 | Bulk status invokes item operations sequentially without one atomic batch contract. | Define partial-failure/reporting behavior. |
| CBR-037 | Recycle-bin UI says automatic purge after 90 days; purge implementation was not established in this trace. | Verify scheduler/job and test retention. |

## Receipt and notification defects

| ID | Observed defect/risk | Required resolution |
|---|---|---|
| CBR-038 | Public receipt route is `/receipt/{uuid}`, but WhatsApp signed URL generation supplies `id`. | Correct route parameter contract and add signed-link test. |
| CBR-039 | `PrintOrder` accepts ID or UUID, so public signed links may expose numeric IDs by design. | Decide opaque public identifier policy. |
| CBR-040 | Notification listener catches exceptions, preventing queued retry despite `$tries = 3`. | Re-throw retryable failures and define terminal handling. |
| CBR-041 | Creation notification currently sends SMS only; comments imply broader async notifications. | Define actual channel contract. |
| CBR-042 | Status changes synchronously trigger external email/SMS/WhatsApp behavior. | Move to after-commit queued events with deduplication. |
| CBR-043 | POS layout loads ApexCharts from a public CDN. | Remove unnecessary dependency or approve offline/security behavior. |

## Authorization and information-boundary risks

| ID | Observed risk | Required resolution |
|---|---|---|
| CBR-044 | Dedicated Vue edit/request APIs do not exist. | Add resource policies, scoped queries, validation, and tests before route replacement. |
| CBR-045 | Super administrators can authenticate while inactive. | Confirm intentional exemption. |
| CBR-046 | Some unauthorized Livewire back-office paths return 404 while POS middleware returns 403. | Define consistent disclosure policy. |
| CBR-047 | Order status action relies on authenticated global state for paid-delivery override. | Pass actor explicitly and authorize in the action/policy. |

## Test gaps required before replacement

- Concurrent payment, edit, UUID, and sequence tests.
- Vue/API tests for existing-order and request editing.
- Price override, discount permission, and stale catalog tests.
- Request acceptance snapshot/current-price tests.
- Queue upgrade, ownership, retry, reauthentication, permanent failure, and approval reconciliation tests.
- Split/merge numbering, payment allocation, lineage, and rollback tests.
- Refund, returned-order, and delivered-order tests.
- Delete/restore/force-delete relationship tests.
- Signed receipt authorization and route-generation tests.
- Notification after-commit, retry, and deduplication tests.
