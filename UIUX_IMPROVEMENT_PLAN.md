# iRepair Shop System — UI/UX Improvement Plan (Agent-Executable)

> **For AI coding agents (Antigravity, Claude Code, Cursor, etc.):** This file is a task
> specification, not documentation. Treat each numbered task as a unit of work: read the
> "Target" to find the relevant files, apply the "Change," and verify against
> "Acceptance criteria" before moving to the next task. Tasks are ordered by priority.
> Stack assumptions: Laravel (Blade views + Controllers), Tailwind CSS, Alpine.js,
> Chart.js, Spatie roles/permissions. Adjust file paths to match the actual repo
> structure if it differs from the conventions below.

---

## 0. Context (read first)

This is a repair-shop management system with two user classes:
1. **Staff** (Administrator / Technician / FrontDesk Staff) — authenticated, manage
   job orders, inventory, invoices, etc.
2. **Customers** — no-auth, access a live tracking page via ticket number or token.

The current architecture has 10 flat top-level modules and duplicates job-order-related
data across 5+ separate resource pages (job_orders, invoices, warranties, etc.). The
goal of this plan is to (a) consolidate the staff experience around a single job-order
workspace, (b) close usability/trust gaps in the customer-facing tracker, and (c)
harden the design system so it doesn't erode under real data.

