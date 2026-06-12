# Epic: Booking Rules — Weekly Limit & Cancellation Cutoff

**Type:** Epic
**Status:** Draft
**Created:** 2026-06-11
**Author:** Requirement Writer Agent
**Phase:** Phase 4 — Iterations & Improvements

---

## Business Alignment

**Objective:** Operational Integrity / Member Fairness
**KPI Target:** Zero bookings that violate plan limits or allow cancellation of past sessions.
**Evidence:** During post-MVP testing (2026-06-11), a member with a "Plan 2 Dias" (2 sessions/week limit) was able to book 4–5 sessions in the same week with no enforcement. Expired session bookings also showed a "Cancelar" button with no time validation. Both are critical correctness bugs that undermine the membership plan system and member fairness.

---

## Problem Statement

### Current Situation

The existing booking system (`epic-booking`) enforces:
- **Capacity per session** — a session cannot be overbooked beyond `max_capacity`.
- **Duplicate booking** — a member cannot book the same session twice.

It does NOT enforce:
- **Weekly session limit per plan** — a "Plan 2 Dias" member can book unlimited sessions per week.
- **Cancellation cutoff** — a member can "cancel" a booking for a session that happened days ago.

Additionally, `ClassSession` stores `day_of_week` (1–5) and `time_slot` (e.g., "07:45") but no concrete date. Bookings currently have no `session_date` field, which makes it impossible to determine the specific calendar date of the class a member is attending.

### Pain Points

- A member paying for 2 sessions/week can use the platform to attend 5 sessions/week. This is unfair to members on higher plans and undermines the pricing model.
- Showing a "Cancelar" button on past bookings is confusing and misleading. It creates a false impression that the action is valid.
- Without `session_date` on the booking, there is no way to compute the cutoff time or enforce the weekly limit accurately.

### Impact if Not Solved

- Revenue leakage: members on cheaper plans effectively get unlimited access.
- Trust issue: the system appears broken to members who see actionable buttons on expired data.
- Future features (invoicing, attendance tracking, auto-close) depend on knowing the concrete session date.

---

## Proposed Solution

Three coordinated changes:

1. **Add `session_date` to bookings** — store the concrete calendar date (e.g., `2026-06-16`) of the class being booked. Derived at booking creation time from the session's `day_of_week` and the current date.
2. **Enforce weekly booking limit** — before creating a booking, count the member's confirmed bookings for the target week. Reject if count >= plan limit.
3. **Enforce cancellation cutoff** — before cancelling a booking, check if `session_date + time_slot <= NOW`. Reject if the session has already started or passed.

Additionally, `MembershipPlan` needs a `max_weekly_sessions` field so the limit is data-driven, not hardcoded.

---

### User Stories

#### US-001: System enforces weekly session limit

**As a** member
**I want** the system to prevent me from booking more sessions than my plan allows per week
**So that** the plan limits are respected and the gym's pricing model is fair

**Acceptance Criteria:**
- [ ] A "Plan 2 Dias" member (max 2 sessions/week) cannot book a 3rd session in the same calendar week
- [ ] A "Plan 3 Dias" member (max 3 sessions/week) cannot book a 4th session in the same calendar week
- [ ] A "Plan 4-5 Dias" member (max 5 sessions/week) cannot book a 6th session in the same calendar week
- [ ] The system returns a clear error: 422 with code `WEEKLY_LIMIT_REACHED` and remaining/max slots
- [ ] Cancelled bookings do NOT count toward the weekly limit
- [ ] Week is defined as Monday 00:00 to Sunday 23:59 (ISO calendar week, Spain local time)
- [ ] The limit check uses `session_date` on the booking, not booking creation date
- [ ] Duplicate booking constraint changes to `(member_id, class_session_id, session_date)` — a member CAN book the same recurring slot on different weeks

#### US-002: Booking stores the concrete session date

**As a** member
**I want** my booking to reference the actual date of the class (not just the day-of-week template)
**So that** the system can correctly compute time-based rules

**Acceptance Criteria:**
- [ ] Each new booking stores a `session_date` (DATE column): the next upcoming occurrence of the session's `day_of_week` relative to booking creation time
- [ ] If today is the same day as `day_of_week` but the `time_slot` has not passed yet, `session_date` = today
- [ ] If today is the same day as `day_of_week` but the `time_slot` has passed, `session_date` = same day next week
- [ ] If today is before the `day_of_week` in the current week, `session_date` = that day of the current week
- [ ] `session_date` is included in all booking API responses

#### US-003: Member cannot cancel a past or started session

**As a** member
**I want** the system to prevent me from cancelling a booking for a session that has already started or passed
**So that** the booking history stays accurate and I cannot retroactively free a spot

