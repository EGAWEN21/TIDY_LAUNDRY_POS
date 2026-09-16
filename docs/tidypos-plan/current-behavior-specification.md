# Current POS and Order Behavior Specification

Date: 2026-09-13  

Status: Phase 0 characterization baseline

This describes current behavior before hardening and Vue replacement. It is not an endorsement of every behavior. Unsafe and unresolved behavior is catalogued in `current-behavior-risk-register.md`.

## 1. Current surfaces

| Surface | Responsibility |
|---|---|
| Vue `/admin/pos` | New-order creation, offline drafts/customers, queue synchronization, sync-and-print. |
| Livewire `/admin/online-pos` | Alternate online new-order creation. |
| Livewire `/admin/pos/edit/{id}` | Existing-order editing. |
| Livewire `/admin/orders/requests/edit/{id}` | Pending/rejected request editing and resubmission. |
| Livewire back office | Lists, views, approval/rejection, payments, statuses, split/merge, recycle bin, receipts, reports. |
| Laravel actions/DTOs | Shared totals, secure pricing, persistence, synchronization, statuses, payments, split/merge. |

## 2. Access contract

- Vue/POS login requires valid credentials, active staff status, and `order_create`.
- Super administrators are exempt from the inactive-staff rejection.
- POS login replaces previous `pos-pwa` tokens and issues a `pos:access` token for 12 hours.
- POS APIs require Sanctum authentication and `AuthorizePosAccess`, which rechecks permission, active status, and token ability.
- New Livewire orders require `order_create`.
- Existing-order editing requires `order_edit`.
- Request editing allows `accept_reject_order`, `edit_pending_requests`, or ownership.
- Printing requires `order_print` or a valid signed URL.
- Order views use `order_view` and staff-order visibility.

## 3. Catalog and customers

- `/api/pos/init` returns active services/add-ons, service types/details, tax settings, financial year, currency, and timestamp.
- Vue loads IndexedDB first, then refreshes server data in the background.
- Customer catalog sync is cursor-paginated by 500 and applies `getViewableCustomerUserIds()`.
- Pending local customers survive catalog replacement.
- Customer sync resolves UUID first, then phone.
- Updating an existing customer requires `customer_edit`; otherwise standalone customer sync fails.
- Order graph sync may carry `new_customer`. It resolves by phone, updates only with `customer_edit`, or creates a new customer.
- When graph sync finds an existing customer but lacks edit permission, the order continues and an audit warning is appended to its note.

## 4. Cart and secure pricing

- Multiple service types become one composite cart row.
- Composite price is the sum of selected `service_details` prices.
- Details store service ID, type names, `service_type_ids`, quantity, price, total, and color.
- Historical details without type IDs use service-type-name lookup.
- Quantity must be positive.
- Add-ons contribute independently to total.
- `order_price_override` permits submitted service/add-on prices.
- Without that permission, the server reloads service/type and add-on prices.
- Negative prices are rejected.
- Without `order_discount_apply`, discount becomes zero.

## 5. Financial calculation

- Subtotal = sum of service price × quantity.
- Gross = subtotal + add-ons.
- Tax settings come from current master settings.
- Exclusive tax: tax is added to gross, then discount is subtracted.
- Inclusive tax: tax is extracted from gross, then discount is subtracted from gross.
- Final total cannot be below zero; returned values are rounded to two decimals.
- Secure math overwrites submitted totals before order/request persistence.
- Creation payments cannot be negative or exceed total.
- A positive unpaid balance requires a registered customer.

## 6. Vue creation and offline flow

1. Vue builds a payload with stable cart UUID, customer data, dates, details, add-ons, payments, and client totals.
2. It writes the payload to the authenticated user’s IndexedDB queue before requiring network access.
3. It clears the active cart after enqueueing.
4. Background or explicit sync sends queue rows in chunks of five.
5. Server converts each payload to `OrderData`, runs secure math/payment rules, and creates an order or request.
6. Directly created orders are removed from the queue.
7. Approval requests remain as `pending_approval`.
8. Sync-and-print prints only directly created orders.

## 7. Vue drafts and queue

- Dexie database: `TidyPOSDatabase`, schema version 7.
- Draft cart is keyed by user ID and stores cart UUID, items, add-ons, customer ID, discount, payments, and notes.
- Pinia mutations persist active draft state; an explicitly empty cart deletes it.
- Initialization hydrates local data before network synchronization.
- Queue synchronization is restricted to the current user’s rows.
- Legacy unowned rows are quarantined by migration and claimed during POS initialization.
- A two-minute Dexie lease lock coordinates tabs and renews every 30 seconds.
- HTTP 401 pauses for reauthentication without deleting work.
- HTTP 403/409/422 become permanent failures.
- Network errors, HTTP 429, and 5xx errors retry with exponential delay from 30 seconds to 15 minutes.
- More than five retries becomes a permanent failure requiring review.
- Catalog refresh pauses while a cart or actionable queue work exists.

