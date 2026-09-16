# Legacy Livewire POS Dependency Map

Date: 2026-09-13
Status: Phase 0 baseline inventory
Scope: Dependencies that must be understood before replacing and deleting the Livewire POS.

## Classification

- **Legacy-only**: Used to implement the Livewire POS and eligible for removal after replacement acceptance.
- **Shared**: Used outside the Livewire POS or forms authoritative domain behavior; must remain or be deliberately refactored.
- **Decision-required**: Data or behavior needs an approved migration, compatibility, or retention policy before removal.

## Entry points and navigation

| Dependency | Classification | Consumers and behavior | Deletion prerequisite |
|---|---|---|---|
| `App\Livewire\Orders\PosScreen` | Legacy-only | Handles new orders, existing-order edits, and request edits. Bound to three routes. | Vue creation and both Vue editors accepted; routes no longer reference the component. |
| `orders.online-pos` (`/admin/online-pos`) | Legacy-only | Explicit Livewire POS navigation target and Playwright smoke target. | Remove or deliberately redirect after Vue acceptance and URL compatibility decision. |
| `orders.pos.edit` (`/admin/pos/edit/{id}`) | Legacy-only implementation; URL retained | Currently loads `PosScreen`; linked from `orders-list.blade.php`. Final architecture assigns this URL to Vue online editing. | Vue order editor passes authorization, validation, conflict, payment, and E2E acceptance tests. |
| `orders.requests.edit` (`/admin/orders/requests/edit/{id}`) | Legacy-only implementation; URL retained | Currently loads `PosScreen`; linked from request list and request detail views. | Vue request editor passes ownership/permission, rejected/pending, and resubmission tests. |
| `orders.pos` (`/admin/pos`) | Shared replacement entry | Vue/PWA creation surface; linked from navbar, sidebar, and order list. | Retain. Verify links intentionally target Vue creation. |
| Sidebar “Online POS” link | Legacy-only | `resources/views/livewire/components/sidebar.blade.php` links to `orders.online-pos`. | Remove after replacement acceptance; preserve normal Vue POS link. |
| Order/request edit links | Shared navigation with legacy targets | `orders-list.blade.php`, `order-requests-list.blade.php`, and `view-order-request.blade.php`. | Keep links, change only their implementation when Vue editor routes are ready. |

## Livewire UI implementation

| Dependency | Classification | Consumers and behavior | Deletion prerequisite |
|---|---|---|---|
| `resources/views/livewire/orders/pos-screen.blade.php` | Legacy-only | Cart, customer search/create, service/type selection, add-ons, discount, payments, save/cash/clear controls, and print/reload listeners. | Vue editors cover every accepted workflow and browser acceptance passes. |
| `resources/views/components/layouts/pos.blade.php` | Decision-required | Used by `PosScreen`; also contains shared PWA connectivity, theme, Toastr, modal, alert, and reload handlers. | Confirm no non-legacy component uses it; move any still-required shared behavior before deletion. |
| `ManagesCart` | Legacy-only orchestration | Livewire state mutation, composite service selection, price loading, quantity, duplicate/remove, legacy type-name fallback. | Vue/API preserve accepted cart and historical type behavior. |
| `ManagesCustomers` | Legacy-only orchestration | Creates/selects customers directly from Livewire state. | Vue uses authorized customer APIs/actions with approved identity reconciliation. |
| `ManagesPayments` | Legacy-only orchestration | Adds/removes in-memory payments and calculates balance. Prevents deleting a payment when `payment_id` exists. | Shared server validation and Vue payment behavior accepted. |
| Livewire events `closemodal`, `alert`, `reloadpage` | Legacy-only where emitted by POS | Bootstrap modal cleanup, Toastr messages, and page reload. | No active replacement workflow depends on these POS emissions. |
| Livewire events `printPage` and `printPageOrder` | Legacy-only dispatch; receipt target shared | Opens receipt printing after create/update. | Vue editors invoke the accepted shared receipt/print flow. |

## Drafts and persisted state

| Dependency | Classification | Consumers and behavior | Deletion prerequisite |
|---|---|---|---|
| `App\Models\PosDraft` | Decision-required | Read, updated, and deleted only by `PosScreen`; tests directly cover automatic saving. Payload is user-scoped Livewire component state. | Approve D-002: conversion, export, recovery, retention, and deletion policy. |
| `pos_drafts` table and migration | Decision-required | Stores `user_id` plus unversioned long-text JSON payload; no foreign key or unique constraint is declared in the migration. | Inventory live rows, execute approved recovery/retention plan, then remove in a later reversible migration. |
| Vue Dexie/IndexedDB drafts and `syncQueue` | Shared replacement state | Used by Vue creation/offline synchronization; separate from `PosDraft`. | Retain and protect through upgrade/version tests. Never delete as part of Livewire cleanup. |

## Shared domain and persistence dependencies

These are not legacy POS code even though `PosScreen` consumes them.

