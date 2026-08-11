# User Role & Permission System — Implementation Plan

This plan details the implementation of the granular Role & Permission system defined in `USER_ROLE_PLAN.md` using `spatie/laravel-permission`.

## 1. Overview & Architecture

We will implement a 5-role RBAC system:
1. `admin`: System owner with full unrestricted access (via `Gate::before` bypass).
2. `shop_manager`: Day-to-day manager operating repairs, inventory catalog, customers, invoices, and limited staff management (`technician`, `inventory_staff`, `cashier`).
3. `technician`: Repair staff restricted to their assigned repair jobs, unable to manage parts catalog or finances.
4. `inventory_staff`: Manages stock, suppliers, and purchase orders, with no access to customer data, repairs, or invoices.
5. `cashier`: Front-of-house staff dealing directly with customers during intake and checkout. Handles creating new job orders/tickets, assigning tickets to technicians, billing, invoices, payments, and customer walk-in intake.

---

## 2. Permissions List (37 Permissions)

- **Dashboard**: `dashboard.view.own`, `dashboard.view.all`, `dashboard.view.inventory`, `dashboard.view.sales`
- **Customers**: `customers.view`, `customers.manage`
- **Repairs**: `repairs.view.own`, `repairs.view.status`, `repairs.manage`, `repairs.assign`
- **Diagnosis**: `diagnosis.manage`
- **Estimation**: `estimation.manage.full`, `estimation.manage.limited`, `estimation.view`
- **Technicians**: `technicians.view.own`, `technicians.view.availability`, `technicians.manage`
- **Jobs**: `jobs.create`, `jobs.manage.full`
- **Invoices**: `invoices.manage`
- **Parts**: `parts.catalog.manage`, `parts.usage.create`, `parts.usage.view`
- **Suppliers**: `suppliers.manage`, `suppliers.view`
- **Warranty**: `warranty.manage`, `warranty.claim`, `warranty.view`
- **Notifications**: `notifications.trigger.inventory`, `notifications.trigger.customer`
- **Reports**: `reports.view.own`, `reports.view.financial`, `reports.view.inventory`, `reports.view.sales`
- **Users**: `users.manage.full`, `users.manage.limited`
- **Audit & Backups**: `audit.view`, `backup.manage`

---

## 3. Extra Scoping Rules & Role Adjustments

1. **Cashier Ticket Intake & Assignment**: Since cashiers deal directly with walk-in customers at intake, cashiers are granted `jobs.create` and `repairs.assign` so they can register customer devices, create repair tickets, and assign them directly to technicians. To make that assignment an informed decision rather than a guess, cashiers are also granted `technicians.view.availability` — a read-only, scoped view showing technician name + current open-job count only (not full profiles, performance data, or contact info, which remain behind `technicians.manage`).
2. **Technician Job Scope — centralized in a Policy, not per-controller**: `repairs.manage`, `parts.usage.create`, `diagnosis.manage`, and job-scoped actions in `ProgressUpdateController` / `PhotoCommentController` all need the same rule: a technician may only act on a repair job where `job_order.technician_id === auth()->id()`. Rather than re-implementing this check in five separate controllers (risk: someone adds a sixth endpoint later and forgets it), this is centralized in a single `RepairJobPolicy`:
   ```php
   class RepairJobPolicy
   {
       public function manage(User $user, JobOrder $job): bool
       {
           if ($user->hasAnyRole(['admin', 'shop_manager'])) {
               return true;
           }
           return $user->can('repairs.manage') && $job->technician_id === $user->id;
       }
   }
   ```
   Every controller action that touches a specific job order calls `$this->authorize('manage', $jobOrder)` instead of hand-rolling the `technician_id` comparison inline.
3. **Cashier Customer Scope**: Cashiers with `customers.manage` can perform customer creation and basic profile edits during intake, while restricting alteration of overall warranty policies or internal device history.
4. **Shop Manager User Limits**: Shop managers with `users.manage.limited` can only create/update users assigned to roles `technician`, `inventory_staff`, or `cashier`, blocking management of `admin` or `shop_manager` accounts.
5. **Admin Bypass**: `Gate::before(fn ($user, $ability) => $user->hasRole('admin') ? true : null);` in `AppServiceProvider`.
6. **Authorization layer — controller-only, no route middleware (deliberate)**: Authorization is enforced via `$this->authorize()` / Policy calls inside controllers only. Route-level `permission:` middleware is intentionally skipped — for a shop of this size, one enforcement layer is sufficient, and controller-level keeps the technician job-scope logic (which needs the model, not just the route) in one place. Revisit this only if the API surface grows enough that unauthenticated route exposure becomes a real risk on its own.

---

## User Review Required

