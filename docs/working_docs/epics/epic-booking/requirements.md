# Epic Booking — Class Booking System

**Type:** Epic
**Status:** Draft
**Created:** 2026-06-11
**Author:** Requirement Writer Agent
**Phase:** Phase 3 — Value Delivery

---

## Business Alignment

**Objective:** Operational Efficiency / Member Retention
**KPI Target:** 100% of class bookings managed through the platform (replacing external third-party app)
**Evidence:** The gym currently uses an external booking app (unnamed, not owned by the gym). This creates three problems: no data ownership, no brand consistency, and dependency on a third-party service. Replacing it is the direct motivation for the entire platform investment.

---

## Problem Statement

### Current Situation

Members book classes through an external third-party application. Coaches and the admin have no unified view of who is attending each session. Capacity is enforced by the third-party app, which the gym does not own.

### Pain Points

- No data ownership: booking history belongs to the third-party app, not Valhalla.
- No brand consistency: the booking experience is outside the Valhalla branded platform.
- Dependency risk: if the third-party app changes pricing or terms, the gym has no alternative.
- No integration: bookings are siloed from membership data (no way to enforce plan limits in MVP context).
- Admin/Coach cannot see class roster from within the same platform that manages members.

### Impact if Not Solved

The platform cannot replace the third-party app. The MVP success criterion "Members can log in and book a class slot" (from `/docs/business/overview.md`) is not met.

---

## Proposed Solution

Build a native booking system within the Valhalla platform. Members can view available class sessions and reserve a spot. The system enforces the capacity limit defined on each `ClassSession`. Members can cancel their own bookings. Admins and coaches can view the class roster for any session.

### User Stories

#### US-001: Member Books a Class Slot

**As a** member
**I want** to view available class sessions and book a spot
**So that** I can reserve my place in a class without using an external app

**Acceptance Criteria:**
- [ ] Member can see a list of upcoming class sessions with available spots
- [ ] Member can book a session that has at least one available spot
- [ ] System creates a booking with status `confirmed`
- [ ] Available spots on the session decrease by 1 after booking
- [ ] Member cannot book the same session twice (duplicate booking rejected)
- [ ] Member cannot book a session that is full (capacity enforced)
- [ ] Member cannot book a session that has status `cancelled`
- [ ] Booking is immediately visible in the member's booking list

#### US-002: Member Cancels a Booking

**As a** member
**I want** to cancel a booking I previously made
**So that** I free up my spot for other members and keep my booking history accurate

**Acceptance Criteria:**
- [ ] Member can cancel any of their own `confirmed` bookings
- [ ] After cancellation, booking status changes to `cancelled`
- [ ] Available spots on the session increase by 1 after cancellation
- [ ] Member cannot cancel a booking that is already `cancelled`
- [ ] Member cannot cancel another member's booking

#### US-003: Admin/Coach Views Class Roster

**As an** admin or coach
**I want** to see the list of members booked for a specific class session
**So that** I can prepare the class and track attendance

**Acceptance Criteria:**
- [ ] Admin can view the roster for any class session
- [ ] Coach can view the roster for any class session (not restricted to their own sessions in MVP)
- [ ] Roster shows: member name, member number, booking status, booking time
- [ ] Roster shows current confirmed bookings count vs max capacity
- [ ] Roster is ordered by booking time (earliest first)

#### US-004: Admin Views All Bookings for a Member

**As an** admin
**I want** to see all bookings for a specific member
**So that** I can understand their activity and resolve issues

**Acceptance Criteria:**
- [ ] Admin can retrieve all bookings for a given member ID
- [ ] Results include booking status (confirmed / cancelled)
- [ ] Results include the class session details (day, time slot, class type)
- [ ] Results are ordered by booking creation time (most recent first)

---

## Entities

| Entity | Description | States |
|--------|-------------|--------|
| Booking | A member's reservation for a ClassSession | `confirmed`, `cancelled` |

> `ClassSession` and `Member` are existing entities from epic-classes and epic-members. Booking references both.

### State Machine: Booking

```
[Member books session]
         │
         ▼
     confirmed ──── Member cancels ──► cancelled
```

### State Transitions

| From | To | Trigger | Conditions |
|------|----|---------|------------|
| (new) | confirmed | Member books session | Session is active, session has capacity, member has no existing confirmed booking for same session |
| confirmed | cancelled | Member cancels | Booking belongs to the requesting member |

### Delete Strategy

Bookings are **never hard-deleted**. Status transitions to `cancelled`. This preserves the history for reporting and audit purposes.

---

## Use Cases

### UC-001: Book a Class Session

**Actor:** Member
**Preconditions:**
- Member is authenticated with `role = member`
- ClassSession exists, is `active` (not cancelled, not deleted)
- Session has at least 1 available spot (`confirmed_count < max_capacity`)
- Member has no existing `confirmed` booking for this session

**Postconditions:**
- Booking record created with status `confirmed`
- Booked count for session increases by 1 (derived from booking count, not a stored column)

