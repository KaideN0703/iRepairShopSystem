# Feature Build Prompt: Live Repair Status Tracker (Percentage + Photo Milestones)

Copy this into your AI coding assistant. It's written to slot into the existing Laravel repair shop system (references the `job_orders` table and staff roles from the main project).

---

## PROMPT START

You are an expert Laravel developer. Build a **Live Repair Status Tracker** feature for an existing Device Repair Shop Management System (Laravel + Tailwind + Alpine.js). This feature lets staff post progress updates on a repair job as a percentage, each with a photo and a description, and lets customers watch the progress live without logging in.

### Tech additions needed
- `spatie/laravel-medialibrary` (or plain Laravel Storage) for photo handling + auto-thumbnails
- Laravel Echo + Pusher (or Laravel Reverb, self-hosted) for real-time push to the customer portal
- Alpine.js for the staff-side upload widget and the customer-side live progress bar
- Image intervention (`intervention/image`) for compressing/resizing uploaded photos

---

## Core Concept

Each job order has a **progress timeline** made up of discrete **status updates**. Each update includes:
- **Percentage** (0–100, staff-selected, must be ≥ the previous update's percentage — see "Improvement" notes on rework)
- **Photo** (1 required, up to 4 optional) of the device at that point
- **Description** (short staff note, e.g. "Motherboard cleaned, testing charging port next")
- **Timestamp** (auto)
- **Staff member** (auto, from logged-in user)
- **Visibility flag** — internal-only note vs. customer-visible (some technical notes shouldn't go to the customer)

The **existing status pipeline** (Received → Diagnosing → Waiting for Parts → Under Repair → Testing → Ready for Pickup → Completed → Released) stays as the formal backend state machine. The percentage tracker is a richer, visual layer **on top of** that pipeline — not a replacement. Each pipeline stage maps to a percentage range so the two stay in sync automatically.

### Suggested default percentage mapping (staff can override per-job if needed)
| Stage | % Range |
|---|---|
| Received | 0–10% |
| Diagnosing | 10–25% |
| Waiting for Parts | 25–40% |
| Under Repair | 40–75% |
| Testing | 75–90% |
| Ready for Pickup | 90–95% |
| Completed | 95–100% |
| Released | 100% |

When staff post an update, they pick the pipeline stage first, then the widget suggests a percentage within that stage's range (still editable).

---

## Database Schema

```
repair_progress_updates
- id
- job_order_id (FK)
- posted_by (FK -> users)
- pipeline_stage (enum, matches job_orders.status)
- percentage (unsigned tinyint, 0-100)
- description (text)
- is_customer_visible (boolean, default true)
- created_at, updated_at

repair_progress_photos
- id
- repair_progress_update_id (FK)
- file_path
- thumbnail_path
- created_at
```

Keep `job_orders.status` and `job_orders.current_percentage` as denormalized columns updated whenever a new progress update is posted, so dashboard queries stay fast (no need to join every time just to show "62%").

---

## Staff-Side Build Requirements

1. **"Post Progress Update" panel** on the Job Order detail page:
   - Dropdown: pipeline stage (auto-suggests percentage range)
   - Slider or number input: exact percentage
   - Drag-and-drop / tap-to-upload photo field (mobile camera access enabled — `capture="environment"` on the file input for phone camera)
   - Text area: description
   - Toggle: "Visible to customer" (default ON)
   - Submit button — on submit, broadcast the update via Laravel Echo so the customer portal updates live if open
2. **Validation:**
   - Percentage cannot go backward unless staff explicitly confirms a "rework" reason (see improvements below)
   - At least one photo required per update
   - Auto-compress photos to a max width (e.g. 1600px) and generate a thumbnail on upload
3. **Timeline view** on the job order page showing all past updates in reverse-chronological order (photo + %, description, staff name, timestamp), so staff can scroll back through history.

## Customer-Side Build Requirements

1. **Public tracking page** — no login required, accessed via:
   - A unique tracking URL (`/track/{job_order_uuid}`)
   - Or QR code scan (already part of the main system)
2. **Live animated progress bar** at the top showing current percentage, updating in real time via Echo (no page refresh needed) when staff post new updates.
3. **Photo timeline / gallery** below the progress bar — each customer-visible update shown as a card: photo, percentage, description, and friendly timestamp ("2 hours ago").
4. **Estimated time remaining** — computed from historical average repair duration for similar device/issue type (simple moving average is fine for v1).
5. Fully mobile-responsive — most customers will check this from their phone.

---

## Customer Approval Module (Additional Cost / Change Requests)

Sometimes a progress update reveals new work needed (e.g., "found a second faulty component, +₱40, +1 day"). Instead of the shop calling the customer, let the customer approve or decline directly from the same tracking page.

### Database Schema

```
repair_approval_requests
- id
- job_order_id (FK)
- repair_progress_update_id (FK, nullable — the update that triggered this request, if any)
- requested_by (FK -> users)
- title (string, e.g. "Additional repair needed")
- description (text — what was found, why it's needed)
- additional_cost (decimal, nullable)
- additional_time_days (unsigned tinyint, nullable — extra turnaround time)
- status (enum: pending, approved, declined, expired)
- responded_at (timestamp, nullable)
- response_note (text, nullable — customer's optional comment when declining)
- created_at, updated_at
```

### Staff-Side Requirements
1. When posting a progress update, staff can optionally attach an **approval request**: title, description, additional cost, additional time.
2. Job order dashboard shows a clear badge for any job with a **pending** approval request, so staff know work is blocked waiting on the customer.
3. Staff get notified (in-app + email) the moment a customer responds, so work isn't delayed longer than necessary.
4. If the request sits unanswered for a configurable period (e.g. 48 hours), auto-flag it as `expired` and notify staff to follow up by phone — don't leave a job silently stuck forever.

### Customer-Side Requirements
1. On the public tracking page, a pending approval request appears as a prominent card above the progress bar (not buried in the timeline) — customer shouldn't have to hunt for it.
2. Card shows: what was found, additional cost, additional time impact, and two clear buttons: **Approve** / **Decline**.
3. Declining requires a short optional note (e.g. "just return device as-is") so staff know how to proceed without a phone call.
4. Once responded, the card collapses into the timeline as a resolved entry (so the history stays intact and auditable).
5. No login required — same tracking token/UUID used for the live status page authorizes the response, but validate the job order isn't already completed/released before accepting a response (prevent stale approvals on a closed ticket).

### Notification Triggers
- New approval request created → SMS/email to customer with a direct link to the tracking page
- Customer responds (approve/decline) → in-app + email notification to the staff member who requested it, and to the assigned technician
- Request expires unanswered → notification to shop manager to follow up manually

---

## Build Order
1. Migrations + models for `repair_progress_updates` and `repair_progress_photos`
2. Staff-side "Post Progress Update" form + photo upload/compression pipeline
3. Timeline view (staff side)
4. Public tracking route + Blade view
5. Real-time broadcast wiring (Echo/Reverb) so the customer page updates live
6. Notification hook — send SMS/email to customer only when a **customer-visible** update is posted (not on internal notes)
7. Migrations + models for `repair_approval_requests`
8. Staff-side "attach approval request" option on progress updates + pending-approval badge on job order dashboard
9. Customer-side approval card on the public tracking page (approve/decline + optional note)
10. Approval notification triggers (request created, response received, request expired)
11. Feature tests: posting an update updates `job_orders.current_percentage`; non-visible updates don't appear on the public page; backward percentage requires a rework reason; approval requests correctly block/unblock job progress and notify the right people; expired requests are flagged and staff notified.

## PROMPT END

---

## My thoughts on improving your idea

Your core idea (percentage + photo + description per update) is genuinely good — it turns a boring status label into something the customer can actually *see progress happening*, which builds trust and cuts down "is it done yet?" calls. Here's what I'd add or tighten up:

1. **Anchor percentage to your existing status pipeline, don't let it float free.** If percentage is totally independent of "Diagnosing / Under Repair / etc.", two problems creep in: staff will be inconsistent (one tech's "50%" is another's "70%"), and your reports/analytics module can't reliably use percentage for anything. Mapping ranges to stages (like above) keeps it meaningful and still customizable.

2. **Handle "rework" / going backward honestly.** Repairs don't always move forward — a part might fail testing and go back to "Under Repair." Rather than blocking this or letting percentage silently drop (which looks broken to a customer watching live), require a short reason when percentage decreases, and show it transparently on the customer timeline ("Part failed testing, reinstalling — 65% → 55%"). Customers trust honesty about a hiccup way more than a progress bar that mysteriously reverses with no explanation.

3. **Separate "internal" vs "customer-visible" updates.** Staff will naturally want to log things like "waiting on tech B to double check soldering" — useful for the team, not useful (or reassuring) for the customer. A visibility toggle avoids TMI and keeps the customer-facing timeline clean and confidence-inspiring.

4. **Make it genuinely live, not just refresh-based.** Since you said "live," I'd actually wire up WebSockets (Laravel Reverb, which is free/self-hosted and built for exactly this) rather than having the customer manually refresh. A parent checking their kid's laptop repair leaving the page open and watching the bar move as photos appear is a much stronger experience than a static page.

5. **Add an estimated time remaining, not just percentage.** "62%" alone doesn't tell someone if that's 20 minutes or 2 days away. Even a rough estimate based on historical average repair time for similar device/issue combos adds a lot of perceived value for very little engineering effort.

6. **Auto-compress and thumbnail photos.** Techs will be uploading straight from phone cameras — often 4-8MB HEIC/JPEG files. Without compression, your storage costs and page load times balloon fast. Compress on upload, and generate small thumbnails for the timeline (full photo opens on tap).

7. **Consider a before/after slider for the final update.** When the repair completes, showing a side-by-side or slider comparison of the device's condition at intake vs. release is a great trust/marketing moment — customers love seeing "look how clean/fixed it looks now" and it's a small addition on top of photos you're already storing.

8. **Let the customer react or approve at key milestones (optional but powerful).** For example, if a staff update includes "found additional issue, +₱40 repair cost" at the diagnosis stage, let the customer tap "Approve" directly from the tracking page instead of you having to call them. This closes a real operational gap (waiting on customer approval) using infrastructure you're already building.

If you want, I can fold the "approve additional cost" idea into this same prompt as an optional module, or keep this feature scoped tight and add it as its own separate prompt later — your call.
