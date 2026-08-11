# iRepairShop System

> **A full-featured repair shop management system built with Laravel.**
> Covers the entire repair lifecycle — from customer intake to device release — including live tracking, AI-assisted diagnosis, inventory control, invoicing, and warranty management.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Tech Stack](#2-tech-stack)
3. [Roles & Permissions](#3-roles--permissions)
4. [System Architecture](#4-system-architecture)
5. [Core Flow: The Repair Lifecycle](#5-core-flow-the-repair-lifecycle)
   - [Step 1 — Customer & Device Intake](#step-1--customer--device-intake)
   - [Step 2 — Job Order Creation](#step-2--job-order-creation)
   - [Step 3 — Technician Assignment](#step-3--technician-assignment)
   - [Step 4 — Diagnosis](#step-4--diagnosis)
   - [Step 5 — Parts Usage & Inventory](#step-5--parts-usage--inventory)
   - [Step 6 — Live Progress Updates](#step-6--live-progress-updates)
   - [Step 7 — Customer Approval Workflow](#step-7--customer-approval-workflow)
   - [Step 8 — Invoice Generation & Payment](#step-8--invoice-generation--payment)
   - [Step 9 — Device Release & Warranty](#step-9--device-release--warranty)
6. [Customer Portal (Public Tracker)](#6-customer-portal-public-tracker)
7. [Inventory & Supplier Management](#7-inventory--supplier-management)
8. [Warranty & Claims](#8-warranty--claims)
9. [Audit Trail & Backup](#9-audit-trail--backup)
10. [Key Services](#10-key-services)
11. [Database Models at a Glance](#11-database-models-at-a-glance)
12. [Route Map](#12-route-map)
13. [Setup & Installation](#13-setup--installation)

---

## 1. System Overview

iRepairShop is a Laravel-based repair shop management system designed for electronics repair businesses. It provides an end-to-end workflow for managing customers, devices, repair jobs, technicians, parts inventory, billing, and post-repair warranties — all from a single platform.

Key highlights:
- **8-Stage Repair Pipeline** with real-time percentage tracking
- **Public Customer Self-Service Portal** — no login required to track a device
- **AI-Assisted Diagnosis** powered by Claude (Anthropic) with a smart heuristic fallback
- **Customer Approval Requests** — customers approve or decline extra repair scope mid-job
- **Automated SMS/Email Notifications** on every status change
- **Role-Scoped Access Control** via Spatie Permissions
- **Full Audit Trail** on all critical actions

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel (PHP) |
| Database | SQLite (dev) / MySQL (prod) |
| Frontend | Blade Templates + Vanilla CSS + Vite |
| Auth | Custom session-based auth (`AuthController`) |
| Permissions | Spatie Laravel Permission (roles & permissions) |
| PDF Generation | Barryvdh DomPDF |
| AI Diagnosis | Anthropic Claude API (`claude-3-5-sonnet`) |
| Image Processing | Custom `ImageCompressionService` |

---

## 3. Roles & Permissions

The system uses **Spatie Laravel Permission** for fine-grained role-based access control. Key roles include:

| Role | Description |
|---|---|
| `admin` | Full system access including user management, audit logs, backups |
| `shop_manager` | Full repair management + reporting |
| `cashier` | Invoice & payment management, job intake |
| `Technician` | Scoped to own assigned job orders only |

### Key Permission Gates

| Permission | Who Has It |
|---|---|
| `jobs.manage.full` | Admin, Shop Manager |
| `jobs.create` | Admin, Manager, Cashier |
| `repairs.assign` | Admin, Manager |
| `repairs.manage` | Technicians (own jobs) |
| `repairs.view.own` | Technicians |
| `diagnosis.manage` | Technicians (own jobs) |
| `parts.usage.create` | Technicians, Admin |
| `parts.catalog.manage` | Admin, Manager |
| `invoices.manage` | Cashier, Admin, Manager |
| `technicians.manage` | Admin, Manager |
| `technicians.view.availability` | Cashier |
| `warranty.view` | Admin, Manager, Cashier |
| `warranty.claim` | Admin, Manager |

> **Policy Scope**: The `RepairJobPolicy` ensures that technicians can only `manage` job orders where their `technician_id` matches the job. Admins and managers bypass this check via `Gate::before`.

---

## 4. System Architecture

```
+-----------------------------------------------------------------------+
|                          iRepairShop System                           |
|                                                                       |
|  +------------------+     +---------------------------------------+   |
|  |  Public Routes   |     |          Auth Routes (Staff)          |   |
|  |                  |     |                                       |   |
|  |  /status         |     |  Dashboard  | Customers  | Devices    |   |
|  |  /track/{token}  |     |  JobOrders  | Diagnosis  | Inventory  |   |
|  |  /approval       |     |  Invoices   | Warranties | Reports    |   |
|  +--------+---------+     |  Technicians| Users      | Audit Logs |   |
|           |               +----+----------------------------------+   |
|           v                    |                                      |
|  +-----------------+      +----v---------------------------------+    |
|  | CustomerPortal  |      |           Controllers                |    |
|  | Controller      |      |  JobOrder  Invoice  Diag  Inventory  |    |
|  +--------+--------+      |  Warranty  Technician  User          |    |
|           |               +----+---------------------------------+    |
|           v                    v                                      |
|  +--------------------------------------------------------------+    |
|  |                         Services                             |    |
|  |  AiDiagnosisService  |  ProgressTrackerService               |    |
|  |  ImageCompressionService  |  ForecastingService              |    |
|  +----------------------------+---------------------------------+    |
|                               v                                       |
|  +--------------------------------------------------------------+    |
|  |                        Eloquent Models                       |    |
|  |  JobOrder  Diagnosis  Invoice  Payment  Part                  |    |
|  |  Customer  Device  Technician  Warranty  AuditLog             |    |
|  +----------------------------+---------------------------------+    |
|                               v                                       |
|                         SQLite / MySQL                                |
+-----------------------------------------------------------------------+
```

---

## 5. Core Flow: The Repair Lifecycle

The entire repair process follows an **8-stage pipeline**. Each stage has a percentage range that drives the live tracking UI.

```
Received (0-10%) --> Diagnosing (10-25%) --> Waiting for Parts (25-40%)
  --> Under Repair (40-75%) --> Testing (75-90%) --> Ready for Pickup (90-95%)
    --> Completed (95-100%) --> Released (100%)
```

---

### Step 1 — Customer & Device Intake

**Who:** Cashier or Admin  
**Controllers:** `CustomerController`, `DeviceController`

1. Staff searches for or creates a **Customer** record (name, phone, email, `customer_code`).
2. A **Device** is registered under that customer (brand, model, serial number, device type).
3. Devices can be pre-registered or created on the fly directly from the Job Order creation form.

---

### Step 2 — Job Order Creation

**Who:** Cashier or Admin (`jobs.create`)  
**Controller:** `JobOrderController@store`

1. Staff fills in the Job Order form:
   - Customer + Device selection
   - Reported issue (customer's description of the problem)
   - Priority (`Low` / `Normal` / `High` / `Urgent`)
   - Optional: technician pre-assignment, estimated completion date, initial labor cost / service fee / discount
2. The system auto-generates a **ticket number** in the format `JO-YYYY-XXXX`.
3. A **UUID tracking token** is auto-generated for the public customer portal link.
4. The QR code is set to the ticket number for physical receipt printing.
5. Initial status is set to **`Received`**.
6. A **status history entry** is logged.
7. An **audit log** entry is recorded.
8. An **SMS notification** is auto-sent to the customer with their ticket number and a tracking link.
9. If a technician is pre-assigned, their `active_jobs_count` is incremented.

---

### Step 3 — Technician Assignment

**Who:** Admin or Shop Manager (`repairs.assign`)  
**Controller:** `JobOrderController@assignTechnician`

- A manager can assign or re-assign any active technician to a job at any time.
- When re-assigning:
  - The **old technician's** `active_jobs_count` is decremented.
  - The **new technician's** `active_jobs_count` is incremented.
- Technicians only appear in assignment pickers if `is_active = true`.
- A lightweight **availability endpoint** (`GET /technicians-availability`) returns `{ id, name, specialty, open_job_count }` for cashiers who have `technicians.view.availability` — no contact info is exposed to that role.

---

### Step 4 — Diagnosis

**Who:** Assigned Technician or Admin (`diagnosis.manage`)  
**Controller:** `DiagnosisController@store`  
**Service:** `AiDiagnosisService`

1. The technician opens the Diagnosis form for their assigned job.
2. They fill in:
   - Checklist items
   - Identified issues
   - Recommended repairs
   - Estimated cost
   - Technician remarks
3. On save, the `AiDiagnosisService` automatically runs an AI diagnosis:
   - **Primary:** Calls the **Anthropic Claude API** with device type, brand, model, and the reported issue.
   - **Fallback:** If the API key is missing or the call fails, a **heuristic rule engine** activates — matching keywords (screen, battery, water, charge, SSD) to return structured suggestions.
4. AI suggestions (`diagnosis`, `confidence`, `recommended_actions`, `suggested_parts`, `estimated_cost`, `estimated_time_hours`) are saved alongside the technician's own notes.
5. If the job is still in `Received` status, it is automatically advanced to **`Diagnosing`**.
6. An audit log is recorded.

> Technicians can also call `POST /job_orders/{id}/diagnosis/ai_suggestions` at any time to fetch fresh AI suggestions inline without saving.

---

### Step 5 — Parts Usage & Inventory

**Who:** Assigned Technician or Admin (`parts.usage.create`)  
**Controller:** `JobOrderController@addPart` / `removePart`

1. The technician selects a part from inventory (only parts with `stock_quantity > 0` are shown).
2. When a part is added:
   - A `JobOrderPart` record is created (or quantity updated if it already exists).
   - The part's `stock_quantity` is **decremented** immediately.
   - A `StockMovement` of type `repair_usage` is recorded with a negative quantity.
   - `JobOrder::calculateTotalCost()` is called to recalculate `parts_cost` and `total_cost`.
3. When a part is removed:
   - The part's `stock_quantity` is **restored**.
   - A `StockMovement` of type `adjustment` is recorded.
   - `calculateTotalCost()` is called again.

**Cost Formula:**
```
total_cost = (labor_cost + parts_cost + service_fee) - discount
```
Discount can be either a **fixed amount** (`fixed`) or a **percentage** (`percentage`) of the subtotal.

---

### Step 6 — Live Progress Updates

**Who:** Assigned Technician or Admin  
**Controller:** `ProgressUpdateController@store`  
**Service:** `ProgressTrackerService@postUpdate`

1. The technician posts a progress update with:
   - Pipeline stage (one of the 8 stages)
   - Completion percentage
   - Description
   - Optional: progress photos
   - Toggle: whether the update is visible to the customer
2. The `ProgressTrackerService`:
   - Detects **rework** if the new percentage is lower than the current — a rework reason is then required.
   - Compresses and thumbnails all uploaded photos via `ImageCompressionService`.
   - Updates `job_order.current_percentage` and `job_order.status`.
   - Creates a `JobOrderStatusHistory` entry if the stage changed.
   - Fires an **audit log** and, if customer-visible, an **SMS notification** with a live tracking link.

---

### Step 7 — Customer Approval Workflow

**Who:** Technician sends request; Customer responds online (no login required)  
**Service:** `ProgressTrackerService@postUpdate` (with `approvalData`)  
**Controller:** `CustomerApprovalController@respond`

When a technician discovers extra work is needed mid-repair:

1. The technician posts a progress update and includes an **approval request** (`title`, `description`, `additional_cost`, `additional_time_days`).
2. A `RepairApprovalRequest` record is created with `status = pending`.
3. An **SMS is sent to the customer** with a link to the tracking portal.
4. The customer visits the public portal and sees the pending approval request.
5. The customer clicks **Approve** or **Decline**:
   - **Approved:** `additional_cost` is added to `service_fee`; `estimated_completion_date` is extended by `additional_time_days`. Staff is notified via email.
   - **Declined:** Staff is notified. A manager can then resolve the declined request by choosing:
     - `proceed_original` — continue with original repair scope.
     - `return_device` — mark as `Ready for Pickup` immediately.
     - `escalate_manager` — flag for management follow-up.
6. Approval requests that remain `pending` for **48+ hours** are automatically expired, and an alert is sent to management.

---

### Step 8 — Invoice Generation & Payment

**Who:** Cashier or Admin (`invoices.manage`)  
**Controller:** `InvoiceController`

#### 8a — Generate Invoice

1. Staff clicks "Generate Invoice" on a Job Order.
2. The system creates an `Invoice` record with number format `INV-YYYY-XXXX`.
3. Invoice line items are automatically created from the job order:
   - **Labor** — from `labor_cost`
   - **Parts** — one line item per `JobOrderPart`
   - **Service Fee** — from `service_fee`
4. Discounts are applied and `total_amount` is calculated.
5. Initial `payment_status` is `unpaid`.
6. An audit log is recorded.

#### 8b — Record Payment

1. Staff records a payment on the invoice:
   - Amount, payment method (`Cash`, `Credit Card`, `GCash`, `Bank Transfer`), reference number, notes.
2. A `Payment` record is created with number format `PAY-YYYY-XXXX`.
3. `Invoice::updatePaymentStatus()` is called:
   - `paid_amount >= total_amount` → `paid`
   - `paid_amount > 0` → `partial`
   - `paid_amount = 0` → `unpaid`
4. Supports **partial payments** — multiple payment records can be attached to one invoice.
5. Staff can **print a receipt** (Blade view) or **download a PDF** (DomPDF) of the official receipt.

---

### Step 9 — Device Release & Warranty

**Who:** Cashier or Admin  
**Controller:** `JobOrderController@updateStatus` / `saveSignature`

1. Once payment is confirmed, the job status is updated to `Ready for Pickup`.
2. An SMS is auto-sent to the customer: *"Your device is ready! Please bring your repair receipt."*
3. At pickup, the customer **signs** for their device:
   - **Drawn signature** — captured on a canvas pad, saved as an image.
   - **Typed signature** — customer types their name, saved as a text file.
4. On signature save, the job status is immediately set to **`Released`** and `released_at` is timestamped.
5. A **90-Day Warranty** is automatically created:
   - `start_date` = today, `end_date` = today + 90 days
   - `coverage_details` = "Standard 90-Day Parts & Labor Warranty"
   - `status` = `active`
6. The technician's `active_jobs_count` is decremented.

---

## 6. Customer Portal (Public Tracker)

**No login required.**  
**Controller:** `CustomerPortalController`

### Accessing a Ticket

Customers can search using any of the following:
- Ticket number (`JO-2026-0001`)
- Invoice number (`INV-2026-0001`)
- UUID tracking token
- Device serial number
- Customer phone number
- Customer email

The `JobOrder::findByReference()` method normalizes and fuzzy-matches all of these formats.

### What Customers See

- Current **repair stage** and **percentage complete** on a progress bar
- **Estimated time remaining**
- Full **status history timeline**
- **Live progress updates** posted by technicians (customer-visible ones only)
- **Before & After photo comparison slider** once repair is complete
- **Pending approval requests** — with Approve / Decline buttons

### Live Progress Polling

The frontend polls `GET /track/{token}/progress-data` periodically to update the percentage and status without a full page refresh.

---

## 7. Inventory & Supplier Management

**Controllers:** `InventoryController`, `SupplierController`

### Parts (Inventory)

Each part has:
- `sku`, `barcode`, `name`, `description`
- `cost_price`, `selling_price`
- `stock_quantity`, `reorder_level`
- `location_rack`, `compatible_models`
- Linked to a **Category** and optional **Supplier**

Every stock change creates a `StockMovement` record (`in`, `out`, `adjustment`, `repair_usage`) for full traceability.

The inventory dashboard shows:
- Total cost valuation
- Total retail valuation
- Low-stock count (parts at or below reorder level)

Barcodes can be printed per part via `GET /inventory/{part}/barcode`.

### Suppliers & Purchase Orders

- Suppliers track name, contact, address, and notes.
- Staff can create **Purchase Orders** against a supplier (`PurchaseOrder` + `PurchaseOrderItem`).

---

## 8. Warranty & Claims

**Controller:** `WarrantyController`

- Warranties are auto-created on device release (90-day standard coverage).
- Staff can view all active/expired warranties and file **warranty claims** (`CLM-YYYY-XXXX`).
- Claim resolution statuses: `pending` → `approved` / `rejected` / `resolved`.
- Managers can update claim status and add resolution notes.

---

## 9. Audit Trail & Backup

### Audit Logs

Every critical action in the system is recorded in `audit_logs`:

| Field | Description |
|---|---|
| `user_id` / `user_name` | Who performed the action |
| `action` | e.g. `create`, `status_change`, `process_payments`, `stock_adjust` |
| `module` | e.g. `JobOrders`, `Invoices`, `Inventory` |
| `description` | Human-readable description |
| `ip_address` / `user_agent` | For security tracing |

Admins can view and **revert** select actions via `AuditLogController`.

### Backups

- Manual backup creation via `BackupController`.
- Backups can be listed, downloaded, or restored from the admin panel.

---

## 10. Key Services

### `AiDiagnosisService`

Generates AI-powered repair suggestions.

- **Primary:** Calls `claude-3-5-sonnet-20241022` via Anthropic API.
- **Fallback:** Heuristic rule engine using keyword matching on the reported issue (screen, battery, water, charge, SSD).
- Returns: `diagnosis`, `confidence`, `recommended_actions`, `suggested_parts`, `estimated_cost`, `estimated_time_hours`.

### `ProgressTrackerService`

Handles all progress update logic:
- Rework detection (percentage regression requires explanation)
- Photo upload & compression
- Customer approval request creation
- Status history + audit + SMS notification on each update
- Stale approval expiration (48-hour rule)

### `ImageCompressionService`

Compresses uploaded repair progress photos and generates thumbnails for performance.

### `ForecastingService`

Provides analytics and demand forecasting for inventory and repair volume planning.

---

## 11. Database Models at a Glance

```
Customer --< Device --< JobOrder --< JobOrderPart >-- Part >-- Supplier
                           |                           +-- PartCategory
                           +--< Diagnosis (1:1)
                           +--< Invoice (1:1) --< InvoiceItem
                           |         +--< Payment
                           +--< Warranty (1:1) --< WarrantyClaim
                           +--< JobOrderStatusHistory
                           +--< RepairProgressUpdate --< RepairProgressPhoto --< PhotoComment
                           +--< RepairApprovalRequest
                           +--< Attachment (polymorphic)
                           +--< NotificationsLog

User ---- Technician --< JobOrder
     +--< AuditLog
```

---

## 12. Route Map

### Public (No Auth)

| Method | URI | Action |
|---|---|---|
| GET | `/status` | Customer portal home |
| POST | `/status/lookup` | Search ticket by reference |
| GET | `/status/{ticket_number}` | Show tracking page |
| GET | `/track/{token}` | Track by UUID token |
| GET | `/track/{token}/progress-data` | Live polling endpoint |
| POST | `/track/{token}/respond/{approval}` | Customer approve/decline |
| POST | `/track/{token}/photo-comments` | Customer comment on photo |

### Staff (Auth Required)

| Module | Key Routes |
|---|---|
| **Dashboard** | `GET /dashboard` |
| **Customers** | Full CRUD `/customers` |
| **Devices** | Full CRUD `/devices` |
| **Job Orders** | Full CRUD `/job_orders` + status, assign, parts, costs, photos, signature, receipt |
| **Diagnosis** | `GET/POST /job_orders/{id}/diagnosis` + AI suggestions |
| **Progress** | `POST /job_orders/{id}/progress_updates` |
| **Inventory** | Full CRUD `/inventory` + stock adjust + barcode print |
| **Suppliers** | Full CRUD `/suppliers` + purchase order create |
| **Technicians** | Full CRUD `/technicians` + availability endpoint |
| **Invoices** | List, generate from job, show, record payment, print receipt, download PDF |
| **Warranties** | List, show, file claim, update claim status |
| **Reports** | `GET /reports` |
| **Users** | Full CRUD `/users` |
| **Audit Logs** | `GET /audit_logs` + revert |
| **Backups** | List, create, download, restore |

---

## 13. Setup & Installation

### Requirements

- PHP 8.1+
- Composer
- Node.js & npm
- SQLite (dev) or MySQL (prod)

### Install

```bash
git clone <repo-url>
cd iRepairShopSystem

composer install
npm install

cp .env.example .env
php artisan key:generate

# Configure DB in .env (SQLite default works out of the box)
php artisan migrate --seed

# Compile frontend assets
npm run dev
```

### Optional: AI Diagnosis

Add your Anthropic API key to `.env` to enable live AI diagnosis:

```env
ANTHROPIC_API_KEY=sk-ant-...
```

Without the key, the heuristic fallback engine activates automatically.

### Run

```bash
php artisan serve
# Visit http://localhost:8000
```

### Default Login

Seed the database and log in with the admin credentials created by the seeder. Check `database/seeders/` for default email/password values.

---

> Built for iRepairShop · Laravel · Blade · SQLite/MySQL
