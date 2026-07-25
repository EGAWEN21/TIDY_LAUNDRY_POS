# TidyPOS Browser and Manual Acceptance Checklist

Last reviewed: 2026-07-24

This is the executable manual-validation plan. Backend tests, route listings, and successful builds do not count as browser acceptance.

## Intentional POS routes

- `/admin/online-pos`: Livewire online POS. Immediate backend interaction under stable network.
- `/admin/pos`: Vue/PWA offline POS. Local IndexedDB operation with later synchronization.

These are intentionally separate systems. Test them separately; do not treat their route distinction as a defect or merge target.

## Prerequisites

- Use disposable local/staging data, never production data.
- Confirm migrations, seed data, and `public/storage` are available.
- Run `npm run build` before PWA testing.
- Use Chromium with DevTools.
- Prepare super-admin and restricted staff accounts with known permissions.
- Prepare active customer, service, service type, addon, order, payment, and test image data.
- Use mock/staging WhatsApp credentials only.

For every item record: ID, date, browser/version, URL/environment, user/permissions, steps, expected result, actual result, pass/fail, and screenshot/console/network evidence.

## A. Authentication and sessions

- [ ] A01 Valid super-admin login reaches dashboard.
- [ ] A02 Invalid credentials remain unauthenticated and show a safe error.
- [ ] A03 Inactive staff cannot access protected pages.
- [ ] A04 Restricted staff sees only permitted navigation.
- [ ] A05 Second staff login behaves according to single-session policy.
- [ ] A06 Logout invalidates the browser session.
- [ ] A07 Refresh after logout redirects safely.
- [ ] A08 POS API token expiry/revocation is handled without losing queued offline data.

## B. Authorization and direct URLs

- [ ] B01 Super-admin can access both POS systems, customers, services, payments, reports, and settings.
- [ ] B02 Staff without `order_create` cannot access `/admin/online-pos`.
- [ ] B03 Staff without `order_create` cannot use `/admin/pos` or POS API endpoints.
- [ ] B04 Staff without `order_edit` cannot edit an existing online order.
- [ ] B05 Staff without `payment_list` cannot access payment receipts.
- [ ] B06 Staff without the relevant report permission cannot access that report URL.
- [ ] B07 Direct URL denial works even when navigation links are hidden.
- [ ] B08 Browser and API authorization behavior is consistent.

## C. Online Livewire POS: `/admin/online-pos`

- [ ] C01 Page loads current server catalog under stable network.
- [ ] C02 Customer search works by name and phone.
- [ ] C03 New customer validation and selection work.
- [ ] C04 Service selection loads service types and official prices.
- [ ] C05 Add, increase, decrease, duplicate, and remove cart items work.
- [ ] C06 Addons, discounts, tax, subtotal, total, and balance are correct.
- [ ] C07 Unauthorized price override is prevented.
- [ ] C08 Unauthorized discount application is prevented.
- [ ] C09 Partial payments retain type, amount, and notes.
- [ ] C10 Overpayment is rejected.
- [ ] C11 Unpaid balance requires registered customer.
- [ ] C12 Server-side draft saves and restores as expected.
- [ ] C13 Authorized staff can submit a direct order.
- [ ] C14 Approval-required staff submits a request, not a direct order.
- [ ] C15 Authorized staff can edit an order.
- [ ] C16 Unauthorized direct edit URL is denied.
- [ ] C17 Authorized payment/print action works.
- [ ] C18 Refresh preserves correct order/payment/customer state.

## D. Offline Vue/PWA POS: `/admin/pos`

- [ ] D01 Vue POS loads online and displays online status.
- [ ] D02 `/api/pos/init` loads catalog, types, details, addons, customers, and settings.
- [ ] D03 Initial data is available from IndexedDB after refresh.
- [ ] D04 Local customer search works.
- [ ] D05 Local service/type selection works.
- [ ] D06 Local cart quantities, colors, addons, discounts, tax, and totals work.
- [ ] D07 Current-user cart draft survives refresh as designed.
- [ ] D08 New customer is stored locally and queued.
- [ ] D09 Offline order receives UUID and is queued.
- [ ] D10 Cash and partial-payment payloads are correct.
- [ ] D11 Unpaid offline order requires registered customer.
- [ ] D12 Offline status is visible to staff.
- [ ] D13 Sync Manager shows pending/error entries.
- [ ] D14 Error entries show retry count and message.
- [ ] D15 Failed order can be loaded for correction.
- [ ] D16 Rejected orders display manager rejection reason.
- [ ] D17 Corrected rejected order can be re-queued.

## E. Synchronization boundary

