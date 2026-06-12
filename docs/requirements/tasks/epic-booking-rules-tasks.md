# Implementation Tasks: epic-booking-rules

**Requirement:** `docs/working_docs/epics/epic-booking-rules/requirements.md`
**Solution Design:** `docs/working_docs/epics/epic-booking-rules/design.md`
**Created:** 2026-06-11
**Total Tasks:** 22
**Estimated Complexity:** M

---

## Summary

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Config | 1 | S |
| Domain (MembershipPlan) | 3 | S–M |
| Infrastructure (MembershipPlan) | 2 | S |
| Domain (Booking) | 4 | S–M |
| Infrastructure (Booking) | 2 | M |
| Application | 4 | M |
| HTTP Layer | 3 | S |
| Tests | 3 | M |
| Frontend | 2 | S–M |

---

## Phase 0: Configuration

### TASK-001: Set APP_TIMEZONE in backend .env

**Phase:** Config
**Complexity:** S
**Dependencies:** None

**Description:**
Add `APP_TIMEZONE=Europe/Madrid` to `backend/.env` so that `now()` and `new \DateTimeImmutable()` in handlers use Spain local time (CET/CEST) for session cutoff checks.

**File:** `backend/.env`

**Acceptance Criteria:**
- [ ] `APP_TIMEZONE=Europe/Madrid` present in `backend/.env`
- [ ] `php artisan tinker --execute="echo now()->timezone;"` outputs `Europe/Madrid`

---

## Phase 1: Domain Layer — MembershipPlan changes

### TASK-002: Add MAX_WEEKLY_SESSIONS constant to MembershipPlanTable

**Phase:** Domain / Infrastructure
**Complexity:** S
**Dependencies:** None

**Description:**
Add `MAX_WEEKLY_SESSIONS = 'max_weekly_sessions'` constant to the existing `MembershipPlanTable` class so infrastructure references are string-safe.

**File:** `backend/src/Core/Member/Infrastructure/Tables/MembershipPlanTable.php`

**Acceptance Criteria:**
- [ ] `public const MAX_WEEKLY_SESSIONS = 'max_weekly_sessions';` added

---

### TASK-003: Add maxWeeklySessions to MembershipPlan entity

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-002

**Description:**
Add `public readonly int $maxWeeklySessions` to the `MembershipPlan` entity constructor. This is the weekly session cap enforced during booking creation.

**File:** `backend/src/Core/Member/Domain/Entities/MembershipPlan.php`

**Acceptance Criteria:**
- [ ] `$maxWeeklySessions` property added as `readonly int`
- [ ] Constructor updated to accept and assign it

---

### TASK-004: Update MembershipPlanHydrator to map max_weekly_sessions

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-003

**Description:**
Update the `MembershipPlanHydrator` to read `max_weekly_sessions` from the DB model and pass it to the `MembershipPlan` entity constructor.

**File:** `backend/src/Core/Member/Infrastructure/Hydrators/MembershipPlanHydrator.php`

**Acceptance Criteria:**
- [ ] `hydrate()` maps `MembershipPlanTable::MAX_WEEKLY_SESSIONS` → `$plan->maxWeeklySessions`
- [ ] No other hydration logic changed

---

## Phase 2: Infrastructure — MembershipPlan migration + seeder

### TASK-005: Migration — add max_weekly_sessions to membership_plans

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-002

**Description:**
Create a new migration that adds `max_weekly_sessions TINYINT UNSIGNED NOT NULL DEFAULT 0` to the `membership_plans` table.

**File:** `backend/database/migrations/2026_06_11_000030_add_max_weekly_sessions_to_membership_plans.php`

**Acceptance Criteria:**
- [ ] Column added: `max_weekly_sessions TINYINT UNSIGNED NOT NULL DEFAULT 0`
- [ ] `down()` drops the column
- [ ] Migration runs without error: `php artisan migrate`

---

### TASK-006: Update MembershipPlanSeeder with max_weekly_sessions values

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-005

**Description:**
Update `MembershipPlanSeeder` to set `max_weekly_sessions` for each plan:
- `plan-2-dias` → `2`
- `plan-3-dias` → `3`
- `plan-4-5-dias` → `5`

