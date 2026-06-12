# Solution Design: epic-booking-rules

**Requirement:** `docs/working_docs/epics/epic-booking-rules/requirements.md`
**Date:** 2026-06-11
**Bounded Context:** Core/Booking (primary) · Core/Member (read-only dependency)

---

## Summary

Three coordinated changes to the existing booking system:
1. Add `session_date` (DATE) to `bookings` table and `Booking` entity — the concrete calendar date of the class being booked.
2. Enforce weekly session limit: before creating a booking, count confirmed bookings for that ISO week and reject if `>= plan.max_weekly_sessions`.
3. Enforce cancellation cutoff: before cancelling, check that `session_date + time_slot > now()`.

Additionally, `MembershipPlan` gets a new `max_weekly_sessions` field. The unique constraint on `bookings` changes from `(member_id, class_session_id)` to `(member_id, class_session_id, session_date)`.

No new bounded contexts. No new routes. No new DB tables. All changes are additive modifications to existing layers.

---

## Architecture Decisions

**A. SessionDateResolver as a Domain Service (not Application layer)**
The date resolution logic (`day_of_week + time_slot + now → concrete date`) is pure business logic with no I/O. It belongs in `Domain/Services/`, is easily unit-tested, and can be reused by both the handler and tests.

**B. `max_weekly_sessions` via BookingRepository (cross-table JOIN inside Booking BC)**
Rather than injecting `MembershipPlanRepository` (Member BC) into the Booking handler (tight coupling), `BookingRepositoryInterface` exposes `findActivePlanMaxWeeklyForMember(MemberId): ?int`. The concrete `BookingRepository` does the JOIN against `member_plan_assignments + membership_plans` tables. This is the same pattern already used in the project (BookingRepository already JOINs `class_sessions + class_types`).

**C. Cancellation cutoff check in Handler (not Entity)**
The cutoff check requires the session's `timeSlot` (external data). The handler already accesses `ClassSessionRepositoryInterface`. The check is placed in `CancelBookingHandler` before calling `booking->cancel()`. The entity remains responsible only for the status transition guard.

**D. Weekly stats in GetMemberBookings response as metadata**
`weekly_bookings_used` and `weekly_bookings_max` are added as top-level fields in the member bookings list response — computed by `GetMemberBookingsHandler` alongside the bookings list. No new endpoint needed.

---

## Existing Code Analysis

| Component | Location | Reusable | Change |
|-----------|----------|----------|--------|
| `Booking` entity | `Core/Booking/Domain/Entities/Booking.php` | Partial | Add `$sessionDate` property |
| `BookingRM` | `Core/Booking/Domain/ReadModels/BookingRM.php` | Partial | Add `$sessionDate` |
| `BookingRepositoryInterface` | `Core/Booking/Domain/Repositories/` | Partial | Add 3 new methods |
| `BookingTable` | `Core/Booking/Infrastructure/Tables/` | Partial | Add `SESSION_DATE` constant |
| `BookingHydrator` | `Core/Booking/Infrastructure/Hydrators/` | Partial | Handle `session_date` |
| `BookingRepository` | `Core/Booking/Infrastructure/Repositories/` | Partial | Implement new methods + update duplicate check |
| `CreateBookingHandler` | `Core/Booking/Application/Commands/CreateBooking/` | Partial | Add session_date resolution + weekly limit check |
| `CreateBookingCommand` | same | Partial | Add `$sessionDate` param |
| `CancelBookingHandler` | `Core/Booking/Application/Commands/CancelBooking/` | Partial | Add cutoff check + ClassSessionRepo injection |
| `GetMemberBookingsHandler` | `Core/Booking/Application/Queries/` | Partial | Add weekly stats to response |
| `CreateBookingAction` | `app/Http/Actions/Booking/Create/` | Partial | Catch 2 new exceptions |
| `CancelBookingAction` | `app/Http/Actions/Booking/Cancel/` | Partial | Catch 1 new exception |
| `BookingResource` | `app/Http/Actions/Booking/Shared/` | Partial | Add `session_date` field |
| `BookingListResource` | same | Partial | Add weekly stats metadata |
| `MembershipPlan` entity | `Core/Member/Domain/Entities/MembershipPlan.php` | Partial | Add `$maxWeeklySessions` |
| `MembershipPlanTable` | `Core/Member/Infrastructure/Tables/` | Partial | Add `MAX_WEEKLY_SESSIONS` |
| `MembershipPlanHydrator` | `Core/Member/Infrastructure/Hydrators/` | Partial | Map new field |
| `MembershipPlanSeeder` | `database/seeders/` | Partial | Add values: 2, 3, 5 |
| `bookings` migration | `database/migrations/` | — | New migration: add `session_date` + change UNIQUE |
| `membership_plans` migration | `database/migrations/` | — | New migration: add `max_weekly_sessions` |
| `MemberBookingsPage.tsx` | `frontend/src/pages/member/` | Partial | Disable Cancelar for past sessions |
| `MemberSchedulePage.tsx` | `frontend/src/pages/member/` | Partial | Show weekly quota |

