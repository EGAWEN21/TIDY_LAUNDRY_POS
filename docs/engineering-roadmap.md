# Engineering Roadmap and Decision Register

Last reviewed: 2026-07-24

This register preserves phased decisions and deferred work across sessions. Items must not be started early without revisiting their entry criteria.

For the durable product and architecture context, consult `docs/system-context.md`. Update both documents when confirmed understanding or constraints change.

## Completed

- Dependency and frontend security remediation merged into `main`.
- Intervention Image compatibility migration completed.
- Permission, POS API, offline-sync, PWA, migration, route, and build validation completed.
- Public storage contract standardized and committed in `dc64335`.
- Customer action/DTO workflow tests added in `91f89aa`.
- Service workflow tests and the durable roadmap added in `2338bb8`.
- POS authorization/state characterization tests added in `9cd2ba6`.
- Master-settings logo/favicon storage tests added in `9b06390`.
- Payment/report authorization tests added in the current checkpoint.
- Durable system context and clarification-question register added in `docs/system-context.md`.
- Browser/manual acceptance checklist added in `docs/browser-acceptance-checklist.md`.

## Completed phase: workflow-test expansion

Proceed incrementally and keep production behavior unchanged unless a test identifies a concrete defect.

1. Customer action tests — complete.
2. Service creation/edit/icon tests — complete.
3. POS authorization and state characterization tests — complete.
4. Master settings logo/favicon storage tests — complete.
5. Payment and report authorization tests — complete.
6. Full regression and browser/manual acceptance planning — next phase.

Do not include the unresolved customer Excel export in unrelated tests.

## Active phase: browser/manual acceptance planning

Automated workflow coverage is now complete for the current scope. Execute `docs/browser-acceptance-checklist.md` for authenticated browser verification of login, role navigation, both POS systems, synchronization, payments, branding uploads, printing, PWA/offline behavior, reports, and WhatsApp integration. Do not claim these behaviors are verified until manually exercised.

## Approved future architecture phase: frontend/backend alignment

Entry criteria: workflow coverage is stable and POS behavior is characterized.

1. Extract POS state-to-`OrderData` mapping into a dedicated builder/action.
2. Test new-order, edit-order, approval-request, payment, and offline payload variants.
3. Review whether child Livewire components provide measurable value.
4. If justified, extract one child component at a time: customer selection, payment UI, then cart UI.
5. Run regression tests after each extraction and browser-test the affected workflow.

Do not perform an all-at-once `PosScreen` rewrite. Do not add a third-party state-management package unless a specific requirement emerges.

## Deferred integration-hardening phase: WhatsApp

Entry criteria: current workflow phase is complete and queue/idempotency behavior is specified.

1. Extract POST signature validation into `VerifyWhatsAppWebhook` middleware.
2. Keep GET verification-token handling separate.
3. Introduce a verified payload object.
4. Introduce `ProcessWhatsAppMessage` as a queued job.
5. Define retry, duplicate-delivery, failure, and observability behavior before implementation.
6. Add mocked integration tests for valid, invalid, duplicate, malformed, and retry cases.

Current controller behavior is protected and tested; this is deferred hardening, not an unaddressed authentication gap.

## Conditional infrastructure backlog

### Redis/Horizon

Evaluate only when queue volume, latency, database contention, or operational monitoring justifies it. Requires provisioning, worker supervision, retry policy, failed-job monitoring, staging validation, and deployment documentation.

### Laravel Reverb / collaborative POS

Evaluate only after the business confirms a real multi-terminal collaboration requirement. Requires authorization channels, event design, conflict/version policy, and multi-session browser tests. Reverb alone does not solve concurrent-edit conflicts.

## Separate deferred defects and hygiene

- Customer Excel export: `CustomersList::downloadFile()` references missing `App\\Exports\\CustomersExport`; define columns and authorization before implementation.
- Pint/style cleanup: run as a separate controlled quality task, not mixed with behavior changes.
- Composer audit: retry when Packagist DNS/network access is available.
- Browser/manual authenticated workflow validation: required for UI, upload, printing, and real offline/PWA behavior.
- Remote synchronization: local `main` contains commits not yet pushed to `origin/main`.

## Change-control rules

- Keep focused commits by concern.
- Run PHPUnit, build, syntax, and diff checks after each implementation batch.
- Do not claim browser behavior from route registration or backend tests.
- Do not create deployment artifacts or alter infrastructure silently.
- Update this register when a deferred item changes status or its entry criteria are met.
- Update `docs/system-context.md` when a product rule, architecture constraint, or clarification question is confirmed or changed.
