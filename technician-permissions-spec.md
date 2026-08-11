# Technician Role — Permissions Specification
> Addendum to Section 3 (Roles & Permissions) of the iRepairShop README

---

## 1. Role Description (Updated)

| Role | Description |
|---|---|
| `Technician` | Scoped to own assigned job orders only. View-only access to customers, inventory, warranties, and reports — no create/edit rights outside their own repair workflow. |

---

## 2. Key Permission Gates (Technician)

| Permission | Scope | Status |
|---|---|---|
| `repairs.view.own` | Own assigned job orders only | Existing |
| `repairs.manage` | Own assigned job orders only | Existing |
| `diagnosis.manage` | Own assigned job orders only | Existing |
| `parts.usage.create` | Add/remove parts on own job orders | Existing |
| `customers.view.scoped` | Customers linked to own assigned jobs | **New** |
| `inventory.view` | Read-only browse of parts catalog & stock levels | **New** |
| `warranty.view.scoped` | Warranties on devices they personally repaired | **New** |
| `reports.view.own` | Personal stats only (own jobs, own completion rate) — not shop-wide totals | **New** |
| `technicians.view.assignments` | Read-only — colleague name + current customer/job assignment, no contact info | **New** |

**Explicitly denied:**

| Permission | Reason |
|---|---|
| `jobs.create` | Job intake stays with Cashier/Admin (Step 2) |
| `devices.create` | Device registration stays with Cashier/Admin (Step 1) |
| `repairs.assign` | Assignment/reassignment stays with Admin/Manager (Step 3) |
| `inventory.manage` | Technicians can consume stock via `parts.usage.create`, but cannot edit the catalog, adjust stock manually, or manage suppliers |
| `warranty.claim` | Filing/resolving claims stays with Admin/Manager |
| `technicians.manage` | Cannot view or edit other technicians' profiles/assignments |
| `users.manage`, `audit_logs.view`, `backups.manage` | Admin only, unchanged |

---

## 3. Technician ↔ Cashier Handoff

The two roles never share a permission, but they sit on opposite ends of the same job order at three points in the pipeline:

| Stage | Cashier does | Technician does | Shared data |
|---|---|---|---|
| Intake (Steps 1–2) | Creates customer/device, creates Job Order, optionally pre-assigns a technician (`jobs.create`) | — (not yet assigned) | `JobOrder` record |
| Assignment (Step 3) | Checks `GET /technicians-availability` for open technicians (`technicians.view.availability` — name, specialty, open job count only, no contact info) | Sees the job appear on their own dashboard once assigned; can see colleague load via `technicians.view.assignments` | `active_jobs_count` |
| Repair (Steps 4–6) | No visibility into diagnosis/progress internally (only via the customer-facing status page, same as the customer) | Runs diagnosis, adds parts (`parts.usage.create` decrements stock, recalculates `parts_cost`), posts progress updates | `parts_cost`, `labor_cost`, stage % |
| Billing (Step 8) | Generates invoice from `labor_cost` + `parts_cost` + `service_fee` (`invoices.manage`), records payment | No access to invoice or payment data | `total_cost` → `Invoice` |
| Release (Step 9) | Confirms payment, moves job to `Ready for Pickup`, captures signature, job → `Released` | `active_jobs_count` decremented; device now covered under a warranty visible to them via `warranty.view.scoped` | `Warranty` record |

**Permission overlap at the handoff points:**

| Permission | Cashier | Technician |
|---|---|---|
| `warranty.view` (full) | ✅ | ❌ — `warranty.view.scoped` instead (own-repaired devices only) |
| `customers.view` (full CRUD) | ✅ | ❌ — `customers.view.scoped` instead (customers tied to own jobs) |
| `technicians.view.availability` | ✅ (name, specialty, open job count) | ❌ — has the read-only `technicians.view.assignments` instead (see below) |
| `inventory.view` | Not currently granted (see Open Decisions) | ✅ (new) |

**Gate for the new shared-visibility endpoint:**

```php
Gate::define('technicians.view.assignments', fn ($user) =>
    $user->hasAnyRole(['admin', 'shop_manager', 'cashier', 'Technician'])
);
```

This mirrors the existing cashier availability endpoint but returns a different shape — `{ id, name, current_customer, job_status }` instead of `{ specialty, open_job_count }` — since technicians need to know *who's on what*, not *who's free*.

---

## 4. Route-Level Access (Technician)

