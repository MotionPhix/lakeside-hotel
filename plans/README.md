# Lakeside Hotel — Improvement Tasks

**Audit Date:** 2026-09-30  
**Commit:** `e52fe59`  
**Status:** Ready for implementation

---

## HIGH PRIORITY

### TASK-001: Add Missing Database Indexes
- **Status:** [x] Done — `2026_10_28_090000_add_booking_lookup_indexes`
- **Priority:** High
- **Effort:** S (Small)
- **Impact:** High — eliminates full table scans on booking lookups
- **Files:** `database/migrations/` (new migration)
- **Description:**
  Add indexes to columns used in WHERE clauses but currently unindexed:
  - `bookings.check_in` — used in `arrivingOn()`, `departingOn()`, `coveringDate()` scopes
  - `bookings.created_at` — used in `revenue()` metric query
  - `booking_items.room_type_id` — used in `AvailabilityService::availableRooms()`
  - `availability_blocks.starts_on` and `ends_on` — used in `overlapping()` scope (composite index)
- **Done when:** Migration created, indexes verified via `php artisan migrate` and query plan inspection
- **Corrections made while implementing:**
  - `booking_items.room_type_id` was **already indexed** — MySQL builds a btree for the foreign key (`booking_items_room_type_id_foreign`). An explicit index there would be a duplicate that only costs writes, so it was left out. This bullet of the audit was wrong.
  - The indexes on `check_in` and `created_at` would have been **dead weight**: `arrivingOn()`/`departingOn()` used `whereDate()` and `revenue()` used `whereDate('created_at')`, which wrap the column in `DATE()` and cannot use an index. Both now use a bare date / range. Verified by `EXPLAIN`: `ALL` + `key NULL` before, `ref`/`range` on the new index after.
  - `check_out` was added too — `departingOn()` and `coveringDate()` filter on it by the same argument.

---

### TASK-002: Fix N+1 in Admin Booking Detail
- **Status:** [x] Done
- **Priority:** High
- **Effort:** S
- **Impact:** High — reduces 20+ queries to 1 on booking detail page
- **Files:** `app/Http/Controllers/Admin/BookingController.php`
- **Description:**
  Line 250 triggers a separate query for each booking's guest stay count:
  ```php
  'stays' => $booking->guest->bookings()->count(),
  ```
  Fix: Eager load with `withCount('bookings')` on the guest relation, or use a subquery in the select.
- **Done when:** `stays` count is returned without triggering an extra query per booking
- **Correction:** the audit claimed 20+ queries. `detail()` renders **one** booking, so the honest saving is a single query, not twenty. The count now arrives via `withCount('bookings')` on the eager-loaded guest. Worth doing anyway: the figure would otherwise silently render `null` if the eager load were later dropped, so a test pins it.

---

### TASK-003: Fix N+1 in Homepage Dining Venues
- **Status:** [x] Done
- **Priority:** High
- **Effort:** S
- **Impact:** High — eliminates N+1 per dining venue on homepage
- **Files:** `app/Http/Controllers/Site/HomeController.php`
- **Description:**
  `diningVenues()` loads venues without eager loading menu items:
  ```php
  DiningVenue::query()->active()->get()
  ```
  Then calls `SitePresenter::diningVenue($venue, true)` which loads `menuItems()` per venue.
  Fix: Add `->with('menuItems')` to the query.
- **Done when:** Homepage loads with single query for venues + menu items
- **Correction:** the suggested `->with('menuItems')` would have **changed behaviour**. The presenter filtered to `available()`, so a plain eager load would have put sold-out dishes back on the homepage. Added an `availableMenuItems` relation carrying the same filter and eager loaded that. Measured: 3 venues now cost **1** menu query (was 3), and the query still carries `is_available = ?` scoped to all three ids.

---

### TASK-004: Extract Duplicate Outstanding Balance Logic
- **Status:** [x] Done — `Booking::outstandingTotal()`
- **Priority:** High
- **Effort:** S
- **Impact:** Medium — removes code duplication, single source of truth
- **Files:** `app/Http/Controllers/Admin/BookingController.php`, `app/Support/HotelMetrics.php`, `app/Models/Booking.php`
- **Description:**
  Outstanding balance calculation is duplicated in:
  - `BookingController::outstanding()` (line 285-295)
  - `HotelMetrics::outstanding()` (line 173-185)
  Fix: Extract to a static method on `Booking` model (e.g., `Booking::outstandingBalance()`).
