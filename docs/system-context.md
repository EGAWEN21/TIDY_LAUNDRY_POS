# TidyPOS System Context and Product Understanding

Last reviewed: 2026-07-24

This is the durable product and architecture context for TidyPOS. Update it when confirmed understanding changes. Consult it before consequential work involving POS behavior, routes, synchronization, permissions, orders, finance, PWA behavior, or infrastructure.

`docs/engineering-roadmap.md` records work status. This document records what the product is and how it is intended to operate.

## Product identity

TidyPOS is a permission-driven laundry and garment-care operations platform for managing:

- customers and customer ledgers
- laundry services, service types, and service-specific prices
- addons
- orders and delivery dates
- payments and outstanding balances
- approval workflows
- staff roles and permissions
- reports and business intelligence
- receipts and printing
- notifications
- WhatsApp order-status interactions
- online POS operations
- offline POS operations with later synchronization

The principal differentiator is the intentional existence of two separate POS systems optimized for different connectivity conditions.

## Non-negotiable dual POS architecture

### Online Livewire POS

Route:

```text
/admin/online-pos
```

This is the online, backend-immediate POS for stable network conditions. It uses Livewire, `App\\Livewire\\Orders\\PosScreen`, server/database drafts, backend Actions, DTOs, database transactions, secure server-side recalculation, approval workflows, payments, edits, and printing.

It is not a deprecated version of the offline POS and must not be replaced by the Vue POS.

### Offline/PWA Vue POS

Route:

```text
/admin/pos
```

This is the offline-capable POS for field staff, unstable networks, and no-network operation. It uses Vue 3, Pinia, Dexie/IndexedDB, Sanctum API tokens, service workers, local catalog/customer/cart/draft storage, an offline queue, retries, synchronization management, and rejected-order recovery.

It is not merely a frontend copy of Livewire. It is a separate operational system with a local persistence and synchronization contract.

### Architectural rule

`/admin/pos` and `/admin/online-pos` are intentionally separate. Do not merge, rename, collapse, or make one replace the other without explicit product and architectural review.

Admin staff, field staff, or other authorized staff may use either according to connectivity and operational needs:

- stable network: online Livewire POS may be preferred
- unstable/no network: offline Vue/PWA POS may be used
- both systems must remain reliable and available

Any improvement must preserve both workflows and their boundary. Never assume that similar UI means duplicate architecture.

Before consequential POS, route, service-worker, API, synchronization, or data-model changes, ask for clarification when intent is not already confirmed.

## Laundry business model

An order contains one or more laundry services and optional addons. A service has a name, icon, active state, service types, and service-type-specific prices.

Orders may contain:

- service items and quantities
- service type selections
- garment/color information
- addons
- discounts
- tax and tax mode
- notes
- order and delivery dates
- registered or walk-in customers
- multiple payments
- outstanding balances
- approval state

Frontend prices and totals are not authoritative. Backend Actions reconstruct official prices and secure totals before persistence.

## Customers

Customers can be searched by name or phone, created in either POS, attached to orders/payments, used for ledgers/reports, and used in WhatsApp verification.

Walk-in customers are supported, but an unpaid balance requires a registered customer to prevent anonymous ledger debt.

Offline customers receive UUIDs. Synchronization resolves by UUID or phone before creating a duplicate. Existing customer overwrite behavior is permission-sensitive: a user without `customer_edit` must not silently overwrite an existing profile.

## Order lifecycle

The normal lifecycle is:

1. Build cart.
2. Select/create customer.
3. Add services, quantities, colors, addons, discounts, and notes.
4. Calculate tax and total.
5. Add payments.
6. Submit directly if authorized.
7. Submit an approval request if required.
8. Approve or reject through management workflow.
9. Correct and resubmit rejected requests where supported.
10. Edit orders where permitted.
11. Print where permitted.

Direct orders, pending requests, rejected requests, and offline-synchronized orders are distinct states. Offline UUIDs provide idempotency across retries and must prevent duplicate orders or approval requests.

## Payments and finance

Supported payment types include cash, UPI, card, cheque, and bank transfer.

The system tracks subtotal, addons, discounts, tax, taxable amount, total, paid amount, balance, and notes.