**File:** `backend/database/seeders/MembershipPlanSeeder.php`

**Acceptance Criteria:**
- [ ] All 3 plans seeded with correct `max_weekly_sessions` values
- [ ] Seeder is idempotent (uses `updateOrInsert`)
- [ ] Running `php artisan db:seed --class=MembershipPlanSeeder` twice doesn't duplicate rows

---

## Phase 3: Domain Layer — Booking changes

### TASK-007: Add SESSION_DATE constant to BookingTable + update Booking entity and BookingRM

**Phase:** Domain / Infrastructure
**Complexity:** S
**Dependencies:** None

**Description:**
Three small additions in the Booking layer:
1. Add `SESSION_DATE = 'session_date'` to `BookingTable`
2. Add `public readonly \DateTimeImmutable $sessionDate` to `Booking` entity constructor and `Booking::create()` factory
3. Add `public string $sessionDate` to `BookingRM` (format `Y-m-d`)

**Files:**
- `backend/src/Core/Booking/Infrastructure/Tables/BookingTable.php`
- `backend/src/Core/Booking/Domain/Entities/Booking.php`
- `backend/src/Core/Booking/Domain/ReadModels/BookingRM.php`

**Acceptance Criteria:**
- [ ] `BookingTable::SESSION_DATE = 'session_date'` added
- [ ] `Booking::create()` accepts `\DateTimeImmutable $sessionDate` and stores it
- [ ] `BookingRM` has `public string $sessionDate`

---

### TASK-008: Create domain exceptions for new booking rules

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create three new domain exception classes for the new business rules:

1. `WeeklyLimitReachedException` — thrown when member exceeds weekly plan limit. Must carry `$used` and `$max` so the action can expose them in the response.
2. `CancellationWindowExpiredException` — thrown when trying to cancel a past/started session.
3. `MemberHasNoPlanException` — thrown when member has no active plan assignment.

**Files:**
- `backend/src/Core/Booking/Domain/Exceptions/WeeklyLimitReachedException.php`
- `backend/src/Core/Booking/Domain/Exceptions/CancellationWindowExpiredException.php`
- `backend/src/Core/Booking/Domain/Exceptions/MemberHasNoPlanException.php`

**Acceptance Criteria:**
- [ ] `WeeklyLimitReachedException` has `__construct(int $used, int $max)` with public readonly props
- [ ] `CancellationWindowExpiredException` and `MemberHasNoPlanException` extend `\RuntimeException`

---

### TASK-009: Create SessionDateResolver domain service

**Phase:** Domain
**Complexity:** M
**Dependencies:** None

**Description:**
Create a pure domain service `SessionDateResolver` that resolves the concrete calendar `DATE` for the next upcoming occurrence of a `DayOfWeek + TimeSlot` pair, given the current datetime.

**File:** `backend/src/Core/Booking/Domain/Services/SessionDateResolver.php`

**Method signature:**
```php
public function resolve(DayOfWeek $dayOfWeek, TimeSlot $timeSlot, \DateTimeImmutable $now): \DateTimeImmutable
```

**Logic:**
```
Map DayOfWeek enum → ISO integer (monday=1 … friday=5)
$diff = $targetIso - (int)$now->format('N')
$candidate = $now->modify("$diff days")->setTime(0, 0, 0)
[$h, $m] = explode(':', $timeSlot->value)
$candidateDatetime = $candidate->setTime($h, $m, 0)
if ($candidateDatetime <= $now) → $candidate = $candidate->modify('+7 days')
return $candidate  // DateTimeImmutable at 00:00:00 of the resolved date
```

**Test cases (from requirements UC-003):**
- Now=Wednesday 14:00, session=Monday 07:45 → next Monday
- Now=Monday 07:00, session=Monday 07:45 → this Monday
- Now=Monday 08:00, session=Monday 07:45 → next Monday
- Now=Wednesday 14:00, session=Friday 18:45 → this Friday

**Acceptance Criteria:**
- [ ] All 4 test cases produce correct output
- [ ] Returns `\DateTimeImmutable` with time 00:00:00