**Main Flow:**
1. Member requests to book a session (provides `class_session_id`)
2. System verifies session exists and is active
3. System checks capacity (counts confirmed bookings for session)
4. System checks no duplicate booking exists for this member + session
5. System creates booking with status `confirmed`
6. System returns booking confirmation

**Error Scenarios:**
- Session not found → 404
- Session is cancelled → 422 (`SESSION_CANCELLED`)
- Session is full → 422 (`SESSION_FULL`)
- Member already has a confirmed booking for this session → 409 (`BOOKING_ALREADY_EXISTS`)

---

### UC-002: Cancel a Booking

**Actor:** Member
**Preconditions:**
- Member is authenticated
- Booking exists with status `confirmed`
- Booking belongs to the requesting member

**Postconditions:**
- Booking status changes to `cancelled`
- Spot freed (capacity count decreases by 1)

**Main Flow:**
1. Member requests cancellation (provides `booking_id`)
2. System verifies booking exists
3. System verifies booking belongs to requesting member
4. System transitions booking to `cancelled`
5. System returns updated booking

**Error Scenarios:**
- Booking not found → 404
- Booking does not belong to member → 403 (`BOOKING_NOT_OWNED`)
- Booking already cancelled → 422 (`BOOKING_ALREADY_CANCELLED`)

---

### UC-003: Get Class Roster

**Actor:** Admin, Coach
**Preconditions:**
- User is authenticated with `role = admin` or `role = coach`
- ClassSession exists

**Postconditions:** (read-only, no state change)

**Main Flow:**
1. Admin/Coach requests roster for a session (provides `class_session_id`)
2. System returns all confirmed + cancelled bookings for the session
3. Response includes member details for each booking
4. Response includes capacity summary (confirmed_count / max_capacity)

**Error Scenarios:**
- Session not found → 404

---

### UC-004: Get Member's Own Bookings

**Actor:** Member
**Preconditions:**
- Member is authenticated

**Main Flow:**
1. Member requests their booking list
2. System returns all bookings (confirmed and cancelled) for the authenticated member
3. Each item includes session details (day, time slot, class type name)

---

### UC-005: Admin Gets All Bookings for a Specific Member

**Actor:** Admin
**Preconditions:**
- User is authenticated with `role = admin`
- Member ID is valid

**Main Flow:**
1. Admin requests booking list for a given `member_id`
2. System returns all bookings (confirmed and cancelled) for that member
3. Each item includes session details

**Error Scenarios:**
- Member not found → 404

---

## Collateral Impact

| Component | Impact | Action Required |
|-----------|--------|-----------------|
| `ClassSession` entity | Read-only dependency — booking reads `max_capacity` and `status` | No modification to ClassSession entity |
| `ClassSessionRepositoryInterface` | Booking handler needs to verify session exists and read capacity | Add `getById()` call (method already exists) — no interface change |
| `Member` entity | Read-only dependency — booking is linked to a member | No modification to Member entity |
| `MemberRepositoryInterface` | Booking handler needs to verify member exists | Add `getById()` call (method already exists) — no interface change |
| `routes/api.php` | New booking routes must be added | Extend route file |
| `AppServiceProvider` | New repository binding required | Add `BookingRepositoryInterface → BookingRepository` |
| `database/migrations/` | New `bookings` table migration | Create migration file |

---

## Definition of Done

- [ ] Member can book an available session via `POST /api/bookings`
- [ ] System rejects booking if session is full (returns 422 `SESSION_FULL`)
- [ ] System rejects duplicate booking (returns 409 `BOOKING_ALREADY_EXISTS`)
- [ ] System rejects booking on cancelled session (returns 422 `SESSION_CANCELLED`)
- [ ] Member can cancel their own booking via `DELETE /api/bookings/{id}` (or PATCH cancel)
- [ ] Admin and Coach can view class roster via `GET /api/class-sessions/{id}/roster`
- [ ] Member can view their own bookings via `GET /api/member/bookings`
- [ ] Admin can view bookings for any member via `GET /api/admin/members/{id}/bookings`
- [ ] All capacity logic uses live count of confirmed bookings (no denormalized counter)
- [ ] No N+1 queries — roster and booking list load member and session data in single queries
- [ ] Unit tests for `Booking` entity (state transitions)
- [ ] Feature tests for all endpoints
- [ ] PHPStan passing at project level

---

## Time Constraints

**Deadline:** None (MVP — no external deadline)
**Type:** None
**Reason:** Internal development, no business calendar event tied to this feature
**Calendar Conflicts:** None

---

## Open Questions

1. Should members be able to book sessions for past dates? (Recommendation: reject bookings for sessions whose day + time has already passed. Out of scope for MVP — no date on ClassSession, only day_of_week and time_slot. Defer to post-MVP.)
2. Should there be a booking window (e.g., only book up to N hours before the session)? Recommendation: no restriction in MVP — simplify first deployment.
3. Should cancelling a booking send any notification to the member? Recommendation: no notifications in MVP.
4. Does the membership plan limit the number of bookings per month? The plan defines `classes_per_month`, but enforcing this limit is complex. Recommendation: **not enforced in MVP** — admin can monitor manually.