---

## Implementation Plan

### 1. Domain Layer

#### New: Domain Service
| Class | Path | Description |
|-------|------|-------------|
| `SessionDateResolver` | `src/Core/Booking/Domain/Services/SessionDateResolver.php` | Resolves the concrete DATE of the next occurrence of a `DayOfWeek + TimeSlot` from a given `now` |

```php
// Resolution logic:
// Map DayOfWeek enum → ISO number (Mon=1 … Fri=5)
// $diff = $targetDay - $now->format('N')
// $candidate = $now->modify("$diff days")->setTime(0,0,0)
// Build $candidateDatetime = $candidate + HH:MM from TimeSlot
// If $candidateDatetime <= $now → $candidate = $candidate->modify('+7 days')
// Return $candidate (DateTimeImmutable at 00:00:00)
```

#### Modified: Booking Entity
Add `public readonly \DateTimeImmutable $sessionDate` parameter.
`Booking::create()` factory receives `$sessionDate` as parameter.

#### New: Domain Exceptions
| Class | Path | HTTP code |
|-------|------|-----------|
| `WeeklyLimitReachedException` | `Domain/Exceptions/WeeklyLimitReachedException.php` | 422 |
| `CancellationWindowExpiredException` | `Domain/Exceptions/CancellationWindowExpiredException.php` | 422 |
| `MemberHasNoPlanException` | `Domain/Exceptions/MemberHasNoPlanException.php` | 422 |

#### Modified: BookingRM ReadModel
Add `public string $sessionDate` (format: `Y-m-d`).

#### Modified: BookingRepositoryInterface
Add 3 methods:
```php
// Duplicate check scoped to a specific date
public function findByMemberSessionAndDate(
    MemberId $memberId, ClassSessionId $sessionId, \DateTimeImmutable $sessionDate
): ?Booking;

// Count confirmed bookings for a member within a calendar week
public function countConfirmedForMemberInWeek(
    MemberId $memberId, \DateTimeImmutable $weekStart, \DateTimeImmutable $weekEnd
): int;

// Get max_weekly_sessions from the member's most recent active plan assignment
// Returns null if no plan assignment exists
public function findActivePlanMaxWeeklyForMember(MemberId $memberId): ?int;
```

#### Modified: MembershipPlan Entity
Add `public readonly int $maxWeeklySessions` property.

---

### 2. Application Layer

#### Modified: CreateBookingCommand
Add `public readonly \DateTimeImmutable $sessionDate`.

#### Modified: CreateBookingHandler
New flow:
```
1. Load session (existing)
2. Guard: session must be Active (existing)
3. Guard: capacity not exceeded (existing)
4. Resolve session_date via SessionDateResolver (NEW)
5. Guard: no duplicate for (member, session, session_date) (CHANGED)
6. Get max_weekly_sessions from member's plan (NEW) → MemberHasNoPlanException if null
7. Count confirmed bookings for member in session_date's ISO week (NEW)
8. Guard: count < max_weekly_sessions → WeeklyLimitReachedException if not (NEW)
9. Create booking with session_date (CHANGED)
10. Save (existing)
```

Inject: `SessionDateResolver` (new dep).

#### Modified: CancelBookingHandler
New flow:
```
1. Load booking (existing)
2. Guard: booking owned by requesting member (existing)
3. Load session to get TimeSlot (NEW) — inject ClassSessionRepositoryInterface
4. Compute session_datetime = booking.sessionDate + session.timeSlot (NEW)
5. Guard: now() < session_datetime → CancellationWindowExpiredException if expired (NEW)
6. booking.cancel() (existing)
7. Save (existing)
```

Inject: `ClassSessionRepositoryInterface` (new dep).

#### Modified: GetMemberBookingsHandler
Alongside the bookings list, also:
- Count confirmed bookings for member in current ISO week
- Get member's `max_weekly_sessions` from plan

Return a new `MemberBookingsResult` struct (or extend BookingRM list with metadata). Simple approach: return an array with `['bookings' => BookingRM[], 'weekly_used' => int, 'weekly_max' => int]`. The query handler can return a plain object or named array.

---

### 3. Infrastructure Layer

#### Modified: BookingTable
Add constant: `SESSION_DATE = 'session_date'`.

#### Modified: BookingHydrator
- `hydrate()`: read `session_date` from model → `new \DateTimeImmutable($model->session_date)`
- `dehydrate()`: include `session_date => $booking->sessionDate->format('Y-m-d')`

