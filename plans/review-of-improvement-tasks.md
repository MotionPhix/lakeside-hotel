# Review: `plans/README.md` — Improvement Tasks

**Reviewed:** 2026-09-30
**Reviewed against:** working tree at `e52fe59` + uncommitted changes
**Verdict:** Proceed, but **not as written.** Three of thirteen tasks are already
done, one is overstated, one carries money risk, and the execution notes contain a
command that does not exist.

---

## 1. Method

Every task was checked against the code rather than read on trust. Where the audit
named a file, line or class, that was opened. The counts below are from the tree,
not from the document.

---

## 2. Tasks that should be withdrawn — the work is already there

### TASK-004 — "Extract duplicate outstanding balance logic" — **incorrect**

The audit says the balance arithmetic is duplicated in `BookingController` and
`HotelMetrics` and recommends extracting it to `Booking::outstandingBalance()`.

It already exists, under a different name. `Booking::balance()` (line 216) is the
single implementation, and **both** callers already call it:

```php
// BookingController::outstanding()
max($bookings->sum(fn (Booking $booking): float => (float) $booking->balance()), 0)

// HotelMetrics::outstanding()
max($balances->sum(fn (Booking $booking): float => (float) $booking->balance()), 0)
```

There is no duplicated formula to remove, and the method the audit recommends
creating is the one it is already calling.

**What is genuinely duplicated** is the nine-line _aggregate wrapper_ — the
`holdingInventory()->get(['total', 'amount_paid'])`, the sum, the `max(…, 0)` and
the `number_format`. That is a real, smaller finding, and it belongs in one place —
but it is a different change from the one described, and it carries no risk of the
two drifting into different balance _formulas_, which is the hazard the audit
implied.

### TASK-005 — "Add tests for admin booking lifecycle" — **already covered**

The audit lists six behaviours as untested. `tests/Feature/Admin/BookingManagementTest.php`
already tests every one of them:

| Audit says untested          | Existing test                                                                                                         |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Confirm a pending booking    | "confirming a pending booking records when it was confirmed"                                                          |
| Cancel a booking             | "cancelling a booking gives its room back"                                                                            |
| Check-in a confirmed booking | "checking in and out records both moments"                                                                            |
| Check-out                    | "checking in and out records both moments"                                                                            |
| No-show before/after arrival | "cannot be marked as a no-show before the arrival date" + "can be marked once the arrival date has come"              |
| Invalid transitions          | "the lifecycle refuses every move that does not apply, and offers none" + "a guest cannot be checked in from pending" |

The only item not explicitly covered is cancellation _with and without a reason_.
Rated "M" effort for work that is ~95% done.

### TASK-010 — "Write tests for settings/profile updates" — **already covered, and self-contradictory**

The audit states these flows "have no test coverage" and then instructs the reader
to "follow patterns in existing `ProfileUpdateTest.php` and `SecurityTest.php`".
Both files exist and between them already test:

- profile page renders and profile information can be updated
- email verification status is unchanged when the email is unchanged (the
  re-verification concern the audit raises)
- password update, and that the correct password is required
- password confirmation enforcement
- security page rendering with two-factor enabled and disabled

The genuine gap is narrow: **two-factor enable/disable transitions**. That is a
small task, not a new test file.

---

## 3. Tasks whose claims are overstated

### TASK-002 — "Fix N+1 in admin booking detail" — real, but not "20+ queries"

`->bookings()->count()` exists at line **297**, not 250, inside
`private function detail(Booking $booking)`. That method has exactly one caller:

```
95: 'booking' => $this->detail($booking),   // show()
```

So it is **one** extra query on a single-booking page, not "20+ queries reduced to
1". The stated impact ("High") is not supportable, and the fix — `withCount` — is a
one-line tidy-up rather than a performance rescue.

Worth noting the pattern is already used elsewhere in the codebase
(`GuestController` line 40 uses `withCount('bookings')`), so the correction is also
consistency rather than novelty.

### TASK-001 — indexes — **mostly correct, one item already indexed**

Verified against the migrations:

| Column                                      | Audit says | Reality                                                                                               |
| ------------------------------------------- | ---------- | ----------------------------------------------------------------------------------------------------- |
| `bookings.check_in`                         | unindexed  | ✅ correct — plain `date`, no index                                                                   |
| `bookings.created_at`                       | unindexed  | ✅ correct                                                                                            |
| `booking_items.room_type_id`                | unindexed  | ❌ **already indexed** — declared `foreignId()->constrained()`, and the foreign key creates the index |
| `availability_blocks.starts_on` / `ends_on` | unindexed  | ✅ correct — but the composite index recommended is the right shape                                   |

Adding an index to `booking_items.room_type_id` would be a redundant index on a
column the foreign key already covers. The other three are worth doing.

---

## 4. Tasks verified as correct

- **TASK-003** — confirmed. `HomeController::diningVenues()` runs
  `DiningVenue::query()->active()->get()` and maps each through
  `SitePresenter::diningVenue($venue, true)`, which loads menu items per venue.
  A genuine N+1 with a correct fix.
- **TASK-006** — confirmed. `ResourceController::mediaFor()` loops records and
  calls `getMedia()` per record per collection.
- **TASK-009** — consistent with an existing convention (`withCount` in
  `GuestController`), low risk.
- **TASK-007**, **TASK-013** — plausible on reading; not exhaustively verified and
  should be re-checked before implementation.
- **TASK-012** — honestly self-limited ("defer until 1000+ guests"). Fine as
  deferred; no action.

---

## 5. The one task that carries money risk