- **Done when:** Both callers use the shared method; no logic duplication remains
- **Constraint honoured:** the per-booking clamp (`max(total - amount_paid, 0)` each, then a floor of zero on the sum) was preserved. A tidier-looking `SUM(total - amount_paid)` in SQL would let an overpayment on one booking cancel out another guest's debt and report less than is owed.

---

### TASK-005: Add Tests for Admin Booking Lifecycle
- **Status:** [x] Done
- **Priority:** High
- **Effort:** M (Medium)
- **Impact:** High — protects critical state machine from regressions
- **Files:** `tests/Feature/Admin/BookingManagementTest.php` (extend existing)
- **Description:**
  The booking lifecycle state machine (`BookingLifecycle`) has no test coverage for:
  - Confirm a pending booking
  - Cancel a booking (with/without reason)
  - Check-in a confirmed booking
  - Check-out a checked-in booking
  - No-show marking (before/after arrival date)
  - Invalid transitions (e.g., confirm a cancelled booking)
  Follow existing test patterns in `BookingEngineTest.php`.
- **Done when:** All lifecycle transitions and edge cases have passing tests
- **What was actually missing:** most of this list was **already covered** — confirm, cancel with reason, check-in, check-out, no-show both sides of the arrival date, and the blanket refusal map all had tests. Added the genuine gaps: cancelling with no reason, confirming a cancelled booking, and cancelling a guest already in house. Also pinned the guest stay count so TASK-002 cannot silently regress to `null`.

---

## MEDIUM PRIORITY

### TASK-006: Fix N+1 in ResourceController Media Loading
- **Status:** [x] Done
- **Priority:** Medium
- **Effort:** S
- **Impact:** Medium — reduces queries on CMS content lists
- **Files:** `app/Http/Controllers/Admin/ResourceController.php`
- **Description:**
  `mediaFor()` iterates records and loads media separately per record per collection:
  ```php
  foreach ($records as $record) {
      $collections[$collection] = $record->getMedia($collection)
  ```
  Fix: Use Spatie's `loadMedia()` on the collection, or eager load media before iterating.
- **Done when:** CMS index pages load media with minimal queries
- **Measured:** on `/admin/content/dining-venues`, media queries went from **3** (one per record) to **1** — `select * from media where model_id in (1, 2, 3)` — and total queries from 5 to 3. The fix is `with('media')` on the index query; Spatie's `getMedia()` reads the loaded relation and only falls back to querying when it is absent. Both sides now go through `carriesMedia()`, which also checks the trait exists, because `with('media')` throws on a model that never got it.

---

### TASK-007: Optimize ResourceController Move Operation
- **Status:** [x] Done
- **Priority:** Medium
- **Effort:** M
- **Impact:** Medium — improves CMS performance for large lists
- **Files:** `app/Http/Controllers/Admin/ResourceController.php`
- **Description:**
  `move()` loads ALL records to reorder two:
  ```php
  $ordered = $definition->model::query()->orderBy(...)->get()->all();
  ```
  Then saves every record after swapping. For 100+ items, this is wasteful.
  Fix: Use database-level position swapping or only update affected rows.
- **Done when:** Move operation only updates necessary rows
- **Implemented, with a complication the task did not anticipate:** `sort_order` is `unsignedSmallInteger` defaulting to **0**, so every freshly created record ties with every other one. Exchanging two equal numbers changes nothing, and the move would have failed silently. `move()` now finds its neighbour by `(sort_order, id)` — so a tie still has exactly one defined neighbour — and exchanges the two values, which is two writes instead of one per row. Only when the two values are equal does it fall back to numbering the list. A test asserts the two-write path; another pins the tie path.

---

### TASK-008: Cache Hotel Settings
- **Status:** [ ] Pending
- **Priority:** Medium
- **Effort:** S
- **Impact:** Medium — reduces database queries on every page load
- **Files:** `app/Models/Setting.php`, `app/Support/SitePresenter.php`
- **Description:**
  `Setting::value()` is called on every request for hotel configuration (currency, rates, contact info). Each call hits the database.
  Fix: Cache settings with `Cache::remember()`, invalidate on update in the CMS.
- **Done when:** Settings are cached; CMS edits invalidate cache

---

### TASK-009: Eager Load Room Counts in SitePresenter
- **Status:** [ ] Pending
- **Priority:** Medium
- **Effort:** S
- **Impact:** Low-Medium — reduces queries on room detail pages
- **Files:** `app/Support/SitePresenter.php`, `app/Http/Controllers/Site/RoomController.php`
- **Description:**
  `roomType()` calls `$roomType->rooms()->count()` when `$withDetail = true`, triggering a COUNT query per room type.
  Fix: Use `withCount('rooms')` in controllers that pass `$withDetail = true`.