| Dependency | Classification | Other consumers / reason to retain |
|---|---|---|
| `OrderData`, `CartItemData`, payment/add-on DTOs | Shared | Used by actions, API synchronization, request approval, and unit tests. |
| `CalculateCartTotals` | Shared | Used by Livewire and unit-tested as central total calculation. Replacement clients must match server results. |
| `CalculateSecureOrderMathAction` | Shared | Server authority for price/permission-sensitive recalculation. |
| `CreateOrderAction` | Shared | Used by Livewire creation, request approval, API synchronization, and feature tests. Owns transactional persistence and numbering. |
| `UpdateOrderAction` | Shared but requires hardening | Existing-order persistence. Recreates details/add-ons, preserves existing payments, and appends payments without `payment_id`. |
| `SyncOfflineOrdersAction` | Shared | Vue offline queue synchronization and idempotency behavior. |
| `Order`, `OrderDetail`, `OrderAddonDetail`, `Payment`, `OrderRequest` | Shared | Back office, reports, receipts, approvals, synchronization, and accounting data. |
| `Customer` and customer actions | Shared | Back office and both POS implementations. Identity/visibility policy remains unresolved. |
| `Service`, `ServiceDetail`, `ServiceType`, `Addon` | Shared | Catalog and pricing inputs for both implementations. |
| `OrderSuccessfullyCreated` and notification listeners | Shared | Downstream email/SMS/WhatsApp behavior after authoritative creation. |
| `SystemNotification` | Shared | Request workflow and back-office notifications. Requires deduplication/event policy. |
| Receipt and print views/components | Shared | Used outside POS creation and must survive legacy removal. |
| Permissions and visibility helpers | Shared | `order_create`, `order_edit`, `accept_reject_order`, `edit_pending_requests`, `view_all_requests`, bypass permissions, customer visibility, and printing. |

## Existing workflow coupling captured from `PosScreen`

### New order

1. Requires `order_create`.
2. Loads active services/add-ons and current tax settings.
3. Restores `PosDraft` by authenticated user.
4. Builds `OrderData`, runs secure math, and either creates an order or an approval request.
5. Clears the draft after successful completion and optionally dispatches printing.

### Existing-order edit

1. Requires `order_edit`.
2. Loads order details, add-ons, customer, and historical payments.
3. Existing payments carry `payment_id` and cannot be removed in the UI trait.
4. `UpdateOrderAction` replaces details/add-ons, preserves existing payment rows, and inserts only payments without IDs.

### Request edit/resubmission

1. Permits managers, users with `edit_pending_requests`, or the request creator.
2. Loads the request payload and supports both `service_type_ids` and legacy service-name lookup.
3. Resubmission updates status to pending and clears both rejection fields.
4. A bypass-capable user can create an order and delete the request.

## Known risks and behavior requiring decisions

1. **Draft retention:** Livewire `PosDraft` has no approved conversion or recovery policy.
2. **Direct customer creation:** `ManagesCustomers` writes `Customer` directly rather than using a shared customer action.
3. **Customer selection:** `selectCustomer()` fetches by ID without applying the search visibility scope again.
4. **Request permissions:** request edit allows `accept_reject_order`, `edit_pending_requests`, or ownership; this must be explicitly approved for the new API.
5. **Historical pricing:** edit reconstruction mixes stored prices, current tax percentage, and legacy type-name reverse lookup.
6. **Payment contract:** historical rows are preserved, but aggregate overpayment and stale/concurrent edits require server-side policy and locking.
7. **Request acceptance date:** acceptance uses the current date and stores original request time separately.
8. **Request deletion:** accepted requests and bypass-created edited requests are deleted, so audit/soft-delete expectations must be approved.
9. **Notification duplication:** request notifications and order-created downstream notifications need an explicit event/deduplication contract.
10. **Layout external dependency:** the POS layout loads ApexCharts from a public CDN, which needs an offline/security decision if the layout remains.

## Tests tied to the legacy implementation

| Test | Classification | Replacement action |
|---|---|---|
| `tests/Feature/Livewire/PosScreenTest.php` | Legacy-specific with behavioral value | Keep until Vue/API tests cover totals, draft behavior, payment validation, and negative authorization; then remove component-specific assertions. |
| Playwright online Livewire POS smoke | Legacy-specific | Remove only after Vue editors are accepted and URL behavior is deliberate. |
| `CalculateCartTotalsTest`, `OrderDataTest`, `CreateOrderActionTest` | Shared | Retain and expand. |
| Sync API and security middleware tests | Shared | Retain and expand for edit/request APIs. |

## Removal gate

Do not delete the Livewire POS until all conditions pass:

- Vue creation, existing-order editing, and request editing are accepted end to end.
- Dedicated APIs enforce permissions, visibility, validation, secure math, locking, and idempotency.
- Existing and historical payments cannot be duplicated or silently removed.
- `PosDraft` rows have been inventoried and handled under an approved policy.
- Printing, receipts, notifications, reports, ledgers, approvals, and old URLs are verified.
- Navigation no longer exposes `orders.online-pos` as a separate workflow.
- Repository search finds no active references to `PosScreen`, its traits, POS Blade view, or POS-only events.
- The `pos_drafts` table is removed only in a separate reversible migration after the retention window.
