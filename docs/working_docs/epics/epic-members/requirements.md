# Epic: Member Management (epic-members)

**Type:** Epic
**Status:** Draft
**Created:** 2026-06-10
**Author:** Requirement Writer Agent
**Epic Reference:** N/A (top-level epic)

---

## Business Alignment

**Objective:** Operational Efficiency — replace manual/paper member management
**KPI Target:** 100% of members registered digitally within the first month of launch; admin time on member management reduced from ~2h/day to under 30 min/day
**Evidence:** Current state — member registration is done on paper or spreadsheets. No digital record of membership plans per member. Admin cannot quickly check who is active or overdue. This is one of the three core pain points identified in the project overview.

---

## Problem Statement

### Current Situation

The gym owner manages members manually:
- New member registration via paper forms
- Membership plan assignment noted in a spreadsheet
- No way to quickly see who is active, inactive, or pending approval
- No self-service for members to check their own profile or active plan

### Pain Points

- Admin wastes time searching spreadsheets to find member data
- No audit trail of when a member joined or who changed their plan
- Member cannot check their own plan without asking the admin
- No digital state machine — a member being "inactive" is just a paper note
- Impossible to filter members by plan or status quickly

### Impact if Not Solved

- Admin workload does not decrease despite the new platform
- Member satisfaction remains low (no self-service)
- Other epics (Booking, Payments) cannot be built without a Member entity

---

## Proposed Solution

Build a Member Management module within the `Core/Member` bounded context. The admin can register members (creating both a `User` with role `member` and a `Member` profile), assign a membership plan, edit the profile, and activate/deactivate members. Members can log in and view their own profile and active plan.

Membership plans already exist as seeded data (3 plans: 2 Days €35, 3 Days €38, 4-5 Days €40). No CRUD for plans in this epic — read only.

### User Stories

#### US-001: Admin registers a new member

**As an** admin
**I want** to create a new member with their personal data and assign a membership plan
**So that** the member has a digital record and a linked user account to log in with

**Acceptance Criteria:**
- [ ] Admin can submit: first name, last name, email, phone (optional), date of birth (optional), join date, membership plan
- [ ] System creates a `User` (role=member, status=pending_approval) and a `Member` profile linked by user_id
- [ ] Member number is auto-assigned sequentially
- [ ] Default password is generated and must be changed on first login (must_change_password=true)
- [ ] If email already exists, system returns a 409 error with clear message
- [ ] Admin sees the newly created member detail after creation

#### US-002: Admin assigns or changes a member's membership plan

**As an** admin
**I want** to assign a membership plan to a member (at creation or later)
**So that** the member's entitlements are correctly tracked

**Acceptance Criteria:**
- [ ] Admin can select any active membership plan from the list
- [ ] Only active plans appear in the selector
- [ ] The assigned plan is stored in the `member_membership_plan` pivot table (with assigned_at date)
- [ ] Previous plan assignment is preserved in history (not deleted)
- [ ] A member always has exactly one active plan assignment at a time

#### US-003: Admin edits a member profile

**As an** admin
**I want** to update a member's personal information
**So that** member records are kept accurate

**Acceptance Criteria:**
- [ ] Admin can edit: first name, last name, phone, date of birth, emergency contact name, emergency contact phone, notes, profile photo path
- [ ] Email is NOT editable via this flow (auth concern — separate)
- [ ] Join date is NOT editable after creation
- [ ] Changes are persisted immediately
- [ ] System returns updated member detail

#### US-004: Admin activates a member

**As an** admin
**I want** to activate a pending or inactive member
**So that** the member can log in and book classes

**Acceptance Criteria:**
- [ ] Admin can activate a member in `pending_approval` or `inactive` state
- [ ] Activation changes User status to `active`
- [ ] Member can log in after activation
- [ ] Trying to activate an already active member returns a 422 with a clear message

#### US-005: Admin deactivates a member

**As an** admin
**I want** to deactivate an active member
**So that** members who have left or stopped paying cannot log in or book

**Acceptance Criteria:**
- [ ] Admin can deactivate an `active` member
- [ ] Deactivation changes User status to `inactive`
- [ ] Deactivated member cannot log in (existing tokens invalidated)
- [ ] Trying to deactivate an already inactive member returns a 422

#### US-006: Admin lists members with filters

**As an** admin
**I want** to see a paginated list of members filtered by status and/or plan
**So that** I can quickly find specific members or see overdue ones