Design tokens already defined (keep these, don't invent new ones):
```
--bg-void:      #0B0B0C
--bg-surface:   #17181A
--bg-card:      #161b26
--accent:       #f59e0b   /* amber gold — primary actions, active tabs, progress fill */
--accent-deep:  #B97A1A   /* copper — borders, muted badges */
--status-ok:    #10b981   /* signal emerald — ready/completed/positive */
--status-alert: #ef4444   /* crimson — urgent/decline/stock warning */
font-display: 'Oswald'
font-body:    'Inter'
font-mono:    'JetBrains Mono'   /* financial values, ticket numbers */
currency: ₱ (Philippine Peso, always symbol-prefixed, no ISO code)
```

---

## MUST FIX

### Task 1 — Consolidate the Job Order page into a tabbed workspace

**Problem:** Diagnosis, ProgressUpdate, PhotoComment, CustomerApprovalRequest, Invoice,
and Warranty are all modeled as children of `JobOrder`, but are currently routed/viewed
as separate top-level resources. Staff have to leave the ticket to touch billing,
warranty, or approvals.

**Target:**
- `resources/views/job_orders/show.blade.php` (or equivalent show view)
- `routes/web.php` — job order resource routes
- Any separate `invoices/show`, `warranties/show` views currently reached independently
  of a job order context

**Change:**
1. Make `job_orders/show.blade.php` the canonical workspace for a single ticket.
2. Add a horizontal tab bar directly under the ticket header (ticket #, customer,
   device, current stage badge). Tabs, in this order:
   `Overview | Diagnosis | Progress | Photos | Approval | Invoice | Warranty`
3. Each tab lazy-loads its section via Alpine.js `x-show` / `x-data` state — do not
   full-page-reload between tabs. Use a single Alpine component scoped to the show
   page, e.g.:
   ```html
   <div x-data="{ tab: 'overview' }">
     <nav class="flex gap-1 border-b border-[--accent-deep]">
       <button @click="tab='overview'"  :class="tab==='overview'  ? 'text-[--accent] border-b-2 border-[--accent]' : 'text-gray-400'">Overview</button>
       <button @click="tab='diagnosis'" :class="tab==='diagnosis' ? 'text-[--accent] border-b-2 border-[--accent]' : 'text-gray-400'">Diagnosis</button>
       <!-- ...remaining tabs... -->
     </nav>
     <div x-show="tab==='overview'">@include('job_orders.partials.overview')</div>
     <div x-show="tab==='diagnosis'">@include('job_orders.partials.diagnosis')</div>
     <div x-show="tab==='progress'">@include('job_orders.partials.progress')</div>
     <div x-show="tab==='photos'">@include('job_orders.partials.photos')</div>
     <div x-show="tab==='approval'">@include('job_orders.partials.approval')</div>
     <div x-show="tab==='invoice'">@include('job_orders.partials.invoice')</div>
     <div x-show="tab==='warranty'">@include('job_orders.partials.warranty')</div>
   </div>
   ```
4. Keep `/invoices` and `/warranties` as top-level index routes for browsing/search
   across all tickets (Administrator/FrontDesk still need "show me all unpaid
   invoices"), but the *canonical edit/detail* experience for a specific record lives
   inside its parent job order tab. Deep-link from the index list into
   `job_orders/{id}?tab=invoice`.
5. Badge each tab with a count/alert dot where relevant (e.g. red dot on "Approval" if
   a `CustomerApprovalRequest` is pending, red dot on "Invoice" if balance > 0).

**Acceptance criteria:**
- Opening one job order and completing diagnosis notes, an invoice, and a warranty
  entry requires zero full-page navigations away from `/job_orders/{id}`.
- Tab state does not reset on Alpine re-render (use `x-data` at the wrapping div, not
  per-tab).
- Existing `/invoices` and `/warranties` index pages still work for cross-ticket
  browsing and link into the correct tab.

---

### Task 2 — Role-scoped sidebar navigation

**Problem:** All 10+ modules appear as sidebar peers regardless of role. A Technician
sees "Suppliers," "Users," "Backups" — noise that doesn't apply to their job.

**Target:** Main layout sidebar partial (e.g. `resources/views/layouts/partials/sidebar.blade.php`)

**Change:**
1. Group nav items into labeled sections:
   ```
   Operations   → Job Orders, Customers, Devices
   Stock        → Inventory, Suppliers
   Money        → Invoices, Warranties, Reports
   Team         → Technicians
   Admin        → Users, Audit Logs, Backups        (Administrator only)
   ```
2. Wrap each section (not just each link) in a Spatie role check:
   ```php
   @role('Administrator')
     <!-- Admin section -->
   @endrole
   ```
3. Technician role: show only Operations + their own assigned queue view.
   FrontDesk role: show Operations + Money, hide Stock's Suppliers sub-item if
   purchasing isn't part of their job (confirm with stakeholder if unsure — flag as a
   `// TODO: confirm FrontDesk PO access` comment rather than guessing).

**Acceptance criteria:**
- Logging in as each of the three roles shows a visibly different, shorter sidebar,
  not the same 10-item list with disabled links.
- No route becomes *unreachable* for a role that should have it — this is a nav
  visibility change, not a permissions change. Don't touch the underlying middleware.

---

### Task 3 — Define the "Declined" approval resolution path

**Problem:** `CustomerApprovalRequest` status can become `Declined`, but no explicit
next state exists. A declined ticket currently has no defined next step, which will
strand real tickets at the counter.

**Target:**
- `CustomerApprovalRequest` model/controller
- `JobOrder` status enum / state machine (wherever stage transitions are defined)
- `job_orders/partials/approval.blade.php` (new, from Task 1)

**Change:**
1. When an approval request's status flips to `Declined`, do **not** auto-advance the
   job order's stage. Instead, set the job order into a new interim state, e.g.
   `Awaiting Staff Decision`, and require an explicit staff action with three options
   surfaced as buttons on the Approval tab:
   - **Proceed with original scope only** → resumes normal stage flow at "Under Repair"
   - **Return device as-is** → advances directly to "Ready for Pickup" with a required
     staff note explaining unresolved fault(s)
   - **Escalate to manager** → flags the ticket (visual badge) and notifies via
     whatever notification channel already exists (mail/Slack/etc. — reuse existing
     notification infra, don't add a new channel)
2. Log every one of these resolutions to `AuditLog` (this model already exists per the
   README's schema — use it, don't create a parallel log).

**Acceptance criteria:**
- A job order cannot silently sit in "Declined" limbo — the UI forces one of the three
  resolutions before further stage progress is allowed.
- Each resolution path is reflected in `AuditLog`.

---

### Task 4 — Public/internal note visibility default + explicit indicator

**Problem:** `ProgressUpdate.public` is a boolean a staff member can forget to toggle
correctly, risking an internal note ("board corroded, previous owner spilled soda")
leaking to the customer-facing tracker.

**Target:**
- `ProgressUpdate` model (default value)
- Staff-facing note entry form (likely `job_orders/partials/progress.blade.php`)
- Public tracker view (`resources/views/public/track.blade.php` or similar)

**Change:**
1. Migration/model default: `public` defaults to `false` at the database level, not
   just the form.
2. Replace any plain checkbox with a two-button choice that's impossible to miss:
   `[ Save as internal note ]`  `[ Post update to customer ]` — no default-checked
   checkbox pattern.
3. In the staff note log (not just the entry form), show a persistent icon next to
   every note that is currently customer-visible — e.g. an eye icon with an amber
   accent — so staff scanning history can tell at a glance what the customer has
   already seen.
4. Add a confirmation step before submitting a customer-visible note for the first
   time on a ticket: a lightweight Alpine `x-show` confirm strip, not a blocking modal
   (avoid modal fatigue for a routine action).

**Acceptance criteria:**
- New `ProgressUpdate` rows created via direct DB/seed default to `public = false`.
- It is not possible to make a note customer-visible via a single accidental click —
  requires a distinctly labeled action.
- Staff viewing the progress log can distinguish public vs internal notes without
  opening each one.

---

### Task 5 — Mobile-first rebuild of the public tracking page

**Problem:** The public `/track/{token}` and `/status/{ticket_number}` pages are the
highest-traffic, least-forgiving surface (customers on phones, possibly poor
connectivity, no login to fall back on) but currently get only generic responsive
treatment.

**Target:** `resources/views/public/track.blade.php`, `resources/views/public/status.blade.php`

**Change:**
1. Single-column layout only below `md:` breakpoint — no side-by-side panels.
2. Above-the-fold order (top to bottom), non-negotiable:
   1. Ticket number + device (brand/model)
   2. **ETA countdown** — this is the #1 reason customers open the link
   3. Current stage, shown as text label + position, not color alone (see Task 7)
   4. Approve/Decline action **only if** a `CustomerApprovalRequest` is pending —
      shown with the cost/day impact and at least one supporting photo inline, not
      behind a second tap
3. Approve/Decline buttons: minimum 44×44px tap target, spaced at least 12px apart,
   with a confirm step ("Are you sure you want to approve ₱X additional cost?") since
   these carry real financial consequences — no accidental taps.
4. Photo gallery and comment thread load below the fold, lazy-loaded (don't block
   initial paint on image downloads over a possibly slow connection).

**Acceptance criteria:**
- On a 375px-wide viewport, ETA and current stage are visible without scrolling.
- Approve/Decline requires two distinct taps (select + confirm), not one.
- Lighthouse mobile performance score on this page is not blocked by eagerly-loaded
  gallery images.

---

## NICE TO HAVE

### Task 6 — Shared status/currency components

**Problem:** ₱ currency formatting, date formatting, and the 8-stage status badge are
likely re-implemented separately across the staff job-order view, invoice view, and
public tracker — risking label drift (e.g. staff sees "In Progress," customer sees
"Under Repair" for the same enum value).

**Target:** New shared Blade components.

**Change:**
1. Create `resources/views/components/currency.blade.php`:
   ```blade
   @props(['amount'])
   <span class="font-mono">₱{{ number_format($amount, 2) }}</span>
   ```
2. Create `resources/views/components/status-badge.blade.php` driven by a single
   source-of-truth enum/config file (e.g. `config/job_order_stages.php`) mapping each
   of the 8 stages to exactly one label + one color token. Every view (staff, public,
   invoice) must render status via `<x-status-badge :stage="$order->stage" />` — never
   a hardcoded string.
3. Grep the codebase for any inline `₱` string formatting or hardcoded stage label
   arrays and replace with these components.

**Acceptance criteria:**
- Exactly one file defines the 8 stage labels; every view imports from it.
- No view contains a hardcoded `₱` + `number_format` combination outside the shared
  component.

---

### Task 7 — Accessible progress indicator (not color-only)

**Problem:** The 8-stage progress bar likely relies on position + fill color alone,
which fails for color-vision-impaired or simply distracted users checking anxiously
on a phone.

**Target:** Progress bar partial used on both staff and public views.

**Change:**
1. Every stage segment renders its text label persistently (not only on hover/active),
   using `class="ts"`-equivalent small text under each segment.
2. The *current* stage gets an explicit "You are here" marker (e.g. a small arrow or
   dot above the segment) in addition to color fill — don't rely on amber alone to
   convey "this is where we are now."
3. Verify contrast: amber fill + dark background text must hit at least 3:1 for the
   segment label; body copy elsewhere must hit 4.5:1 against `#0B0B0C` / `#17181A`.
   Spot-check the copper (`#B97A1A`/`#7A4A12`) accent specifically — it's the token
   most likely to fail against near-black.

**Acceptance criteria:**
- Stage can be identified correctly with a grayscale filter applied (browser devtools
  simulate color-blindness check).
- Contrast ratios verified for accent/copper text against dark backgrounds.

---

### Task 8 — Signature capture fallback

**Problem:** Canvas-based digital signature capture can block intake entirely for a
staff member on assistive tech or a malfunctioning touchscreen.

**Target:** Job order intake signature component.

**Change:** Add a "Type your name to sign" text-input fallback next to the canvas pad,
toggled via a plain link/button ("Can't sign? Type your name instead"), storing a flag
that distinguishes typed vs. drawn signatures in the same field the drawn signature
currently populates.

**Acceptance criteria:** Intake can be completed to 100% without ever touching the
canvas element.

---

### Task 9 — Surface existing audit log as a one-click revert

**Problem:** `AuditLog` already captures user/action/model/changes, but is currently
read-only. Stock adjustments, stage changes, and invoice edits have no visible undo.

**Target:** Audit log viewer (`/audit_logs`) and any inline "recent changes" widget on
job order / inventory pages.

**Change:** For reversible change types only (stage transitions, stock quantity
adjustments — **not** payments or anything with financial/legal weight), add a
"Revert this change" button next to the relevant audit log entry that re-applies the
prior `changes` payload. Require a confirmation step and log the revert itself as a
new audit entry (don't delete history).

**Acceptance criteria:**
- Reverting a stock adjustment restores the prior quantity and creates a new,
  separate audit log row (the original entry is never mutated or deleted).
- Payment/invoice financial entries are explicitly excluded from one-click revert.

---

## Suggested execution order

1. Task 6 (shared components) — do this **before** Tasks 1, 5, 7, since they all
   consume the currency/status components you're building here.
2. Task 1 (job order hub)
3. Task 2 (role-scoped nav)
4. Task 3 (declined-approval path)
5. Task 4 (public/internal note default)
6. Task 5 (mobile tracker rebuild)
7. Task 7 (accessible progress bar)
8. Task 8 (signature fallback)
9. Task 9 (audit log revert)

## Notes for the agent

- Do not invent new color tokens — reuse the ones listed in Section 0.
- Do not change underlying route middleware/permissions when doing Task 2 — that task
  is nav *visibility* only.
- Where a task references a file path that doesn't match the actual repo layout, locate
  the equivalent file by function (e.g. "the job order show view") rather than skipping
  the task.
- Flag any ambiguous business-logic decision (e.g. FrontDesk's exact permission
  boundary in Task 2) with a `// TODO: confirm with stakeholder` comment instead of
  guessing silently.