#### Modified: BookingRepository
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
- Update `getByIdRM()` and `findByMember()` SELECTs to include `b.session_date`.

#### Modified: MembershipPlanTable
Add constant: `MAX_WEEKLY_SESSIONS = 'max_weekly_sessions'`.

#### Modified: MembershipPlanHydrator
Map `max_weekly_sessions` → `$plan->maxWeeklySessions`.

#### New Migrations

**Migration 1:** `add_session_date_to_bookings`
```sql
ALTER TABLE bookings
  ADD COLUMN session_date DATE NOT NULL DEFAULT '2026-01-01' AFTER class_session_id;

-- Drop old unique constraint
DROP INDEX uq_member_session ON bookings;

-- Add new unique constraint scoped to date
ALTER TABLE bookings
  ADD UNIQUE KEY uq_member_session_date (member_id, class_session_id, session_date);

-- Add index for weekly count query
ALTER TABLE bookings
  ADD INDEX idx_b_member_date (member_id, session_date, status);
```

> Note: existing rows get `session_date = '2026-01-01'` as fallback. No production data exists yet.

**Migration 2:** `add_max_weekly_sessions_to_membership_plans`
```sql
ALTER TABLE membership_plans
  ADD COLUMN max_weekly_sessions TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER classes_per_month;
```

#### Modified: MembershipPlanSeeder
```php
MembershipPlanTable::MAX_WEEKLY_SESSIONS => 2,  // plan-2-dias
MembershipPlanTable::MAX_WEEKLY_SESSIONS => 3,  // plan-3-dias
MembershipPlanTable::MAX_WEEKLY_SESSIONS => 5,  // plan-4-5-dias
```

---

### 4. HTTP Layer

#### Modified: CreateBookingAction
Add 2 new exception catches:
```php
} catch (WeeklyLimitReachedException $e) {
    return response()->json([
        'error' => 'Has alcanzado el limite de reservas semanales de tu plan',
        'code'  => 'WEEKLY_LIMIT_REACHED',
        'used'  => ...,
        'max'   => ...,
    ], 422);
} catch (MemberHasNoPlanException) {
    return response()->json(['error' => 'No tienes un plan activo', 'code' => 'NO_ACTIVE_PLAN'], 422);
}
```

> `WeeklyLimitReachedException` must carry `$used` and `$max` so the action can expose them.

#### Modified: CancelBookingAction
Add 1 new exception catch:
```php
} catch (CancellationWindowExpiredException) {
    return response()->json([
        'error' => 'No puedes cancelar una reserva cuya sesion ya ha comenzado o pasado',
        'code'  => 'CANCELLATION_WINDOW_EXPIRED',
    ], 422);
}
```

#### Modified: BookingResource
Add `'session_date' => $this->rm->sessionDate` to the JSON output.

#### Modified: BookingListResource
Add top-level fields to the list response:
```json
{
  "weekly_used": 2,
  "weekly_max": 2,
  "data": [ ... ]
}
```

---

### 5. Frontend Layer

#### Modified: MemberBookingsPage.tsx
For each booking in the list, check if `session_date + time_slot <= now`. If so, disable/hide the "Cancelar" button. Use `session_date` from API response.

```ts
const isPast = (booking: Booking): boolean => {
  const [h, m] = booking.session.time_slot.split(':')
  const sessionDatetime = new Date(booking.session_date)
  sessionDatetime.setHours(parseInt(h), parseInt(m), 0)
  return sessionDatetime <= new Date()
}
```

#### Modified: MemberSchedulePage.tsx
Show weekly quota using `weekly_used` and `weekly_max` from the bookings list response:
```tsx
<p>Reservas esta semana: {weeklyUsed} / {weeklyMax}</p>
```

---

### 6. Configuration

#### Modified: `backend/.env`
Add: `APP_TIMEZONE=Europe/Madrid`

This ensures `now()` and `new \DateTimeImmutable()` in handlers use Spain local time (CET/CEST) for cutoff checks.

---

## Database Schema Changes

```sql
-- bookings table after migration
ALTER TABLE bookings
  ADD COLUMN session_date DATE NOT NULL DEFAULT '2026-01-01' AFTER class_session_id,
  DROP INDEX uq_member_session,
  ADD UNIQUE KEY uq_member_session_date (member_id, class_session_id, session_date),
  ADD INDEX idx_b_member_date (member_id, session_date, status);

-- membership_plans table after migration
ALTER TABLE membership_plans
  ADD COLUMN max_weekly_sessions TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER classes_per_month;
```

---

## State Machine (updated)