- **Done when:** Room detail pages don't trigger extra COUNT queries

---

### TASK-010: Write Tests for Settings/Profile Updates
- **Status:** [ ] Pending
- **Priority:** Medium
- **Effort:** S
- **Impact:** Medium — protects auth-related user flows
- **Files:** `tests/Feature/Settings/` (new test file)
- **Description:**
  Profile update and settings changes have no test coverage:
  - Profile update (name, email, phone)
  - Email change requires re-verification
  - Two-factor enable/disable
  Follow patterns in existing `ProfileUpdateTest.php` and `SecurityTest.php`.
- **Done when:** Settings/profile flows have passing tests

---

## LOW PRIORITY

### TASK-011: Add Rate Limiting to Payment Webhook
- **Status:** [ ] Pending
- **Priority:** Low
- **Effort:** S
- **Impact:** Low — prevents abuse of public webhook endpoint
- **Files:** `routes/web.php`
- **Description:**
  The PayChangu webhook endpoint has no rate limiting. While it validates signatures, rate limiting would prevent brute-force signature attempts.
  Fix: Add `throttle:` middleware to the webhook route.
- **Done when:** Webhook endpoint has rate limiting

---

### TASK-012: Improve Search Index Usage
- **Status:** [ ] Pending
- **Priority:** Low
- **Effort:** M
- **Impact:** Low — relevant at scale (1000+ guests/users)
- **Files:** `app/Models/User.php`, `app/Models/Guest.php`
- **Description:**
  Search scopes use `LIKE %term%` with leading wildcard, preventing index usage:
  ```php
  ->where('name', 'like', "%{$term}%")
  ```
  For production scale, consider full-text indexes or a search service (Laravel Scout).
  Note: Acceptable for current scale; defer until guest count exceeds 1000.
- **Done when:** Search uses indexes or external search service (if needed at scale)

---

### TASK-013: Add CMS Resource Controller Tests
- **Status:** [ ] Pending
- **Priority:** Low
- **Effort:** M
- **Impact:** Medium — protects generic CMS layer
- **Files:** `tests/Feature/Admin/ContentManagementTest.php` (extend existing)
- **Description:**
  The generic `ResourceController` serves 17 content types but has minimal test coverage. Add tests for:
  - CRUD operations via the generic routes
  - Permission enforcement per resource type
  - Media upload/destroy
  - Publish toggle
  - Sort order operations
- **Done when:** CMS resource operations have passing tests

---

## DIRECTION (Future Considerations)

### DIR-001: Add Event Dispatching to Booking Lifecycle
- **Status:** [ ] Deferred
- **Priority:** Future
- **Description:**
  Consider dispatching events from `BookingLifecycle` for:
  - Audit logging (who changed what and when)
  - Notification triggers (email/SMS on status changes)
  - Integration hooks (channel manager, revenue management)
  This would decouple side effects from state transitions.

### DIR-002: Document CMS Resource Registry Contract
- **Status:** [ ] Deferred
- **Priority:** Future
- **Description:**
  The `ResourceRegistry` and `ResourceDefinition` classes power the generic CMS. Consider:
  - Adding type-safe field definition helpers
  - Documenting the registration contract for new content types
  - Adding integration tests that verify the full CMS workflow

---

## Execution Notes

- **Dependency:** TASK-001 (indexes) should land first — other performance fixes benefit from it
- **Dependency:** TASK-004 (extract logic) should precede TASK-005 (tests) to avoid testing duplicated code
- **Verification:** Run `php artisan test --compact` after each task
- **Formatting:** Run `vendor/bin/pint --dirty --format agent` after PHP changes
- **Types:** Run `composer phpstan` (Larastan, level 7). `php artisan types:check` **does not exist** — `npm run types:check` is the TypeScript check and a different tool.

### Pass log

**2026-10-28 — all five high-priority tasks.** TASK-001 → TASK-005 complete.
Three of the audit's prescriptions were wrong or incomplete and were corrected
in place above (a redundant index, a behaviour-changing eager load, and an
overstated N+1). The remaining medium/low tasks were not touched.

Observation, out of scope: the public homepage still runs ~209 queries in total,
1 of which is now menu-related. The bulk is elsewhere (site sections, media) and
is a separate, larger job than anything on this list.