## 8. Idempotency and approval routing

- Offline UUID is checked against both orders and requests under row locks.
- Existing order UUID returns the existing order.
- Existing pending request UUID returns the request without duplication.
- Existing rejected request UUID may be resubmitted or converted to an order.
- Approval is bypassed by `bypass_order_approval`, `accept_reject_order`, or `bypass_approval_under_limit` when secured total is within the configured limit.
- Otherwise a status-0 request stores secured payload, creator, customer summary, total, and UUID.
- Request numbers use the locked `request_number` sequence and `REQ-` prefix.

## 9. Authoritative order creation

- `CreateOrderAction` uses a database transaction.
- Order numbers use a locked sequence and `ORD-` prefix.
- Client status is ignored; new orders start at status 0.
- Header, details, add-ons, and payments are inserted transactionally.
- Financial year derives from effective order/payment date.
- `OrderSuccessfullyCreated` is dispatched and dashboard count caches are invalidated.
- Its queued listener currently sends creation SMS and logs/suppresses failures.

## 10. Request lifecycle

### Rejection

- Requires `accept_reject_order`.
- Reason is required and limited to 1000 characters.
- Status becomes 2; both rejection fields receive the reason.
- Creator receives a system notification.
- Rejected-request API returns only the authenticated creator’s requests.

### Resubmission

- Livewire reconstructs customer, details, payments, add-ons, dates, note, and discount.
- Resubmission updates the same request, resets status to 0, and clears rejection fields.
- Offline resync with the same UUID resurrects a rejected request.
- If the user can now bypass approval, the rejected request is deleted and an order is created.

### Acceptance

- Requires `accept_reject_order`.
- Stored payload becomes `OrderData` and is passed to `CreateOrderAction`.
- Acceptance time overrides order date; original request creation time becomes `requested_at`.
- UUID is copied to the order and the request is deleted.

## 11. Existing-order editing

- Loads stored customer, details, add-ons, dates, note, discount, and payments.
- Existing payment entries carry `payment_id` and cannot be removed in the Livewire UI.
- Secure math runs before update.
- `UpdateOrderAction` transactionally updates the header/totals.
- Existing details/add-ons are force-deleted and recreated.
- Existing payment rows remain.
- Submitted payment entries without `payment_id` create additional payments.

## 12. Payments and refunds

- `ProcessPaymentAction` computes balance from order total minus non-deleted payments.
- When the customer owes money, negative payments and overpayments are rejected.
- When the store owes money, positive payments are rejected and a negative refund up to the owed amount is allowed.
- Positive payments are rejected for returned/voided status 4.
- Payment/refund creates an append-only row dated today, using today’s financial year.

## 13. Status transitions

Allowed transitions:

- 0 → 1, 2, 3, 4
- 1 → 0, 2, 3, 4
- 2 → 0, 1, 3, 4
- 3 → 4
- 4 → none

Additional behavior:

- Non-super-admin users cannot mark an unpaid order delivered (3).
- Status change invalidates dashboard caches.
- It triggers status email, optional automated WhatsApp or manual `wa.me`, and SMS.
- Bulk status changes suppress manual WhatsApp URL opening.

## 14. Split and merge

### Split

- Requires `order_split` in the UI.
- At least one but not all detail rows must move.
- Existing payments must be allocated exactly between original/new orders.
- Allocations cannot be negative or exceed recalculated totals.
- Selected details move to a new order.
- Add-ons and discount remain on the original.
- Payments are moved or split to satisfy allocation.

### Merge

- Requires `order_merge` in the UI and at least two selected orders.
- All orders must have the same customer ID.
- Details, add-ons, and payments move to the primary order.
- Discounts are summed and totals recalculated.
- Secondary orders are permanently force-deleted.

## 15. Delete, restore, and receipts

- Order deletion soft-deletes order, details, add-ons, and payments and records `deleted_by` on the order.
- Recycle-bin restoration restores the order and all those child rows transactionally.
- Authorized force deletion permanently removes all child rows and the order.
- Order print accepts order ID or UUID and requires `order_print` unless URL signature is valid.
- Back office can email a receipt, send creation SMS, or generate a WhatsApp link containing a signed receipt URL.

## 16. Current automated evidence

Current tests cover portions of:

- totals for inclusive/exclusive tax and legacy arrays;
- DTO mapping;
- transactional order creation and negative-payment rejection;
- secured API access and valid offline sync;
- approval-request numbering and rejected reason retrieval;
- Livewire totals, draft creation, payment bounds, and authorization;
- POS middleware, inactive users, token/session revocation, and throttling;
- storage isolation and PWA service-worker lifecycle.

Coverage is not yet complete for concurrency, retries, idempotency races, edit conflicts, request acceptance math, split/merge, refunds, restoration, and notification deduplication.
