# Epic: Class Schedule Management

**Type:** Epic
**Status:** Draft
**Created:** 2026-06-10
**Author:** Requirement Writer Agent
**Epic Reference:** N/A (this is the epic)

---

## Business Alignment

**Objective:** Operational efficiency — digitize the weekly class schedule to replace manual/external processes.

**KPI Targets:**
- Admin can create and modify the full weekly schedule without any external tool
- Coaches can see their assigned sessions directly from the platform
- Public visitors can view the gym schedule without calling or checking external apps
- Zero dependency on third-party booking apps for schedule visibility at launch of this epic

**Evidence:**
- The gym currently manages the class schedule via an external third-party booking app (not branded, no data ownership)
- Class slots are fixed Mon–Fri (7 daily slots per day) with fixed class types per day — this can be fully modeled
- The MVP goal explicitly states: "Admin can create and manage the weekly class schedule" as a success criterion
- Public schedule visibility is required for guest users (no login) per business overview

---

## Problem Statement

### Current Situation

Valhalla Gym manages its class schedule externally, outside the branded platform being built. The schedule is static (fixed day-to-class-type mapping, 7 time slots per day), but it lives in a third-party tool. Coaches cannot see their assignments in the platform. Public visitors cannot see the schedule from the gym's own website.

### Pain Points

- No data ownership: the schedule lives in a third-party system not controlled by the gym
- No branded experience: visitors see the schedule on an external app, not on Valhalla's own platform
- Coaches have no platform view of their assigned sessions
- Admin cannot assign or reassign coaches to sessions from the owned platform
- Any change to the schedule requires access to the external tool
- The upcoming booking feature (epic-booking) requires ClassSession records to exist — this epic is a hard dependency

### Impact if Not Solved

- epic-booking cannot be implemented without ClassSession data
- The platform cannot replace the external booking app
- Coaches and members continue to use disconnected tools
- No data about sessions is owned by the gym

---

## Proposed Solution

Build the ClassSession bounded context (`Core/ClassSession`) in the backend:

- A `ClassSession` represents one recurring weekly slot: a specific day of week + time slot + class type + assigned coach + max capacity
- ClassType already exists (seeded, read-only in this epic)
- Staff/Coach = User with `role = coach` (already exists in `Shared/Auth`)
- Admin can fully manage (create, update, delete, cancel/restore) sessions via authenticated API
- Coach can read their own assigned sessions via authenticated API
- Public (guest) can read the full weekly schedule without authentication
- A seeder provides the default weekly schedule at deploy time

### User Stories

#### US-001: Admin creates a class session
**As an** admin
**I want** to create a class session with a specific day, time slot, class type, assigned coach, and max capacity
**So that** the weekly schedule is reflected in the platform

**Acceptance Criteria:**
- [ ] Admin can POST to create a new class session providing: day_of_week, time_slot, class_type_id, coach_id, max_capacity
- [ ] The session is created with status `active`
- [ ] If the coach already has a session at the same day+time, the request is rejected with a clear error
- [ ] If day_of_week is Saturday or Sunday, the request is rejected (weekends not in MVP scope)
- [ ] If max_capacity is 0 or negative, the request is rejected
- [ ] If class_type_id does not exist, the request is rejected
- [ ] If coach_id does not correspond to a user with role=coach, the request is rejected
- [ ] The new session ID is returned in the response

#### US-002: Admin updates a class session
**As an** admin
**I want** to update an existing class session (reassign coach, change capacity, or change class type)
**So that** I can keep the schedule accurate when coaches change or capacity adjusts

**Acceptance Criteria:**
- [ ] Admin can PUT to update coach_id, max_capacity, or class_type_id on an existing session
- [ ] day_of_week and time_slot are immutable once created (they define the slot identity)
- [ ] If the new coach already has a session at the same day+time (different session), the request is rejected
- [ ] Validation rules from US-001 apply to updated fields