> [!IMPORTANT]
> - Existing database roles (e.g. `Administrator`, `Shop Manager`) will be migrated to standard lower-snake slugs (`admin`, `shop_manager`, `technician`, `inventory_staff`, `cashier`) so permission matrix checks remain clean and unified across code and DB. **This is now a one-off Artisan command (`roles:migrate-legacy`), not a schema migration** — renaming/reassigning existing rows inside a migration file is fragile if seeder run order ever changes; a command you run once by hand is easier to verify and re-run safely if something goes wrong mid-deploy.
> - Cashiers will now have permissions `jobs.create` and `repairs.assign` enabled to support front-desk customer intake and ticket hand-off to technicians, **plus the new `technicians.view.availability` permission** so assignment is based on actual open-job counts, not guesswork.
> - Technician job-scoping (`technician_id === auth()->id()` checks) is now centralized in a single `RepairJobPolicy` instead of being reimplemented inside `JobOrderController`, `DiagnosisController`, `ProgressUpdateController`, and `PhotoCommentController` separately — reduces the chance a future new endpoint forgets the check.

---

## Proposed Changes

### Database & Seeders

#### [MODIFY] [DatabaseSeeder.php](file:///home/kaiden/Documents/iRepairShopSystem/database/seeders/DatabaseSeeder.php)
- Define all 36 exact permission strings.
- Define the 5 exact roles (`admin`, `shop_manager`, `technician`, `inventory_staff`, `cashier`).
- Assign permissions to roles matching the role-permission matrix (including `jobs.create` and `repairs.assign` for `cashier`).
- Assign roles to seeded users (`admin@irepair.com`, `manager@irepair.com`, `cashier@irepair.com`, etc.).

#### [NEW] [MigrateLegacyRoles.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Console/Commands/MigrateLegacyRoles.php)
- Artisan command `php artisan roles:migrate-legacy`, idempotent (safe to re-run).
- Maps existing legacy role names (`Administrator`, `Shop Manager`, etc.) to the new slugs and reassigns affected users.
- **Run order**: this command runs *before* `RolePermissionSeeder`, so the new slugged roles it creates are the same rows the seeder attaches permissions to via `firstOrCreate`. Running the seeder first would create a duplicate `admin` role with no legacy users attached, orphaning existing accounts.
- Logs a summary of how many users were reassigned per role for manual verification before going further.

---

### Core Providers & Authorization Hooks

#### [MODIFY] [AppServiceProvider.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Providers/AppServiceProvider.php)
- Register `Gate::before(fn($user, $ability) => $user->hasRole('admin') ? true : null);`.
- Register `RepairJobPolicy` against the `JobOrder` model in `$policies`.

#### [NEW] [RepairJobPolicy.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Policies/RepairJobPolicy.php)
- Single source of truth for "can this user act on this specific job order."
- `manage()` method: `admin`/`shop_manager` always pass; otherwise requires `repairs.manage` permission AND `job_order.technician_id === auth()->id()`.
- Consumed via `$this->authorize('manage', $jobOrder)` from every controller listed below that previously would have hand-rolled the same check — this is the fix for the original plan reimplementing the same scoping logic four separate times.

---

### Controllers & Business Scoping Rules

#### [MODIFY] [JobOrderController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/JobOrderController.php)
- Add permission authorization checks (`repairs.view.own`, `repairs.view.status`, `jobs.create`, `repairs.manage`, `repairs.assign`, `parts.usage.create`, `estimation.manage.full`, `estimation.manage.limited`, `jobs.manage.full`).
- `jobs.manage.full` gates post-intake edits on an existing job order (due date changes, reassignment, metadata edits) — `admin`/`shop_manager` only. `jobs.create` (cashier included) only covers initial ticket creation; it does not grant edit rights afterward.
- Allow `cashier` with `jobs.create` and `repairs.assign` to access job creation and technician assignment during customer intake, using `technicians.view.availability` data to inform the assignment.
- Filter `index` query by `technician_id` when user only has `repairs.view.own`.
- Replace the per-action inline `technician_id` checks with `$this->authorize('manage', $jobOrder)` calls (`updateStatus`, `addPart`, `removePart`, `uploadPhoto`, etc.), delegating to `RepairJobPolicy`.

#### [MODIFY] [CustomerController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/CustomerController.php)
- Authorize `customers.view` and `customers.manage`.
- Enforce Cashier scope restriction on customer forms/updates.

#### [MODIFY] [DeviceController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/DeviceController.php)
- Authorize `customers.view` for listing/viewing and `customers.manage` or `jobs.create` for creating/editing devices.

#### [MODIFY] [DiagnosisController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/DiagnosisController.php)
- Authorize `diagnosis.manage` and enforce technician job scope via `$this->authorize('manage', $jobOrder)` (`RepairJobPolicy`), not an inline check.

#### [MODIFY] [InventoryController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/InventoryController.php)
- Authorize `parts.usage.view` for index/show and `parts.catalog.manage` for stock adjustments, creating, editing, and deleting parts.

#### [MODIFY] [SupplierController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/SupplierController.php)
- Authorize `suppliers.view` and `suppliers.manage`.