Controls include:

- no negative payments
- no payment above current balance
- registered customer required for unpaid balance
- server-side payment validation
- preservation of historical payments during edits unless an explicitly approved rule changes this

Both POS systems calculate locally for usability; the backend recalculates authoritative totals before persistence.

## Permissions and security

Granular permissions cover orders, customers, services, service types, addons, expenses, payments, reports, settings, users, roles, translations, approval workflows, price overrides, discounts, and bulk actions.

Important permissions include:

```text
order_create
order_edit
order_print
customer_create
customer_edit
service_create
service_edit
payment_list
report_order
report_sales
report_customer
order_price_override
order_discount_apply
```

Super-admin users intentionally bypass normal permission checks. Restricted users must be blocked server-side even when navigation links are hidden.

Security mechanisms include inactive-user checks, session validation, single-session behavior, Sanctum token protection and abilities, login rate limiting, signed WhatsApp webhook verification, DTO validation, secure price validation, UUID idempotency, per-record synchronization transactions, and approval controls.

## Offline synchronization contract

The Vue POS persists services, service types, service details, addons, customers, settings, cart drafts, and sync queue records in IndexedDB.

Queue records can be `pending` or `error` and include retry counts, error messages, timestamps, record type, UUID, and serialized data.

The flow is:

1. Authenticate with a Sanctum POS token.
2. Initialize from `/api/pos/init` while online.
3. Persist catalog/settings/customers locally.
4. Continue cart/customer operations offline.
5. Queue offline customers/orders.
6. Detect reconnection or run Sync Manager manually.
7. Submit orders through `/api/pos/sync-orders` in batches.
8. Submit standalone customers through `/api/pos/sync-customers`.
9. Process each order independently.
10. Recalculate secure prices/totals on the server.
11. Create an order or approval request.
12. Return mappings and per-record failures.
13. Delete successful queue entries.
14. Keep failed entries for retry or repair.
15. Retrieve rejected requests through `/api/pos/rejected-orders`.
16. Allow rejected orders to be corrected and resubmitted.

The backend protects this boundary with authentication, `order_create`, the `pos:access` ability, payload limits, DTO enforcement, secure math, payment/balance validation, UUID idempotency across orders and requests, server-generated order numbers, approval routing, cache invalidation, and partial-failure reporting.

Do not simplify this into ordinary frontend state or remove resilience features as cleanup.

## PWA and service worker

The Vue POS supports installability, service-worker registration, install prompts, persistent storage requests, online/offline status, IndexedDB persistence, local drafts, sync management, and background update checks.

POS API requests are intentionally handled by the synchronization engine, not treated as ordinary cached responses.

### Known item requiring approval before change

The Vue/PWA route is `/admin/pos`, and current service-worker scope configuration also references `/admin/pos/`. Browser verification is still required to confirm installed PWA and service-worker control behavior. Do not alter scope without approval.

Do not alter it without browser verification, generated service-worker/manifest inspection, confirmation of the desired deployment scope, and explicit approval. The route distinction itself must remain intact.

## Reports and management intelligence

Report areas include daily, expense, ledger, order, sales, tax, customer, and business-insights reports. The reporting system has received database aggregation, N+1 reduction, strict-mode SQL corrections, period comparisons, service breakdowns, customer aggregates, outstanding balances, and at-risk customer work.

Reports are permission-controlled.

## Branding and configuration

Master Settings includes application name, logo, favicon, phone, email, currency, tax percentage/mode, financial year, address, country, printer mode, and approval bypass limit.

Branding uses:

```text
storage/app/public
/storage/...
```

Service icons support upload, resize, select, delete, and protection against deleting an assigned icon.

## WhatsApp

Endpoints:

```text
GET  /whatsapp/webhook
POST /whatsapp/webhook
```

GET handles verification. POST verifies `X-Hub-Signature-256`, rejects invalid signatures, parses messages, extracts order references, checks sender authorization, looks up orders/customers, sends replies, handles privacy responses, and uses cache idempotency for duplicate delivery.

Processing is currently synchronous but protected and tested. Middleware extraction, verified payloads, and queued processing are deferred until retry, failure, duplicate-delivery, and observability rules are explicitly designed.

