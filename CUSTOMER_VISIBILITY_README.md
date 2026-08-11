# Customer Visibility and Ownership Permissions

## Overview
This update introduces a granular, robust permissions system for **Customer Management** in TidyPOS, ensuring that customer data is securely scoped to specific users. It extends to both the online dashboard and the offline POS catalog.

## Scope of Modifications

### 1. Customer Ownership tracking
Every customer created in the system now strictly records the staff member who created it in the `created_by` column.
- **POS Inline Creation:** Secured (`ManagesCustomers.php`)
- **Offline POS Sync:** Secured (`PosApiController.php`)
- **Offline Orders Auto-Creation:** Secured (`SyncOfflineOrdersAction.php`)
- **Admin Dashboard Creation:** Secured (`CreateCustomerAction.php`)

### 2. Visibility Control
Staff members by default can only see customers they have created.
- **Admin Delegation:** Super Admins can explicitly grant specific staff members permission to view customers created by other specific staff members.
- **Super Admin Access:** Super Admins inherently bypass all filters, seeing the global customer list.

### 3. Null/Orphaned Customer Reassignment
Customers that existed prior to this update (where `created_by` is `NULL`) are securely hidden from standard staff.
- **Assignment Interface:** Super Admins have an exclusive "Assign to Staff" dropdown in the Customer Create and Edit modals, allowing them to explicitly claim or reassign these orphaned customers to specific staff members.

### 4. Query Architecture (Visibility Filters)
The system securely scopes queries at every customer data touchpoint using the `getViewableCustomerUserIds()` helper method on the `User` model:
- **`CustomersList.php`**: Scopes the main dashboard list.
- **`CustomerView.php`**: Protects direct URL access to a customer's profile via a 403 Forbidden abort.
- **`LedgerReport.php`**: Scopes the customer search in the reporting ledger.
- **`PosScreen.php`**: Scopes the active customer search on the online POS interface.
- **`PosApiController.php`**: Strict `cursorPaginate` scoping ensures the offline PWA never downloads unauthorized customer data to the local indexedDB.
- **`CustomersExport.php`**: Rebuilt the missing export class and applied scoping so Excel downloads cannot bypass the visibility rules.

## strict Audit Rules
These changes were executed under strict pre- and post-analysis protocols to ensure that all pagination cursors, downstream array maps, and API responses handled the scoped data dynamically without exceptions.