**Acceptance Criteria:**
- [ ] If `session_date + time_slot <= NOW`, cancellation is rejected with 422 and code `CANCELLATION_WINDOW_EXPIRED`
- [ ] If `session_date + time_slot > NOW`, cancellation is allowed (up to the second before the session)
- [ ] Example: session on Monday at 15:30 → cancellable up to Monday 15:29:59, not at 15:30:00
- [ ] The frontend hides or disables the "Cancelar" button for past/started sessions

#### US-004: Admin adds max_weekly_sessions to membership plans

**As an** admin
**I want** each membership plan to store its weekly session limit
**So that** the booking system can enforce it without hardcoding values

**Acceptance Criteria:**
- [ ] `membership_plans` table has a `max_weekly_sessions` integer column (NOT NULL)
- [ ] Existing plans are seeded/migrated with the correct values:
  - Plan 2 Dias → `max_weekly_sessions = 2`
  - Plan 3 Dias → `max_weekly_sessions = 3`
  - Plan 4-5 Dias → `max_weekly_sessions = 5`
- [ ] The booking handler reads `max_weekly_sessions` from the member's current active plan

---

## Entities

| Entity | Change | Description |
|--------|--------|-------------|
| `Booking` | Modified | Add `session_date` DATE field |
| `MembershipPlan` | Modified | Add `max_weekly_sessions` INT field |

### State Machine: Booking (updated)

```
[Member books session]
         │
         ▼
     confirmed ──── Member cancels (only if session_date+time_slot > NOW) ──► cancelled
         │
         │── System rejects if confirmed_count_this_week >= max_weekly_sessions → WEEKLY_LIMIT_REACHED
         │── System rejects if session_date+time_slot <= NOW → SESSION_ALREADY_STARTED (can't book a past class)
```

### State Transitions (updated)

| From | To | Trigger | Conditions |
|------|----|---------|------------|
| (new) | confirmed | Member books session | Session active + has capacity + no duplicate + weekly limit not reached + session not in the past |
| confirmed | cancelled | Member cancels | Booking owned by member + `session_date + time_slot > NOW` |

---

## Use Cases

### UC-001: Book a Class Session (updated)

**Actor:** Member
**Preconditions:**
- Member is authenticated with `role = member`
- Member has an active membership plan assignment
- ClassSession exists and is `active`
- Session has capacity available

**Main Flow:**
1. Member requests to book a session (`class_session_id`)
2. System resolves `session_date`: next upcoming date for this session's `day_of_week`
3. System verifies session exists and is active
4. System checks capacity (confirmed bookings count < max_capacity)
5. System checks no duplicate booking for this member + session_date combination
6. System counts member's confirmed bookings for the ISO week containing `session_date`
7. System reads member's active plan `max_weekly_sessions`
8. System rejects if count >= limit
9. System creates booking with `session_date` stored
10. System returns booking confirmation

**Error Scenarios:**
- Session not found → 404
- Session cancelled → 422 `SESSION_CANCELLED`
- Session full → 422 `SESSION_FULL`
- Duplicate booking → 409 `BOOKING_ALREADY_EXISTS`
- Weekly limit reached → 422 `WEEKLY_LIMIT_REACHED` (includes `used` and `max` in response)
- No active plan → 422 `MEMBER_HAS_NO_ACTIVE_PLAN`

---

### UC-002: Cancel a Booking (updated)

**Actor:** Member
**Preconditions:**
- Member is authenticated
- Booking exists with status `confirmed`
- Booking belongs to the requesting member

**Main Flow:**
1. Member requests cancellation (`booking_id`)
2. System verifies booking exists and belongs to member
3. System computes cutoff: `session_datetime = session_date + time_slot` (UTC/local)
4. System checks `NOW < session_datetime`
5. System transitions booking to `cancelled`
6. System returns updated booking

**Error Scenarios:**
- Booking not found → 404
- Booking not owned by member → 403 `BOOKING_NOT_OWNED`
- Booking already cancelled → 422 `BOOKING_ALREADY_CANCELLED`
- Session already started or passed → 422 `CANCELLATION_WINDOW_EXPIRED`

---

### UC-003: Session date resolution logic

**Actor:** System (internal, called at booking creation)
**Input:** `day_of_week` (1=Mon … 5=Fri), `time_slot` ("HH:MM"), `now` (current datetime)

**Logic:**
```
target_day = day_of_week (1=Mon, 7=Sun in ISO)
current_day = now.isoWeekday()
current_week_target = start_of_current_week + (target_day - 1) days

if current_week_target.date + time_slot > now:
    session_date = current_week_target.date
else:
    session_date = current_week_target.date + 7 days
```

**Examples:**
- Now: Wednesday 14:00, session: Monday 07:45 → `session_date` = next Monday
- Now: Monday 07:00, session: Monday 07:45 → `session_date` = this Monday (slot not yet passed)
- Now: Monday 08:00, session: Monday 07:45 → `session_date` = next Monday (slot already passed)
- Now: Wednesday 14:00, session: Friday 18:45 → `session_date` = this Friday