```
[Member books session]
         │
         ├─ SessionDateResolver resolves session_date from DayOfWeek + TimeSlot
         ├─ Guard: session Active ✓
         ├─ Guard: capacity available ✓
         ├─ Guard: no duplicate (member, session, session_date) ✓
         ├─ Guard: member has active plan (max_weekly_sessions != null) ✓
         ├─ Guard: countConfirmedInWeek < max_weekly_sessions ✓
         ▼
     [confirmed]
         │
         ├─ Load session.timeSlot
         ├─ Compute session_datetime = sessionDate + timeSlot
         ├─ Guard: now() < session_datetime ✓ → CancellationWindowExpiredException
         ▼
     [cancelled]
```

---

## Implementation Order

1. [ ] `backend/.env` — add `APP_TIMEZONE=Europe/Madrid`
2. [ ] `MembershipPlanTable` — add `MAX_WEEKLY_SESSIONS` constant
3. [ ] `MembershipPlan` entity — add `$maxWeeklySessions` property
4. [ ] `MembershipPlanHydrator` — map new field
5. [ ] Migration: `add_max_weekly_sessions_to_membership_plans`
6. [ ] `MembershipPlanSeeder` — add values (2, 3, 5)
7. [ ] `BookingTable` — add `SESSION_DATE` constant
8. [ ] `Booking` entity — add `$sessionDate` property; update `create()` factory
9. [ ] `BookingRM` — add `$sessionDate` field
10. [ ] New exceptions: `WeeklyLimitReachedException`, `CancellationWindowExpiredException`, `MemberHasNoPlanException`
11. [ ] `SessionDateResolver` domain service
12. [ ] `BookingRepositoryInterface` — add 3 new methods
13. [ ] Migration: `add_session_date_to_bookings` (add column + change UNIQUE)
14. [ ] `BookingHydrator` — handle `session_date` in hydrate/dehydrate
15. [ ] `BookingRepository` — implement 3 new methods; update `getByIdRM()` and `findByMember()` SELECTs
16. [ ] `CreateBookingCommand` — add `$sessionDate`
17. [ ] `CreateBookingHandler` — full update with new flow
18. [ ] `CancelBookingHandler` — add cutoff check + `ClassSessionRepositoryInterface` injection
19. [ ] `GetMemberBookingsHandler` — add weekly stats
20. [ ] `BookingResource` — add `session_date`
21. [ ] `BookingListResource` — add weekly stats metadata
22. [ ] `CreateBookingAction` — catch 2 new exceptions
23. [ ] `CancelBookingAction` — catch 1 new exception
24. [ ] Unit tests: `SessionDateResolver` (all edge cases)
25. [ ] Unit tests: weekly limit enforcement
26. [ ] Unit tests: cancellation cutoff
27. [ ] Feature tests: updated booking creation + cancellation
28. [ ] Frontend: `MemberBookingsPage.tsx` — disable Cancelar for past sessions
29. [ ] Frontend: `MemberSchedulePage.tsx` — show weekly quota
30. [ ] Run full test suite — all 183+ tests must pass

---

## Dependencies

| Dependency | Type | Description |
|------------|------|-------------|
| `ClassSessionRepositoryInterface` | New injection in CancelBookingHandler | Needs `getById()` to fetch TimeSlot |
| `SessionDateResolver` | New injection in CreateBookingHandler | Domain service |
| `member_plan_assignments` table | Cross-table JOIN in BookingRepository | Already exists |
| `membership_plans.max_weekly_sessions` | New column | Migration + seeder required first |
| `bookings.session_date` | New column | Migration required before running tests |

---

## Testing Strategy

| Test Type | Scope | Priority |
|-----------|-------|----------|
| Unit | `SessionDateResolver` — all 4 cases from requirements | High |
| Unit | `CreateBookingHandler` — weekly limit (at limit, over limit, cancelled don't count) | High |
| Unit | `CancelBookingHandler` — cutoff (before session OK, after session rejected, exact minute rejected) | High |
| Feature | `POST /api/member/bookings` — limit enforced (422 WEEKLY_LIMIT_REACHED) | High |
| Feature | `POST /api/member/bookings` — no active plan (422 NO_ACTIVE_PLAN) | High |
| Feature | `DELETE /api/member/bookings/{id}` — past session (422 CANCELLATION_WINDOW_EXPIRED) | High |
| Feature | `GET /api/member/bookings` — includes weekly_used + weekly_max | Medium |
| Regression | All 183 existing tests | High |

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Existing tests break (no `session_date` in factories/test data) | High | Medium | Update test factories to include `session_date`; migration adds `DEFAULT '2026-01-01'` for existing rows |
| `findByMemberAndSession` still called somewhere | Low | Low | Keep old method in interface for backwards compat; new duplicate check uses `findByMemberSessionAndDate` |
| Timezone edge case at CET→CEST boundary (March/October) | Low | Low | `APP_TIMEZONE=Europe/Madrid` handles DST automatically |
| WeeklyLimitReachedException needs $used/$max fields | — | — | Add constructor params to exception class |