#### [MODIFY] [TechnicianController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/TechnicianController.php)
- Authorize `technicians.view.own` for technicians viewing their own profile and `technicians.manage` for full technician management.
- Add an `availability()` endpoint (or lightweight scope on `index`) gated by `technicians.view.availability`, returning only `{ id, name, open_job_count }` — used by the cashier's assignment picker. Must not expose fields covered by `technicians.manage` (contact info, performance metrics).

#### [MODIFY] [InvoiceController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/InvoiceController.php)
- Authorize `invoices.manage` across all endpoints.

#### [MODIFY] [WarrantyController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/WarrantyController.php)
- Authorize `warranty.view`, `warranty.claim`, and `warranty.manage`.

#### [MODIFY] [ReportController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/ReportController.php)
- Authorize and filter metrics according to report permissions (`reports.view.own`, `reports.view.financial`, `reports.view.inventory`, `reports.view.sales`).

#### [MODIFY] [UserController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/UserController.php)
- Authorize `users.manage.full` or `users.manage.limited`.
- Implement Shop Manager restriction: forbid creating/editing/assigning `admin` or `shop_manager` roles if user only has `users.manage.limited`.

#### [MODIFY] [AuditLogController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/AuditLogController.php)
- Authorize `audit.view`.

#### [MODIFY] [BackupController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/BackupController.php)
- Authorize `backup.manage`.

#### [MODIFY] [ProgressUpdateController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/ProgressUpdateController.php)
- Authorize `repairs.manage`; job scope enforced via `$this->authorize('manage', $jobOrder)` (`RepairJobPolicy`), not restated inline.

#### [MODIFY] [PhotoCommentController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/PhotoCommentController.php)
- Authorize `repairs.manage`; job scope enforced via `$this->authorize('manage', $jobOrder)` (`RepairJobPolicy`), not restated inline.

#### [MODIFY] [DashboardController.php](file:///home/kaiden/Documents/iRepairShopSystem/app/Http/Controllers/DashboardController.php)
- Tailor visible widgets based on `dashboard.view.all`, `dashboard.view.own`, `dashboard.view.inventory`, and `dashboard.view.sales`.

---

### Navigation & UI Blade Directives

#### [MODIFY] [app.blade.php](file:///home/kaiden/Documents/iRepairShopSystem/resources/views/layouts/app.blade.php)
- Update navigation sidebar using `@can` checks for modular items (enabling "New Repair Ticket" & "Customers" for cashiers).

#### [MODIFY] Action buttons across views
- Wrap action buttons (`Edit`, `Delete`, `Assign Technician`, `Adjust Stock`, `Add Part`, `Create Invoice`, etc.) with appropriate `@can` / `@canany` directives in views:
  - `job_orders/index.blade.php`, `job_orders/show.blade.php`
  - `inventory/index.blade.php`, `inventory/show.blade.php`
  - `customers/index.blade.php`, `customers/show.blade.php`
  - `users/index.blade.php`, `users/create.blade.php`, `users/edit.blade.php`
  - `suppliers/index.blade.php`
  - `invoices/index.blade.php`, `invoices/show.blade.php`

---

## Verification Plan

### Automated Tests
- Run `php artisan test --filter=UserRoleAndPermissionTest`
- Test cases will cover:
  1. `admin` user bypasses all gate checks.
  2. `cashier` user can create a repair ticket (`jobs.create`) and assign it to a technician (`repairs.assign`) upon customer intake, can view technician availability (`technicians.view.availability`, and confirm the response excludes contact/performance fields), and can handle invoices, while receiving 403 when trying to manage inventory catalog or edit an existing job order (`jobs.manage.full`).
  3. `technician` user can only view/edit assigned repair jobs and receives 403 when attempting to edit unassigned jobs or parts catalog.
  4. **`RepairJobPolicy` coverage at every call site** — not just one generic test, but one assertion per controller that consumes it: `JobOrderController`, `DiagnosisController`, `ProgressUpdateController`, `PhotoCommentController` each get their own "technician gets 403 on an unassigned job" test. This is the check that catches a call site where `$this->authorize()` was forgotten during implementation, which a single shared test would miss.
  5. `inventory_staff` can access stock catalog/suppliers but receives 403 on invoices, customers, and repair jobs.
  6. `shop_manager` with `users.manage.limited` receives 403 when trying to create/edit `admin` or `shop_manager` users.
  7. `roles:migrate-legacy` command: run twice in a row against seeded legacy data, assert second run is a no-op (idempotency) and no user ends up with zero roles.

### Manual Verification
- Log in as test users for each of the 5 roles (`admin`, `shop_manager`, `technician`, `inventory_staff`, `cashier`).
- Verify sidebar navigation links hide/show correctly according to role permissions.
- Verify 403 Forbidden screens when accessing unauthorized routes directly in browser URL.