---

### TASK-010: Add 3 new methods to BookingRepositoryInterface

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-007

**Description:**
Add three new methods to `BookingRepositoryInterface`:

```php
// Duplicate check scoped to a specific session_date
public function findByMemberSessionAndDate(
    MemberId $memberId, ClassSessionId $sessionId, \DateTimeImmutable $sessionDate
): ?Booking;

// Count confirmed bookings for a member in a date range (ISO week)
public function countConfirmedForMemberInWeek(
    MemberId $memberId, \DateTimeImmutable $weekStart, \DateTimeImmutable $weekEnd
): int;

// Get max_weekly_sessions from the member's most recent plan assignment (null = no plan)
public function findActivePlanMaxWeeklyForMember(MemberId $memberId): ?int;
```

**File:** `backend/src/Core/Booking/Domain/Repositories/BookingRepositoryInterface.php`

**Acceptance Criteria:**
- [ ] 3 methods added with correct signatures using Value Objects
- [ ] Existing methods unchanged

---

## Phase 4: Infrastructure — Booking migration + repository

### TASK-011: Migration — add session_date to bookings + change unique constraint

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-007

**Description:**
Create migration that:
1. Adds `session_date DATE NOT NULL DEFAULT '2026-01-01'` to `bookings` (after `class_session_id`)
2. Drops old unique key `uq_member_session` on `(member_id, class_session_id)`
3. Adds new unique key `uq_member_session_date` on `(member_id, class_session_id, session_date)`
4. Adds index `idx_b_member_date` on `(member_id, session_date, status)`

**File:** `backend/database/migrations/2026_06_11_000031_add_session_date_to_bookings.php`

**Acceptance Criteria:**
- [ ] `session_date DATE NOT NULL` added after `class_session_id`
- [ ] Old unique constraint `uq_member_session` dropped
- [ ] New unique constraint `uq_member_session_date` on `(member_id, class_session_id, session_date)` added
- [ ] New index `idx_b_member_date` added
- [ ] `down()` reverses all changes
- [ ] Migration runs without error

---

### TASK-012: Update BookingHydrator and BookingRepository for session_date

**Phase:** Infrastructure
**Complexity:** M
**Dependencies:** TASK-010, TASK-011

**Description:**
Two changes in the infrastructure layer:

**BookingHydrator:**
- `hydrate()`: read `session_date` → `new \DateTimeImmutable($model->session_date)`
- `dehydrate()`: include `BookingTable::SESSION_DATE => $booking->sessionDate->format('Y-m-d')`

**BookingRepository:**
- Update `getByIdRM()` SELECT to include `b.session_date`
- Update `findByMember()` SELECT to include `b.session_date`; update `BookingRM` construction to include `sessionDate`
- Implement `findByMemberSessionAndDate()`:
  ```sql
  WHERE member_id = ? AND class_session_id = ? AND session_date = ? AND status = 'confirmed'
  ```
- Implement `countConfirmedForMemberInWeek()`:
  ```sql
  WHERE member_id = ? AND status = 'confirmed' AND session_date BETWEEN ? AND ?
  ```
- Implement `findActivePlanMaxWeeklyForMember()`:
  ```sql
  SELECT mp.max_weekly_sessions
  FROM member_plan_assignments mpa
  JOIN membership_plans mp ON mp.id = mpa.membership_plan_id
  WHERE mpa.member_id = ?
  ORDER BY mpa.assigned_at DESC
  LIMIT 1
  ```

**Files:**
- `backend/src/Core/Booking/Infrastructure/Hydrators/BookingHydrator.php`
- `backend/src/Core/Booking/Infrastructure/Repositories/BookingRepository.php`

**Acceptance Criteria:**
- [ ] `hydrate()` reads `session_date` from model
- [ ] `dehydrate()` writes `session_date` as `Y-m-d` string
- [ ] `getByIdRM()` and `findByMember()` include `session_date` in SELECT and `BookingRM` construction
- [ ] All 3 new interface methods implemented
- [ ] Existing methods unchanged

---

## Phase 5: Application Layer