**Acceptance Criteria:**
- [ ] Admin can filter by: status (active / inactive / pending_approval), membership plan (by plan id)
- [ ] List is paginated (default 20 per page)
- [ ] Each row shows: member number, full name, email, plan name, status, join date
- [ ] List is ordered by member number ascending by default
- [ ] Filters can be combined

#### US-007: Admin views a member detail

**As an** admin
**I want** to view the full profile of a specific member
**So that** I can see all their information in one place

**Acceptance Criteria:**
- [ ] Admin can access any member by ID
- [ ] Shows: member number, full name, email, phone, date of birth, join date, status, current plan, emergency contacts, notes, created at

#### US-008: Member views their own profile and active plan

**As a** member (socio)
**I want** to see my own profile and current membership plan
**So that** I know what I am entitled to and can verify my information

**Acceptance Criteria:**
- [ ] Member can only see their own profile (no access to other members)
- [ ] Shows: full name, email, member number, join date, active plan (name, price, classes per month), status
- [ ] Returns 403 if a non-member role tries to access this endpoint

---

## Entities

| Entity | Description | States |
|--------|-------------|--------|
| Member | Gym member profile (socio). Linked 1:1 to a User. | N/A — status lives in User |
| User | Auth entity (already exists). Role=member for gym members. | pending_approval → active ↔ inactive |
| MembershipPlan | Predefined plans (read-only in this epic). | active / inactive |
| MemberPlanAssignment | Pivot: which plan a member has and when assigned. | N/A — historical record |

### State Machine: User (for members)

```
[Admin creates member] ──► pending_approval
                                │
                          Admin activates
                                │
                                ▼
                            active ◄──── Admin re-activates
                                │
                          Admin deactivates
                                │
                                ▼
                            inactive
```

**Note:** The `suspended` status from User entity is out of scope for this epic (used for payment delinquency, handled in Billing epic). The `pending_approval` → `active` transition maps to the existing `User.approve()` method.

### State Transitions

| From | To | Method | Condition |
|------|----|--------|-----------|
| pending_approval | active | User.approve() | Admin triggers activation |
| active | inactive | User.deactivate() | Admin triggers deactivation |
| inactive | active | User.activate() | Admin re-activates |

---

## Use Cases

### UC-001: Create Member

**Actor:** Admin
**Preconditions:** Admin is authenticated. Email does not already exist in users table. At least one active membership plan exists.
**Postconditions:** A new User (role=member, status=pending_approval) and Member profile exist. MemberPlanAssignment created.

**Main Flow:**
1. Admin sends POST `/api/admin/members` with personal data + membership_plan_id
2. System validates: required fields present, email valid format, plan exists and is active
3. System generates MemberId (ULID) and UserId (ULID)
4. System creates User with role=member, status=pending_approval, must_change_password=true, auto-generated temporary password
5. System auto-assigns next member_number
6. System creates Member profile linked to User
7. System creates MemberPlanAssignment with today as assigned_at
8. System returns 201 with full member detail

**Alternative Flows:**
- Admin activates member immediately: Admin can follow up with PUT `/api/admin/members/{id}/activate`

**Error Scenarios:**
- E1: Email already exists → 409 `MEMBER_EMAIL_ALREADY_EXISTS`
- E2: Plan not found or inactive → 422 `MEMBERSHIP_PLAN_NOT_FOUND`
- E3: Required field missing → 422 with field-level detail

---

### UC-002: Update Member Profile

**Actor:** Admin
**Preconditions:** Member exists.
**Postconditions:** Member profile updated.

**Main Flow:**
1. Admin sends PUT `/api/admin/members/{id}` with updated fields
2. System validates: member exists
3. System updates editable fields only (no email, no join_date)
4. Returns updated member detail

**Error Scenarios:**
- E1: Member not found → 404 `MEMBER_NOT_FOUND`

---

### UC-003: Assign Membership Plan

**Actor:** Admin
**Preconditions:** Member exists. Plan is active.
**Postconditions:** New active MemberPlanAssignment recorded. Previous assignment remains as history.

**Main Flow:**
1. Admin sends PUT `/api/admin/members/{id}/plan` with membership_plan_id
2. System validates plan is active
3. System creates new MemberPlanAssignment (no delete of old ones)
4. Returns updated member detail

**Error Scenarios:**
- E1: Member not found → 404
- E2: Plan not found or inactive → 422

---

### UC-004: Activate Member

**Actor:** Admin
**Preconditions:** Member exists with status pending_approval or inactive.
**Postconditions:** User status = active.

**Main Flow:**
1. Admin sends PUT `/api/admin/members/{id}/activate`
2. System calls User.approve() or User.activate() depending on current status
3. Returns updated member detail

