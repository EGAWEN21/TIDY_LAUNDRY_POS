# Phases 3–4 — Financial Integrity, Concurrency, and Synchronization

Status: `[ ]` Not started

# Phase 3 — Unify Financial Rules and Protect Concurrent Writes

Main areas: `CreateOrderAction.php`, `UpdateOrderAction.php`, `ProcessPaymentAction.php`, `CalculateSecureOrderMathAction.php`, `CalculateCartTotals.php`.

## 3.1 Approve the financial contract first

- [!] Decide discount application before/after tax.
- [!] Define inclusive-tax treatment.
- [!] Define exclusive-tax treatment.
- [!] Define rounding precision.
- [!] Define rounding points.
- [!] Define allowed payment types.
- [!] Define refund authorization.
- [!] Define refund limits.
- [!] Define voided-order behavior.
- [!] Define financial-year assignment.
- [!] Define historical-price treatment.
- [!] Define stale catalog pricing for offline synchronization.
- [!] Define customer changes on paid orders.
- [!] Define accounting-date changes on paid orders.
- [!] Distinguish legitimate refund obligations from unauthorized payments and actual refunds.

## 3.2 Common financial invariants

- [ ] Recalculate authoritative values on server.
- [ ] Reject empty orders.
- [ ] Reject invalid quantities.
- [ ] Reject invalid references.
- [ ] Reject unsupported payment types.
- [ ] Validate persisted payment history plus proposed new payments.
- [ ] Never trust client-submitted historical payments.
- [ ] Put balance checks and payment insertion in one transaction.
- [ ] Lock order during balance-sensitive operations.
- [ ] Use consistent locking order/protocol across writers.
- [ ] Preserve payment history.
- [ ] Define customer attribution after edits.
- [ ] Define financial-year attribution after edits.
- [ ] Document money representation without schema-wide conversion.

## 3.3 Historical pricing

- [ ] Unchanged historical line preserves approved basis.
- [!] Approve quantity-change historical pricing.
- [ ] New line uses approved current pricing.
- [ ] Explicit override requires permission and validation.
- [!] Approve existing-discount preservation/change rule.
- [ ] Display inactive/deleted items historically and control further changes.
- [!] Approve historical-tax preservation/recalculation rule.
- [ ] Frontend preview matches backend contract.

## 3.4 Concurrency and safe retries

- [ ] Test two cashiers collecting same remaining balance.
- [ ] Test edit racing with payment.
- [ ] Test two editors saving same order.
- [ ] Test request edit racing with approval.
- [ ] Test commit followed by lost response.
- [!] Design revision mechanism covering order aggregate, related lines, and payments.
- [ ] Do not rely only on top-level timestamp if children can change independently.
- [ ] Add safe retry semantics for financial/workflow side effects.

## Phase 3 completion gate

- [ ] Creation, editing, payments, and refunds use one approved contract.
- [ ] Contract holds under normal use, retries, and concurrency.

# Phase 4 — Complete the Offline Synchronization Lifecycle

Improve existing batching, UUID, retry, and cross-tab locking rather than replacing them.

## 4.1 Structured processing

- [ ] Validate order batches explicitly.
- [ ] Validate nested payloads explicitly.
- [ ] Return per-item success outcome.
- [ ] Return per-item approval-required outcome.
- [ ] Return per-item validation-failure outcome.
- [ ] Return per-item permission-failure outcome.
- [ ] Return per-item conflict outcome.
- [ ] Return per-item retryable-server-failure outcome.
- [ ] Do not convert every server error into permanent failure.
- [!] Define supported older payload/client window.
- [ ] Preserve compatibility for supported queued payloads.
- [ ] Concurrent duplicate submissions remain idempotent.
- [!] Define replay behavior for soft-deleted orders.
- [ ] Preserve UUIDs through request editing/approval.
- [!] Define local `pending_approval` resolution.
- [ ] Retain queue ownership/work after auth expiry.
- [ ] Add request timeouts.
- [ ] Add server-reachability checks.
- [ ] Treat `navigator.onLine` as a hint only.
- [ ] Verify cross-tab lock renewal.
- [ ] Verify cross-tab lock expiration.
- [ ] Verify cross-tab lock recovery.
- [ ] Provide manual recovery for exhausted retries.

## 4.2 Coordinate online editing and offline records

- [ ] Older queued request cannot overwrite newer server edit.
- [ ] Approved request resolves to resulting order consistently.
- [ ] Rejected Sync Manager request uses online-only editor.
- [ ] Server-backed request never reopens through creation checkout.
- [ ] Unsynchronized new-order correction remains a distinct creation workflow.

## 4.3 Essential scenarios

- [ ] Server commits and response is lost.
- [ ] Connection drops midway through batch.
- [ ] Token expires during synchronization.
- [ ] Two tabs synchronize together.
- [ ] Customer succeeds but order fails.
- [ ] Rejected request is edited online and resubmitted.
- [ ] Approved record receives replay.
- [ ] Deleted record receives replay.
- [ ] Browser upgrade encounters legacy unowned work.

## Phase 4 completion gate

- [ ] Every queue state has a defined meaning.
- [ ] Every queue state has a safe recovery path.