### TASK-013: Update CreateBookingCommand and CreateBookingHandler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-008, TASK-009, TASK-010, TASK-012

**Description:**
Update command and handler for booking creation with full new validation flow.

**CreateBookingCommand** — add `public readonly \DateTimeImmutable $sessionDate`

**CreateBookingHandler** — new flow:
1. Load session (existing)
2. Guard: session Active (existing)
3. Guard: capacity not exceeded (existing)
4. Resolve `session_date` via injected `SessionDateResolver` (NEW)
5. Guard: no duplicate for `(member, session, session_date)` using `findByMemberSessionAndDate()` (CHANGED)
6. Get `max_weekly_sessions` via `findActivePlanMaxWeeklyForMember()` → throw `MemberHasNoPlanException` if null (NEW)
7. Compute week bounds from `session_date` (Monday 00:00 → Sunday 23:59)
8. Count confirmed bookings in week via `countConfirmedForMemberInWeek()` (NEW)
9. Guard: count < max → throw `WeeklyLimitReachedException($count, $max)` if not (NEW)
10. `Booking::create($id, $memberId, $classSessionId, $sessionDate)` (CHANGED)
11. Save (existing)

**Files:**
- `backend/src/Core/Booking/Application/Commands/CreateBooking/CreateBookingCommand.php`
- `backend/src/Core/Booking/Application/Commands/CreateBooking/CreateBookingHandler.php`

**Acceptance Criteria:**
- [ ] `CreateBookingCommand` has `$sessionDate` property
- [ ] Handler injects `SessionDateResolver`
- [ ] Handler uses `findByMemberSessionAndDate()` for duplicate check (not old `findByMemberAndSession()`)
- [ ] Handler throws `WeeklyLimitReachedException` with correct `$used` and `$max`
- [ ] Handler throws `MemberHasNoPlanException` when no plan
- [ ] Handler passes `$sessionDate` to `Booking::create()`
- [ ] Handler returns void (CQRS rule)

---

### TASK-014: Update CancelBookingHandler with cancellation cutoff

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-008, TASK-012

**Description:**
Update `CancelBookingHandler` to check that the session hasn't started before allowing cancellation.

New flow after loading booking and ownership check:
1. Inject `ClassSessionRepositoryInterface` (new dependency)
2. Load session via `$this->sessionRepo->getById($booking->classSessionId)`
3. Compute `$sessionDatetime`: combine `$booking->sessionDate` (date) with `$session->timeSlot->value` (HH:MM) → `\DateTimeImmutable`
4. Get `$now = new \DateTimeImmutable('now', new \DateTimeZone(config('app.timezone', 'Europe/Madrid')))`
5. If `$now >= $sessionDatetime` → throw `CancellationWindowExpiredException`
6. `$booking->cancel()` (existing)
7. Save (existing)

**File:** `backend/src/Core/Booking/Application/Commands/CancelBooking/CancelBookingHandler.php`

**Acceptance Criteria:**
- [ ] `ClassSessionRepositoryInterface` injected
- [ ] Cutoff computed correctly as `session_date + time_slot`
- [ ] Cancellation of past sessions throws `CancellationWindowExpiredException`
- [ ] Cancellation of future sessions proceeds normally
- [ ] Existing ownership and status guards still present

---

### TASK-015: Update GetMemberBookingsHandler to include weekly stats

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-012

**Description:**
Update `GetMemberBookingsHandler` to also compute and return weekly booking stats for the current ISO week.

The handler already returns `BookingRM[]`. Wrap return in a value object or associative array:
```php
return [
    'bookings'     => $bookings,       // BookingRM[]
    'weekly_used'  => $usedThisWeek,   // int
    'weekly_max'   => $maxWeekly ?? 0, // int (0 if no plan)
];
```

Use `countConfirmedForMemberInWeek()` with current week's Monday–Sunday bounds.
Use `findActivePlanMaxWeeklyForMember()` for the limit.

**File:** `backend/src/Core/Booking/Application/Queries/GetMemberBookings/GetMemberBookingsHandler.php`