## Notifications and infrastructure

Some order notifications already use queued listeners, but there is no assumed Redis/Horizon operating model. Redis/Horizon requires operational evidence such as queue volume, latency, contention, or monitoring needs.

Reverb/collaborative POS requires a confirmed multi-terminal collaboration requirement, authorization channels, event design, and conflict policy. Real-time transport alone does not solve concurrent-edit conflicts.

## Development progression understood from Git

The system progressed from a Laravel/Livewire laundry-management foundation with customers, services, orders, roles, payments, and reports; through a Vue/PWA offline branch with Dexie, Pinia, Sanctum, API initialization, queues, and background synchronization; into operational hardening with UUID idempotency, rejected-order recovery, customer conflict handling, secure server math, DTOs, locked order sequences, payment validation, approvals, permissions, rate limits, session security, soft deletes, constraints, cache invalidation, and report optimization.

Recent stabilization added dependency/image remediation, public-storage normalization, customer/service/POS/branding/payment/report tests, and durable planning documents.

This is an evolving operational system, not an unstructured prototype. Existing resilience must be preserved.

## Confirmed change-control rules

Before consequential work:

1. Study current relevant code.
2. Study Git history where the behavior was introduced or changed.
3. Classify it as intentional, historical, or defective.
4. Explain impact and alternatives.
5. Ask the user when business or architectural clarity is required.
6. Never auto-confirm an assumption when the user’s answer could clarify faster.
7. Preserve both POS systems and their synchronization boundary.
8. Prefer focused, reversible changes.
9. Add/update tests before behavior changes where practical.
10. Run validation appropriate to the affected boundary.
11. Update this document and the roadmap when confirmed context changes.

Do not silently merge POS systems, rename/collapse routes, alter synchronization semantics, alter approvals/payment/ledger rules, change service-worker scope, introduce infrastructure, remove resilience, or claim browser behavior from backend tests alone.

## Open clarification questions

These questions are retained and should be raised only when a related consequential action requires them. Resolved questions should be marked resolved with the date and decision; new questions may be added as the system is studied.

### Operations and role usage

1. What is the exact role hierarchy among super-admins, managers, cashiers, field staff, and other staff?
2. Which staff normally use `/admin/pos` versus `/admin/online-pos`?
3. Should both POS modes be available to every authorized user, or should navigation differ by role/device/location?

### Offline data and conflicts

4. How stale may offline catalog, price, tax, and customer data be before refresh or re-authentication is required?
5. What happens when a service price changes while a device is offline?
6. What should happen when an offline customer phone exists but the user lacks `customer_edit`?
7. Should offline-entered customer details become an audit note, be rejected, or use the existing profile unchanged?
8. May an offline order requiring approval be printed, or must printing wait for approval?
9. What should the user see after repeated sync failures or token expiration?

### Orders, approval, and finance

10. What are the formal meanings of all order statuses, including returned, rejected, cancelled, and completed?
11. What are the rules for returns, cancellations, refunds, reversals, and ledger adjustments?
12. What is the exact approval policy, including bypass permissions, limits, and tax/discount basis?
13. Which historical payments may be edited, removed, or reversed?
14. Are partial payments allowed for every order type and status?

### Reports and exports

15. Which reports are required for each staff role?
16. Which download/print actions require `report_download`, `report_print`, or report-specific permissions?
17. What exact columns, filters, filename, and authorization rules are required for the unresolved customer Excel export?

### PWA deployment

18. What exact installed-app start destination and service-worker scope should users see for `/admin/pos` in each deployment environment?
20. Which environments must support installation and offline operation?

### WhatsApp and operations

21. Is WhatsApp only for order lookup/status, or will it support broader customer communication?
22. What are the required reply, retry, escalation, and failure behaviors?
23. What queue/worker deployment model is intended when asynchronous processing expands?

## Question-management rules

- Do not ask all questions at once when unrelated to the current task.
- Ask the relevant question immediately before a consequential decision that depends on it.
- Record confirmed answers in this document and remove them from the open list or mark them resolved.
- Add new questions when code/history reveals an ambiguity that could affect product behavior.
- Never convert an assumption into a confirmed rule without user confirmation.