- [ ] E01 Disable network and create customer/order at `/admin/pos`.
- [ ] E02 Confirm records remain in IndexedDB/Sync Manager while offline.
- [ ] E03 Restore network and confirm synchronization starts.
- [ ] E04 Confirm customer queue uses `/api/pos/sync-customers`.
- [ ] E05 Confirm order queue uses `/api/pos/sync-orders`.
- [ ] E06 Confirm server creates customer/order or approval request appropriately.
- [ ] E07 Confirm successful queue entries are removed once.
- [ ] E08 Confirm server order number mapping is returned and used correctly.
- [ ] E09 Repeat the same UUID and confirm no duplicate order/request.
- [ ] E10 Confirm one invalid item does not roll back valid batch items.
- [ ] E11 Confirm failed items remain visible with useful errors.
- [ ] E12 Confirm token expiry does not silently delete queued data.
- [ ] E13 Confirm approval-required offline order becomes approval request.
- [ ] E14 Confirm authorized bypass order becomes direct order when policy allows.
- [ ] E15 Confirm existing-phone customer conflict follows approved behavior.
- [ ] E16 Confirm stale offline pricing follows approved synchronization policy.

## F. Services and icons

- [ ] F01 Authorized staff can create service with icon, type, and price.
- [ ] F02 Missing icon/type validation is visible.
- [ ] F03 Authorized staff can edit service name, icon, state, and details.
- [ ] F04 Replacing details leaves no stale detail rows.
- [ ] F05 Icon upload accepts intended image formats/sizes.
- [ ] F06 Uploaded icon displays in picker and service screens.
- [ ] F07 Assigned icon cannot be deleted.
- [ ] F08 Unused icon can be deleted.

## G. Branding and storage

- [ ] G01 Authorized staff can upload logo and favicon.
- [ ] G02 Branding remains visible after refresh.
- [ ] G03 Header/sidebar uses uploaded logo.
- [ ] G04 Browser tab uses uploaded favicon.
- [ ] G05 URLs use `/storage/...`, not filesystem paths.
- [ ] G06 Existing branding remains when no replacement is uploaded.
- [ ] G07 Invalid/oversized branding files are rejected safely.

## H. Payments, reports, and printing

- [ ] H01 Authorized staff can view payment receipts.
- [ ] H02 Receipt search works by customer name/phone.
- [ ] H03 Order report loads default dates.
- [ ] H04 Sales report loads metrics.
- [ ] H05 Customer report loads aggregates.
- [ ] H06 Report filters update results.
- [ ] H07 Authorized report download/print produces expected output.
- [ ] H08 Unauthorized report download/print is blocked.
- [ ] H09 Customer Excel export remains tracked as unresolved until its export class/requirements are defined.
- [ ] H10 Real browser printing works; HTTP/backend tests alone are insufficient evidence.

## I. PWA and offline browser behavior

- [ ] I01 `/manifest.json` returns valid manifest JSON and expected name.
- [ ] I02 Manifest icon URLs return accessible images.
- [ ] I03 `/sw.js` loads with JavaScript content type.
- [ ] I04 Browser offers installation in an eligible secure/local context.
- [ ] I05 Installed app opens the intended Vue/PWA POS entry point `/admin/pos`.
- [ ] I06 Confirm `/admin/pos` and `/admin/online-pos` remain separate in navigation and behavior.
- [ ] I07 Confirm service-worker scope controls the intended Vue/PWA route; do not change scope without approval.
- [ ] I08 POS catalog loads online.
- [ ] I09 Disconnect network and confirm supported POS operations remain usable.
- [ ] I10 Create offline order and confirm pending queue entry.
- [ ] I11 Reconnect and confirm exactly-once synchronization behavior.
- [ ] I12 Confirm rejected sync responses remain visible.
- [ ] I13 Confirm service worker does not interfere with `/api/pos/*` synchronization requests.

## J. WhatsApp integration

- [ ] J01 Valid GET verification token returns challenge.
- [ ] J02 Invalid GET token returns 403.
- [ ] J03 Missing/invalid POST signature returns 403.
- [ ] J04 Valid signature for minimal payload returns success.
- [ ] J05 Duplicate delivery does not produce duplicate processing/replies.
- [ ] J06 Valid authorized order lookup produces expected staging/mock reply.
- [ ] J07 Unauthorized sender receives privacy response.
- [ ] J08 Malformed/oversized payload is handled safely.
- [ ] J09 Record response latency and external API failures.
- [ ] J10 Do not introduce queued processing until separately approved with retry/idempotency rules.

## K. Browser quality and release rules

- [ ] K01 No unexpected console errors in tested workflows.
- [ ] K02 No unexplained failed XHR/fetch requests.
- [ ] K03 Refresh/back/forward navigation behaves safely.
- [ ] K04 Test desktop and narrow/mobile viewport.
- [ ] K05 Uploaded images have no broken links.
- [ ] K06 Unauthorized content is not exposed through browser history.
- [ ] K07 Print/download is tested in a real browser.

Release rules:

- Security, data loss, duplicate order/payment, and broken synchronization failures block release.
- Cosmetic failures may be triaged separately only if core workflows remain usable.
- Do not begin componentization or WhatsApp queue work solely because a manual test fails; classify the result first as product defect, environment issue, or approved architecture work.