---

## Collateral Impact

| Component | Type | Impact | Action Required |
|-----------|------|--------|-----------------|
| `bookings` table | Data | Add `session_date` DATE column (NOT NULL) | New migration; existing rows get NULL or derived value |
| `membership_plans` table | Data | Add `max_weekly_sessions` INT NOT NULL | New migration + seeder update |
| `CreateBookingHandler` | Behavioral | Must resolve session_date and check weekly limit | Modify handler |
| `CancelBookingHandler` | Behavioral | Must check cancellation window before transitioning | Modify handler |
| `Booking` entity | Behavioral | Add `sessionDate` property | Modify entity + hydrator |
| `BookingResource` | API | Must include `session_date` in response | Modify resource |
| `MembershipPlan` entity | Data | Add `maxWeeklySessions` property | Modify entity |
| `MembershipPlanRepository` | Data | Must return `maxWeeklySessions` | Modify repository/hydrator |
| `BookingRepositoryInterface` | API | New method: `countConfirmedForMemberInWeek(MemberId, weekStart, weekEnd): int` | Add method |
| Frontend `MemberSchedulePage` | UI | Hide/disable "Reservar" button for sessions that have passed | Modify component |
| Frontend `MemberBookingsPage` | UI | Hide/disable "Cancelar" button for past/started sessions | Modify component |
| Existing seeder (`MembershipPlanSeeder`) | Data | Must set `max_weekly_sessions` values | Modify seeder |
| Existing tests | Behavioral | Booking creation tests must account for new validation | Update feature tests |

### Migration Requirements

- [ ] Migration: add `session_date DATE NOT NULL` to `bookings` — existing rows: derive from session's `day_of_week` relative to `created_at`, or set to `created_at` date as fallback
- [ ] Migration: add `max_weekly_sessions TINYINT UNSIGNED NOT NULL DEFAULT 0` to `membership_plans`
- [ ] Seeder: update MembershipPlanSeeder to set correct values (2, 3, 5)

### Risk Assessment

| Risk | Probability | Severity | Mitigation |
|------|-------------|----------|------------|
| Timezone mismatch (server UTC vs gym local Spain time) | High | Medium | Use `Europe/Madrid` timezone consistently for session_date resolution and cutoff checks |
| Existing test data breaks (no session_date) | High | Low | Migration sets fallback value; test factories updated |
| session_date calculation edge case (same day, slot just passed) | Medium | Medium | UC-003 logic covers all cases with examples |
| Member plan assignment gap (no active plan at booking time) | Low | Medium | Return 422 `MEMBER_HAS_NO_ACTIVE_PLAN` |

---

## Definition of Done

- [ ] `bookings` table has `session_date` DATE column
- [ ] `membership_plans` table has `max_weekly_sessions` column with correct values for all plans
- [ ] Creating a booking resolves and stores `session_date` correctly
- [ ] Weekly limit enforced on booking creation — 422 `WEEKLY_LIMIT_REACHED` returned with `used`/`max` in response body
- [ ] Cancellation after session start/pass rejected — 422 `CANCELLATION_WINDOW_EXPIRED`
- [ ] Cancellation before session start allowed as before
- [ ] `session_date` included in all booking API responses
- [ ] Frontend hides "Cancelar" button for past/started sessions
- [ ] Frontend shows remaining weekly bookings or limit error message
- [ ] Unit tests for session_date resolution logic (all cases in UC-003)
- [ ] Unit tests for weekly limit enforcement
- [ ] Unit tests for cancellation cutoff
- [ ] Feature tests updated (existing booking tests still pass)
- [ ] Unique DB constraint on `bookings` changed to `(member_id, class_session_id, session_date)`
- [ ] `APP_TIMEZONE=Europe/Madrid` set in `.env` so `now()` uses Spain local time for cutoff checks
- [ ] `weekly_bookings_used` and `weekly_bookings_max` included in GET /member/bookings response
- [ ] All existing 183 tests still passing
- [ ] PHPStan passing

---

## Time Constraints

**Deadline:** None
**Type:** None
**Reason:** Internal correction, no external deadline

---

## Open Questions

1. **Timezone:** Server runs UTC. Session times are in Spain local time (CET/CEST, UTC+1/UTC+2). Cancellation cutoff must use Spain local time. Recommendation: set `APP_TIMEZONE=Europe/Madrid` in `.env` and use Laravel's `now()` which respects it.
2. **Existing bookings migration:** Rows in `bookings` with no `session_date` — derive from `day_of_week` of the linked session relative to `created_at`. Acceptable fallback.
3. **Plan without max_weekly_sessions:** If a member has no active plan at booking time, return 422 `MEMBER_HAS_NO_ACTIVE_PLAN`. Admin must assign a plan before member can book.
4. **Can admin bypass the weekly limit?** Recommendation: no — admin creates bookings on behalf of members through the same endpoint. Out of scope for now.