| Module | Route | Access |
|---|---|---|
| **Dashboard** | `GET /dashboard` | Scoped view — own active jobs, own stage breakdown, no shop-wide KPIs |
| **Job Orders** | `GET /job_orders/{id}` | Own jobs only (`RepairJobPolicy`) |
| **Job Orders** | `POST /job_orders` (create) | ❌ Denied |
| **Devices** | `POST /devices` (create) | ❌ Denied |
| **Diagnosis** | `GET/POST /job_orders/{id}/diagnosis` | Own jobs only |
| **Progress** | `POST /job_orders/{id}/progress_updates` | Own jobs only |
| **Customers** | `GET /customers` | Read-only, filtered to customers tied to technician's own jobs |
| **Customers** | `POST/PUT/DELETE /customers/*` | ❌ Denied |
| **Inventory** | `GET /inventory` | Read-only — stock levels, SKUs, reorder status |
| **Inventory** | `POST/PUT/DELETE /inventory/*`, stock adjust | ❌ Denied |
| **Warranties** | `GET /warranties` | Read-only, filtered to warranties from own-repaired devices |
| **Warranties** | `POST /warranties/{id}/claim` | ❌ Denied |
| **Reports** | `GET /reports` | Personal scope only |
| **Technicians** | `GET /technicians-assignments` | Read-only — colleague name + current customer/job assignment, no contact info |

---

## 5. Gate Definitions (Laravel / Spatie)

```php
// In a service provider (e.g. AuthServiceProvider) or seeder

// Existing technician gates
Gate::define('repairs.view.own', fn ($user, $job) => $user->technician?->id === $job->technician_id);
Gate::define('repairs.manage', fn ($user, $job) => $user->technician?->id === $job->technician_id);

// New scoped view gates
Gate::define('customers.view.scoped', function ($user, $customer) {
    if ($user->hasRole(['admin', 'shop_manager', 'cashier'])) {
        return true;
    }
    return $customer->devices()
        ->whereHas('jobOrders', fn ($q) => $q->where('technician_id', $user->technician?->id))
        ->exists();
});

Gate::define('warranty.view.scoped', function ($user, $warranty) {
    if ($user->hasRole(['admin', 'shop_manager', 'cashier'])) {
        return true;
    }
    return $warranty->jobOrder->technician_id === $user->technician?->id;
});

Gate::define('inventory.view', fn ($user) => $user->hasAnyRole(['admin', 'shop_manager', 'Technician']));

Gate::define('reports.view.own', fn ($user) => $user->hasRole('Technician'));

// Shared handoff visibility — both Cashier and Technician
Gate::define('technicians.view.assignments', fn ($user) =>
    $user->hasAnyRole(['admin', 'shop_manager', 'cashier', 'Technician'])
);
```

> **Note on `Gate::before`:** Admins and Managers already bypass all checks via the existing `Gate::before` hook — no change needed there. These new gates only need to short-circuit `true` for the higher roles *if* you want the same gate reused for both scoped and full access (as shown above), otherwise keep `reports.view` / `reports.view.own` as two separate permissions the way `warranty.view` and `warranty.view.scoped` are split here.

---

## 6. Controller-Level Notes

- **`DashboardController`** — branch on `auth()->user()->hasRole('Technician')` to query only jobs where `technician_id` matches, rather than shop-wide aggregates.
- **`CustomerController@index`** — when the requesting user is a Technician, join through `Device → JobOrder` and filter `technician_id`, instead of returning the full customer table.
- **`InventoryController@index`** — expose a read-only variant of the index view (hide "Adjust Stock," "Edit," "Delete" action buttons) when `!$user->can('inventory.manage')`.
- **`WarrantyController@index`** — mirror the same scoping pattern used in `RepairJobPolicy`, but traverse `Warranty → JobOrder → technician_id`.
- **`ReportsController`** — split into a shop-wide report (existing) and a personal report (new), gated separately so a technician's `/reports` request never touches other technicians' data.
- **`TechnicianController@assignments`** (new endpoint) — reuse the query shape from the existing `technicians-availability` endpoint used by Cashier, but return current customer/job instead of open job count, and allow both `cashier` and `Technician` roles through the gate.

---

## 7. Seeder Update

Add to `database/seeders/PermissionSeeder.php` (or wherever roles/permissions are seeded):

```php
$technicianRole = Role::findByName('Technician');

$technicianRole->givePermissionTo([
    'customers.view.scoped',
    'inventory.view',
    'warranty.view.scoped',
    'reports.view.own',
    'technicians.view.assignments',
]);

$cashierRole = Role::findByName('cashier');

$cashierRole->givePermissionTo([
    'technicians.view.assignments',
]);
```

---

## 8. Open Decisions

These need a decision before implementation — not something inferable from the current README:

1. **Customer scope** — should a technician see *only* customers tied to jobs currently assigned to them, or the full history of every job they've ever worked (including released/closed ones)?
2. **Colleague visibility** — should technicians see *every* other technician's current assignment, or only technicians on jobs for the same customer (as originally described)?
3. **Reports depth** — "personal stats" could mean just completion count/turnaround time, or something closer to individual performance metrics (rework rate, avg repair time). Worth scoping explicitly so it doesn't drift into a de facto performance-review tool without HR sign-off.
4. **Cashier inventory access** — Cashier currently has no `inventory.view` permission at all. Should Cashier be able to check part stock before creating a job order with parts pre-selected, or does that stay technician/manager territory?