**Error Scenarios:**
- E1: Member already active → 422 `INVALID_STATUS_TRANSITION`
- E2: Member not found → 404

---

### UC-005: Deactivate Member

**Actor:** Admin
**Preconditions:** Member is active.
**Postconditions:** User status = inactive. Sanctum tokens for this user revoked.

**Main Flow:**
1. Admin sends PUT `/api/admin/members/{id}/deactivate`
2. System calls User.deactivate()
3. System revokes all Sanctum tokens for the user
4. Returns updated member detail

**Error Scenarios:**
- E1: Member not active → 422 `INVALID_STATUS_TRANSITION`
- E2: Member not found → 404

---

### UC-006: List Members

**Actor:** Admin
**Preconditions:** Admin authenticated.
**Postconditions:** Paginated list returned.

**Main Flow:**
1. Admin sends GET `/api/admin/members?status=active&plan_id=xxx&page=1&per_page=20`
2. System applies filters and pagination
3. Returns list with meta (total, page, per_page)

---

### UC-007: Get Member Detail (Admin)

**Actor:** Admin
**Preconditions:** Admin authenticated. Member exists.
**Postconditions:** Full member detail returned.

**Main Flow:**
1. Admin sends GET `/api/admin/members/{id}`
2. Returns full member detail including current plan

**Error Scenarios:**
- E1: Member not found → 404

---

### UC-008: Get Own Profile (Member)

**Actor:** Member (authenticated)
**Preconditions:** Member authenticated with role=member.
**Postconditions:** Own profile returned.

**Main Flow:**
1. Member sends GET `/api/member/profile`
2. System identifies member by authenticated user_id
3. Returns profile with current active plan

**Error Scenarios:**
- E1: No member profile found for this user → 404 (should not happen in normal flow)

---

## Collateral Impact

| Component | Impact | Action Required |
|-----------|--------|-----------------|
| Shared/Auth/User | Members are Users — creation must also create User record | CreateMemberHandler dispatches CreateUserCommand or directly uses UserRepository |
| Shared/Auth/UserStatus | Activate/deactivate operations use existing User state machine methods | No changes needed — reuse User.approve(), activate(), deactivate() |
| Billing/Payment (future) | Payments will reference Member by MemberId | No action now — design MemberId as stable identifier |
| Core/Booking (future) | Bookings will reference Member by MemberId | No action now |
| routes/api.php | New routes under `/api/admin/members` and `/api/member/profile` | Add routes in this epic |
| MemberTable | Already exists with schema | No migration needed — table was created in epic-foundation |
| MembershipPlanTable | Already exists and seeded | No migration needed |

---

## Database Notes

The `members` table was created in epic-foundation with the following columns:
`id`, `user_id`, `member_number`, `first_name`, `last_name`, `phone`, `date_of_birth`, `profile_photo`, `join_date`, `emergency_contact_name`, `emergency_contact_phone`, `notes`, `created_at`, `updated_at`.

**Missing column identified:** The `members` table does not have a `membership_plan_id` foreign key column. The design must handle plan assignment via a separate `member_plan_assignments` pivot table OR add a `current_plan_id` column to `members`. The recommended approach is a pivot table to preserve history.

**New migration needed:** `create_member_plan_assignments_table` with columns: `id` (ULID), `member_id` (FK → members.id), `membership_plan_id` (FK → membership_plans.id), `assigned_at` (date), `created_at`, `updated_at`.

---

## Definition of Done

- [ ] Admin can create a member and the member receives a linked User account
- [ ] Admin can assign/change a membership plan for a member
- [ ] Admin can edit member profile fields
- [ ] Admin can activate and deactivate members
- [ ] Admin can list members with filters (status, plan) and pagination
- [ ] Admin can view full member detail
- [ ] Member can view their own profile and active plan
- [ ] All endpoints protected by correct role middleware
- [ ] Deactivation revokes all tokens for that user
- [ ] Unit tests: Member entity state transitions
- [ ] Feature tests: all 8 endpoints (happy path + main error scenarios)
- [ ] PHPStan passes at configured level
- [ ] No N+1 queries (verified by code review)
- [ ] API contracts documented in `/docs/api-contracts/members/`

---

## Time Constraints

**Deadline:** None (MVP ongoing)
**Type:** None
**Reason:** Core feature required before Booking and Billing epics can be implemented
**Calendar Conflicts:** None

---

## Open Questions

None — all business decisions are resolved based on the available context. The pivot table approach for plan assignment is a design decision made in this document.