#### US-003: Admin cancels a class session
**As an** admin
**I want** to cancel a specific class session (e.g. on a holiday or coach absence)
**So that** members and coaches know that session is not happening

**Acceptance Criteria:**
- [ ] Admin can PATCH /class-sessions/{id}/cancel to transition status from `active` to `cancelled`
- [ ] Cancelled sessions still appear in the schedule but with `status: cancelled`
- [ ] A cancelled session cannot be cancelled again (idempotency: return 422 with clear message)

#### US-004: Admin restores a cancelled class session
**As an** admin
**I want** to restore a cancelled class session back to active
**So that** I can un-cancel a session if the cancellation was in error

**Acceptance Criteria:**
- [ ] Admin can PATCH /class-sessions/{id}/restore to transition status from `cancelled` to `active`
- [ ] Only a `cancelled` session can be restored (if already `active`, return 422)

#### US-005: Admin deletes a class session permanently
**As an** admin
**I want** to permanently remove a class session from the schedule
**So that** I can clean up sessions that no longer exist (e.g. a time slot is retired)

**Acceptance Criteria:**
- [ ] Admin can DELETE a class session
- [ ] The session is removed (hard delete is acceptable since bookings don't exist yet; in epic-booking, this will need revisiting)
- [ ] A session with existing bookings cannot be deleted (guarded for future-proofing, but no bookings exist in this epic)

#### US-006: Admin lists all class sessions
**As an** admin
**I want** to see all class sessions (optionally filtered by day or coach)
**So that** I can manage the full schedule

**Acceptance Criteria:**
- [ ] Admin can GET /class-sessions with optional filters: day_of_week, coach_id, status
- [ ] Results include session details: id, day_of_week, time_slot, class_type (id + name + slug), coach (id + email), max_capacity, status
- [ ] Results are ordered by day_of_week then time_slot

#### US-007: Admin views a single class session
**As an** admin
**I want** to view the details of a specific class session
**So that** I can inspect or verify the configuration

**Acceptance Criteria:**
- [ ] Admin can GET /class-sessions/{id} to retrieve full session details
- [ ] Returns 404 if session does not exist

#### US-008: Coach views their assigned sessions
**As a** coach
**I want** to see the list of class sessions assigned to me
**So that** I know my weekly schedule within the platform

**Acceptance Criteria:**
- [ ] Authenticated coach can GET /coach/sessions to retrieve only sessions where coach_id = their user ID
- [ ] Results include: day_of_week, time_slot, class_type name, max_capacity, status
- [ ] Results are ordered by day_of_week then time_slot
- [ ] A coach cannot see sessions assigned to other coaches

#### US-009: Public user views the weekly schedule
**As a** guest (not logged in)
**I want** to see the complete weekly class schedule
**So that** I can know what classes are available and when

**Acceptance Criteria:**
- [ ] Anyone can GET /schedule without authentication
- [ ] Returns all active (and cancelled for transparency) sessions grouped by day
- [ ] Each session shows: day_of_week, time_slot, class_type name, coach name (or display name), max_capacity, status
- [ ] Response is read-only — no auth required
- [ ] Weekdays only (Mon–Fri), no weekend sessions in response

---

## Entities

| Entity | Description | States |
|--------|-------------|--------|
| ClassSession | A recurring weekly time slot with assigned class type, coach, and capacity | active, cancelled |
| ClassType | Type of class (seeded, read-only in this epic) | N/A (no state machine in this epic) |

### State Machine: ClassSession

```
[Created] → active → cancelled → active (restore)
                  ↘
               [hard deleted] (admin removes slot permanently)
```

### State Transitions

| From | To | Trigger | Conditions |
|------|----|---------|------------|
| (new) | active | create() | Valid fields, no coach conflict, weekday only |
| active | cancelled | cancel() | Admin action |
| cancelled | active | restore() | Admin action |
| active | (deleted) | delete() | Admin action; no bookings attached |
| cancelled | (deleted) | delete() | Admin action; no bookings attached |

---

## Use Cases

### UC-001: Create Class Session

**Actor:** Admin
**Preconditions:** Admin is authenticated. class_type_id exists. coach_id belongs to a user with role=coach.
**Postconditions:** New ClassSession record created with status=active.

**Main Flow:**
1. Admin sends POST /api/class-sessions with payload
2. System validates: weekday only, capacity > 0, valid class_type_id, valid coach_id (role=coach)
3. System checks coach is not double-booked at same day+time
4. System creates session with a new ULID, status=active
5. System returns 201 with the created session resource

**Alternative Flows:**
- Admin creates a Friday session: two sessions can share the same day+time if they have different class_type_id (GAP + Entrenamiento Libre)

**Error Scenarios:**
- E1: day_of_week is saturday or sunday → 422 `WeekendSessionNotAllowedException`
- E2: max_capacity <= 0 → 422 `InvalidCapacityException`
- E3: class_type_id not found → 422 `ClassTypeNotFoundException`
- E4: coach_id not found or not role=coach → 422 `CoachNotFoundException`
- E5: Coach already assigned to another session at same day+time → 409 `CoachAlreadyBookedException`

---

### UC-002: Update Class Session

**Actor:** Admin
**Preconditions:** Admin is authenticated. Session exists.
**Postconditions:** Session updated; day_of_week and time_slot unchanged.

**Main Flow:**
1. Admin sends PUT /api/class-sessions/{id}
2. System validates updated fields (same rules as creation for coach_id, capacity, class_type_id)
3. System checks new coach is not double-booked at same day+time (excluding this session)
4. System returns 200 with updated session resource

**Error Scenarios:**
- E1: Session not found → 404
- E2: Attempt to change day_of_week or time_slot → 422 `ImmutableFieldException`
- E3–E5: Same as UC-001

---

### UC-003: Cancel Class Session

**Actor:** Admin
**Preconditions:** Admin is authenticated. Session exists and is active.
**Postconditions:** Session status = cancelled.

**Main Flow:**
1. Admin sends PATCH /api/class-sessions/{id}/cancel
2. System transitions status to cancelled
3. System returns 200 with updated session resource

**Error Scenarios:**
- E1: Session not found → 404
- E2: Session already cancelled → 422 `SessionAlreadyCancelledException`

---

### UC-004: Restore Class Session

**Actor:** Admin
**Preconditions:** Admin is authenticated. Session exists and is cancelled.
**Postconditions:** Session status = active.

**Main Flow:**
1. Admin sends PATCH /api/class-sessions/{id}/restore
2. System transitions status to active
3. System returns 200 with updated session resource

**Error Scenarios:**
- E1: Session not found → 404
- E2: Session is already active → 422 `SessionNotCancelledException`

---

### UC-005: Delete Class Session

**Actor:** Admin
**Preconditions:** Admin is authenticated. Session exists. No bookings attached (future-proofing guard).
**Postconditions:** Session record removed.

**Main Flow:**
1. Admin sends DELETE /api/class-sessions/{id}
2. System verifies no bookings reference this session (no-op check in this epic since bookings don't exist)
3. System hard-deletes the session
4. System returns 204 No Content

**Error Scenarios:**
- E1: Session not found → 404
- E2 (future): Session has bookings → 409 `SessionHasBookingsException`

---

### UC-006: List Class Sessions (Admin)

**Actor:** Admin
**Preconditions:** Admin is authenticated.
**Postconditions:** Returns filtered list of sessions.

**Main Flow:**
1. Admin sends GET /api/class-sessions with optional query params: day_of_week, coach_id, status
2. System returns list ordered by day_of_week ASC, time_slot ASC

---

### UC-007: Get Single Class Session (Admin)

**Actor:** Admin
**Preconditions:** Admin is authenticated.
**Postconditions:** Returns session detail.

**Main Flow:**
1. Admin sends GET /api/class-sessions/{id}
2. System returns session detail including class type and coach info

**Error Scenarios:**
- E1: Session not found → 404 `ClassSessionNotFoundException`

---

### UC-008: Coach Views Own Sessions

**Actor:** Coach (authenticated)
**Preconditions:** User is authenticated with role=coach.
**Postconditions:** Returns only sessions assigned to this coach.

**Main Flow:**
1. Coach sends GET /api/coach/sessions
2. System filters sessions by coach_id = authenticated user ID
3. Returns ordered list

**Error Scenarios:**
- E1: Authenticated user is not a coach → 403 Forbidden

---

### UC-009: Public Schedule View

**Actor:** Guest (unauthenticated)
**Preconditions:** None.
**Postconditions:** Returns full weekly schedule without authentication.

**Main Flow:**
1. Guest sends GET /api/schedule
2. System returns all sessions (active and cancelled) grouped by day, ordered by time_slot
3. Response includes: day_of_week, time_slot, class_type name/slug/color, max_capacity, status
4. Coach information included (display name if available, or email)

---

## Business Rules

| # | Rule | Enforcement |
|---|------|-------------|
| BR-01 | Sessions can only be created for weekdays (Mon–Fri) | Domain validation on create |
| BR-02 | A coach cannot be assigned to two sessions at the same day+time | Repository check before save |
| BR-03 | max_capacity must be >= 1 | Domain value object / entity validation |
| BR-04 | class_type_id must reference an existing, active ClassType | Application handler check |
| BR-05 | coach_id must reference a User with role=coach | Application handler check |
| BR-06 | day_of_week and time_slot are immutable after creation | Entity enforces; update command excludes these fields |
| BR-07 | Friday allows two sessions per time slot (GAP + Entrenamiento Libre) | No unique constraint on day+time alone; unique on day+time+class_type_id |
| BR-08 | The time_slot must be one of the 7 fixed gym slots | Domain value object (enum or validated VO) |

---

## Collateral Impact

### Affected Components

| Component | Type | Impact | Action Required |
|-----------|------|--------|-----------------|
| epic-booking | Dependency | ClassSession.id is the FK for bookings | Design DB schema with booking FK in mind; do not delete sessions with bookings |
| ClassType (Core/ClassType) | Read dependency | ClassSession references class_type_id | ClassType must exist before sessions are seeded; no changes to ClassType entity needed |
| Shared/Auth User | Read dependency | coach_id references users.id where role=coach | No changes to User entity; handler validates role at application layer |
| Delete strategy | Behavioral | Hard delete in this epic may break bookings later | In epic-booking, enforce guard: cannot delete session with bookings |

### Migration Requirements

- [ ] New migration: `create_class_sessions_table` (if not yet exists — confirmed NOT in current migrations list)
- [ ] Seeder: `ClassSessionSeeder` for the default weekly schedule (42 sessions: 7 slots x 4 days + 7 slots x 2 class types on Friday = 28 + 14 = 42)

### Risk Assessment

| Risk | Probability | Severity | Mitigation |
|------|-------------|----------|------------|
| Hard delete breaks future bookings | High | High | Add `SessionHasBookingsException` guard now (no-op in this epic) |
| Friday dual-session logic is ambiguous | Medium | Medium | Clearly document: two separate ClassSession rows for same day+time on Friday |
| Coach role check missing | Low | Medium | Validate role=coach in application handler |

---

## Seeder: Default Weekly Schedule

The default schedule to seed on deploy:

| Day | Slots | Class Type(s) | Sessions count |
|-----|-------|---------------|----------------|
| Monday | 07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15 | tren-superior | 7 |
| Tuesday | 07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15 | tren-inferior | 7 |
| Wednesday | 07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15 | tren-superior | 7 |
| Thursday | 07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15 | full-body | 7 |
| Friday | 07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15 | gap + entrenamiento-libre | 14 (2 per slot) |
| **Total** | | | **42 sessions** |

Seeder assigns `coach_id = null` by default (no coach assigned until admin sets one), or assigns a placeholder coach if a coach user exists in the database. `max_capacity` defaults to 20.

> Note: If coach_id is nullable in the schema, the seeder sets it to null. Admin assigns coaches post-seed.

---

## API Endpoints Summary

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | /api/schedule | None | Public weekly schedule |
| GET | /api/class-sessions | Admin | List all sessions (filterable) |
| POST | /api/class-sessions | Admin | Create a session |
| GET | /api/class-sessions/{id} | Admin | Get session detail |
| PUT | /api/class-sessions/{id} | Admin | Update session |
| DELETE | /api/class-sessions/{id} | Admin | Delete session |
| PATCH | /api/class-sessions/{id}/cancel | Admin | Cancel session |
| PATCH | /api/class-sessions/{id}/restore | Admin | Restore cancelled session |
| GET | /api/coach/sessions | Coach | Coach's own sessions |

Full API contracts to be defined in `/docs/api-contracts/class-sessions/`.

---

## Out of Scope (MVP)

| Item | Reason | Info Needed Now |
|------|--------|-----------------|
| Weekend sessions | Not defined by gym owner | None — skip entirely |
| ClassType CRUD (create/edit/delete class types) | ClassTypes are fixed/seeded in MVP | Class type IDs needed for seeder |
| Session recurrence / calendar generation | Schedule is static weekly pattern | None |
| Attendance tracking | Belongs to epic-booking | ClassSession.id FK must be stable |
| Booking capacity enforcement | Belongs to epic-booking | max_capacity field must exist in schema now |
| Frontend UI | No frontend until epic-foundation is complete | None |
| Notifications (coach assigned, session cancelled) | Future enhancement | None |
| Coach display name / profile | Coach is User from Shared/Auth (email only for now) | None |

---

## Definition of Done

- [ ] Migration `create_class_sessions_table` created and reversible
- [ ] ClassSession entity, value objects, and domain exceptions implemented
- [ ] ClassSessionRepositoryInterface defined in Domain layer
- [ ] ClassSessionRepository implemented in Infrastructure layer
- [ ] All application Commands and Queries implemented with handlers
- [ ] All HTTP Actions implemented (thin — max 20 lines)
- [ ] All domain exceptions mapped to correct HTTP status codes (no 500s)
- [ ] API contracts documented in `/docs/api-contracts/class-sessions/`
- [ ] ClassSessionSeeder creates the 42 default sessions
- [ ] Unit tests for ClassSession entity (state transitions, business rules)
- [ ] Feature tests for all 9 API endpoints
- [ ] PHPStan passes (level configured in project)
- [ ] No queries in loops (performance rule compliance)
- [ ] IDs are Value Objects (ClassSessionId extends Ulid)
- [ ] Requests use only getDto() — no framework validation rules()

---

## Time Constraints

**Deadline:** None
**Type:** None
**Reason:** No external deadline defined for this epic
**Calendar Conflicts:** None identified

---

## Open Questions

1. **Coach assignment in seeder:** Should the default seeder create sessions with `coach_id = null` (no coach assigned), or should it require at least one coach user to exist? Recommended: nullable coach, admin assigns post-deploy.
2. **Soft delete vs hard delete for ClassSession:** The current spec uses hard delete. Once epic-booking exists, this will need to change. Confirm: is a soft delete (`deleted_at`) acceptable now to future-proof? Recommendation: use soft delete from the start.
3. **Coach display name:** The `User` entity currently only has `email` (no `name`/`display_name` field). For the public schedule, should the coach name be shown? If yes, a `name` field may need to be added to the User/Staff entity. **For now: show coach email in the public schedule, or omit coach info from the public endpoint.**
4. **Status visibility on public schedule:** Should cancelled sessions appear on the public schedule (e.g. "Esta clase ha sido cancelada hoy")? Recommendation: yes, include cancelled sessions in the public response so members are informed.