**Acceptance Criteria:**
- [ ] Handler returns structure with `bookings`, `weekly_used`, `weekly_max`
- [ ] `weekly_used` counts only confirmed bookings in current ISO week
- [ ] `weekly_max` is 0 when no plan assigned

---

## Phase 6: HTTP Layer

### TASK-016: Update BookingResource and BookingListResource

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-007, TASK-015

**Description:**
Two resource updates:

**BookingResource** — add `'session_date' => $this->rm->sessionDate` to JSON output.

**BookingListResource** — update to accept the new handler response structure and add top-level fields:
```json
{
  "weekly_used": 2,
  "weekly_max": 3,
  "data": [ ...bookings... ]
}
```

**Files:**
- `backend/app/Http/Actions/Booking/Shared/BookingResource.php`
- `backend/app/Http/Actions/Booking/Shared/BookingListResource.php`

**Acceptance Criteria:**
- [ ] `BookingResource` includes `session_date` in JSON
- [ ] `BookingListResource` includes `weekly_used` and `weekly_max` as top-level fields
- [ ] Existing fields unchanged

---

### TASK-017: Update CreateBookingAction to catch new exceptions

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-013, TASK-016

**Description:**
Add two new exception catches to `CreateBookingAction`:

```php
} catch (WeeklyLimitReachedException $e) {
    return response()->json([
        'error' => 'Has alcanzado el limite de reservas semanales de tu plan',
        'code'  => 'WEEKLY_LIMIT_REACHED',
        'used'  => $e->used,
        'max'   => $e->max,
    ], 422);
} catch (MemberHasNoPlanException) {
    return response()->json([
        'error' => 'No tienes un plan activo asignado',
        'code'  => 'NO_ACTIVE_PLAN',
    ], 422);
}
```

**File:** `backend/app/Http/Actions/Booking/Create/CreateBookingAction.php`

**Acceptance Criteria:**
- [ ] `WeeklyLimitReachedException` caught → 422 with `used` and `max` fields
- [ ] `MemberHasNoPlanException` caught → 422 with `NO_ACTIVE_PLAN` code
- [ ] Existing catches unchanged

---

### TASK-018: Update CancelBookingAction to catch CancellationWindowExpiredException

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-014

**Description:**
Add one new exception catch to `CancelBookingAction`:

```php
} catch (CancellationWindowExpiredException) {
    return response()->json([
        'error' => 'No puedes cancelar una reserva cuya sesion ya ha comenzado o pasado',
        'code'  => 'CANCELLATION_WINDOW_EXPIRED',
    ], 422);
}
```

**File:** `backend/app/Http/Actions/Booking/Cancel/CancelBookingAction.php`

**Acceptance Criteria:**
- [ ] `CancellationWindowExpiredException` caught → 422 with `CANCELLATION_WINDOW_EXPIRED` code
- [ ] Existing catches unchanged

---

## Phase 7: Tests

### TASK-019: Unit tests — SessionDateResolver (all edge cases)

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-009

**Description:**
Create unit tests for `SessionDateResolver` covering all 4 cases from UC-003:

| Input | Expected |
|-------|----------|
| now=Wednesday 14:00, session=Monday 07:45 | Next Monday |
| now=Monday 07:00, session=Monday 07:45 | This Monday |
| now=Monday 08:00, session=Monday 07:45 | Next Monday |
| now=Wednesday 14:00, session=Friday 18:45 | This Friday |

Also test: now=Friday 21:14, session=Friday 21:15 → this Friday (slot not yet passed by 1 min).

**File:** `backend/tests/Unit/Core/Booking/SessionDateResolverTest.php`

**Acceptance Criteria:**
- [ ] All 5 test cases written and passing
- [ ] Uses `DateTimeImmutable` with explicit timezone

---

### TASK-020: Unit tests — weekly limit and cancellation cutoff

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-013, TASK-014

**Description:**
Unit tests for the two new business rules:

**Weekly limit (CreateBookingHandler):**
- Member with 2-day plan: 1st and 2nd bookings succeed, 3rd throws `WeeklyLimitReachedException`
- Cancelled bookings don't count toward the weekly limit
- Member with no plan throws `MemberHasNoPlanException`