### TASK-011 — rate limiting the payment webhook — **do not do this as written**

`routes/web.php:15` is a public, unauthenticated `POST webhooks/paychangu`
performed by PayChangu, not by a browser.

Adding `throttle:` means that when PayChangu retries a delivery — or sends a
legitimate burst — the endpoint answers **429**, and the payment confirmation is
never processed. The failure mode is the worst kind: **the guest has been charged
and the booking still reads unpaid.** It is silent, it is intermittent, and it
lands on a guest.

The stated rationale — "prevent brute-force signature attempts" — does not hold
either. If the signature is validated with a constant-time comparison, flooding the
endpoint does not help an attacker recover the secret, and a per-IP request limit
does not protect a shared secret. The real exposure of a public POST route is
resource consumption, which belongs at the edge (WAF/CDN), not in a Laravel
middleware that cannot tell a retry from an attack.

**Recommendation:** drop TASK-011, or replace it with what actually protects this
route — alerting when signature validation fails repeatedly, and an edge-level
limit with a deliberate allowance for provider retries. If it proceeds as written,
it needs an explicit exemption for known provider IPs and a generous burst, plus a
test that a retry is accepted.

---

## 6. Defects in the document itself

1. **`php artisan types:check` does not exist.** Verified:
   `ERROR There are no commands defined in the "types" namespace`.
   The PHP static analyser here is **Larastan/PHPStan** (`larastan/larastan ^3.9`,
   `composer phpstan` → `phpstan analyse`), and level 7 is confirmed in
   `phpstan.neon`. Note `npm run types:check` _does_ exist but is a different tool
   entirely (`tsc --noEmit`, TypeScript). The two should not be conflated.
2. **Self-contradiction in TASK-010** (section 2 above).
3. **Line numbers are unreliable.** TASK-002 cites line 250 for an expression at 297. Line numbers should not be treated as coordinates.
4. **`--compact` and `pint --dirty --format` are both valid** — checked, these two
   notes are fine.
5. **Ambiguous baseline.** The header pins "Commit `e52fe59`", but the working tree
   carries substantial uncommitted work (below), so it is not clear what state was
   actually audited. Related: the audit does not record a test baseline. The suite
   is currently **453 tests, green**, which any performance claim should be measured
   against.
6. **Effort and impact labels are not calibrated.** "High impact" is claimed for a
   one-query fix and "M" for tests that exist.

---

## 7. Missing information

- **No security or authorisation review.** The audit is performance and test
  coverage. For a system holding guest records, payments and staff accounts that is
  the more consequential half, and it is absent.
- **No accessibility review**, despite a public site with image alt text and form
  labelling that were recently reworked.
- **The audit predates the tax work** and does not know about it. Two consequences:
    - **TASK-008 (cache settings) is now more valuable than it was rated.**
      `Tax::vatRate()` reads a setting on every price calculation, and a room list
      prices every room. Caching is no longer a general nicety.
    - TASK-008 gains an integration point the audit could not know about: the cache
      must be invalidated by the new `App\Http\Controllers\Admin\SettingController::update()`,
      or an edit in the dashboard will not take effect until the cache expires. Any
      caching task must include that, or it will produce exactly the class of bug that
      is hardest to diagnose — "the rate I set is not the rate being charged".
- **A defect I introduced and should own:** `SettingSchema::forDisplay()` calls
  `Setting::value()` once per field, so the new system settings screen issues ~37
  queries to paint itself. It is the same problem as TASK-008, in the code I just
  wrote, and it should be fixed with it rather than separately.
- **No coverage of the open project backlog.** The board still holds unfinished
  acceptance work — placeholder imagery pending real photographs, and the remaining
  admin modules — which is a better guide to what to do next than a fresh list of
  micro-optimisations.

---

## 8. Recommendation

**Proceed, with the scope corrected first.** The audit is useful and a good half of
it is sound; but implementing it as written would spend effort re-doing finished
work and would ship one change that can lose a guest's payment.

**Before starting anything, correct the document** — cheap, and it prevents the
wasted effort:

1. Withdraw TASK-004, TASK-005 and TASK-010 (re-scope TASK-010 to two-factor
   transitions alone).
2. Correct TASK-001 (drop `booking_items.room_type_id`) and TASK-002 (correct
   impact).
3. Replace `php artisan types:check` with the real command.
4. Hold TASK-011 pending a decision, with the risk stated.

**Then, in this order:**

| Order | Work                                                                        | Why                                                                    |
| ----- | --------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| 1     | TASK-001 (corrected), TASK-003, TASK-006                                    | Real wins, low risk, no user-visible change                            |
| 2     | TASK-008 **+ the settings-screen N+1**                                      | Same problem; now higher value because pricing reads settings per room |
| 3     | TASK-002, TASK-009, TASK-004's aggregate wrapper                            | Small, consistency-driven                                              |
| 4     | TASK-005's cancellation-with-reason case, TASK-010's two-factor transitions | Fill the genuine slivers                                               |
| 5     | TASK-007, TASK-013                                                          | Only if the CMS actually grows                                         |
| —     | TASK-012                                                                    | Already deferred correctly; leave it                                   |

**Separately, and more urgent than any of the above:** the working tree carries
uncommitted work from 24–29 September — roughly 420 changed lines across
`admin/bookings/index.tsx` and `admin/guests/index.tsx`, layout and `vite.config.ts`
edits, changed favicon/wordmark binaries, plus an untracked web-app manifest set.
Nothing in the audit notices this, and until it is committed or discarded the
repository does not describe what is deployed. Decide its fate before adding more
to the pile.