**Cancellation cutoff (CancelBookingHandler):**
- Cancel booking 1 hour before session → succeeds
- Cancel booking 1 second after session start → throws `CancellationWindowExpiredException`
- Cancel booking exactly at session start time → throws `CancellationWindowExpiredException`

**Files:**
- `backend/tests/Unit/Core/Booking/CreateBookingHandlerTest.php` (update existing or create)
- `backend/tests/Unit/Core/Booking/CancelBookingHandlerTest.php` (update existing or create)

**Acceptance Criteria:**
- [ ] Weekly limit scenarios tested with mocked repository
- [ ] Cancellation cutoff boundary tested (before/at/after)
- [ ] All tests pass

---

### TASK-021: Feature tests — updated booking endpoints

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-017, TASK-018

**Description:**
Update existing feature tests and add new scenarios:

**POST /api/member/bookings (CreateBooking feature test):**
- Add: returns 422 `WEEKLY_LIMIT_REACHED` when member has 2 bookings and plan allows 2
- Add: returns 422 `NO_ACTIVE_PLAN` when member has no plan
- Add: `session_date` present in 201 response
- Verify: existing capacity and duplicate tests still pass

**DELETE /api/member/bookings/{id} (CancelBooking feature test):**
- Add: returns 422 `CANCELLATION_WINDOW_EXPIRED` for booking with past `session_date`
- Verify: cancellation of future session still works

**GET /api/member/bookings (GetMemberBookings feature test):**
- Add: response includes `weekly_used` and `weekly_max`

**Files:**
- `backend/tests/Feature/Core/Booking/` (update existing test files)

**Acceptance Criteria:**
- [ ] All new scenarios covered
- [ ] All existing 183+ tests still pass
- [ ] PHPStan passes

---

## Phase 8: Frontend

### TASK-022: Frontend — disable Cancelar button for past sessions + show weekly quota

**Phase:** Frontend
**Complexity:** M
**Dependencies:** TASK-016

**Description:**
Two frontend changes using the new API response fields:

**MemberBookingsPage.tsx:**
- For each booking, compute `isPast(booking)`: `session_date + time_slot <= now()`
- Disable or hide the "Cancelar" button when `isPast` is true
- Show a "Sesion pasada" badge instead of the cancel button for past bookings

```ts
const isPast = (booking: Booking): boolean => {
  const [h, m] = booking.session.time_slot.split(':')
  const dt = new Date(booking.session_date)
  dt.setHours(parseInt(h), parseInt(m), 0)
  return dt <= new Date()
}
```

**MemberSchedulePage.tsx:**
- Use `weekly_used` and `weekly_max` from `GET /api/member/bookings` response
- Show quota badge: `Reservas esta semana: {weeklyUsed} / {weeklyMax}`
- Disable "Reservar" button in the schedule when `weeklyUsed >= weeklyMax`

**Files:**
- `frontend/src/pages/member/MemberBookingsPage.tsx`
- `frontend/src/pages/member/MemberSchedulePage.tsx`
- `frontend/src/types/booking.ts` (add `session_date` field + `weekly_used`/`weekly_max` to list response)

**Acceptance Criteria:**
- [ ] "Cancelar" button hidden/disabled for sessions that have already started or passed
- [ ] "Sesion pasada" indicator shown for past bookings
- [ ] Weekly quota displayed correctly in schedule view
- [ ] "Reservar" button disabled when weekly limit reached
- [ ] No TypeScript errors

---

## Final Checklist

- [ ] All 22 tasks completed in order
- [ ] `php artisan migrate` runs without error
- [ ] `php artisan db:seed --class=MembershipPlanSeeder` updates max_weekly_sessions correctly
- [ ] All tests pass: `docker-compose exec app php artisan test`
- [ ] PHPStan passes
- [ ] Frontend builds without TypeScript errors: `docker-compose exec node npm run build`
- [ ] Manual test: member with Plan 2 Dias cannot book a 3rd session in the same week
- [ ] Manual test: cannot cancel a booking for a session that started more than 1 hour ago
- [ ] Git commit + push
