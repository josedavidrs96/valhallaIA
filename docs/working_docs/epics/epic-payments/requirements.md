# Epic: Payment Tracking (epic-payments)

**Type:** Epic
**Status:** Draft
**Created:** 2026-06-11
**Author:** Requirement Writer Agent
**Epic Reference:** N/A (top-level epic)

---

## Business Alignment

**Objective:** Operational Efficiency — replace manual/paper payment tracking with a digital record
**KPI Target:** 100% of monthly payments recorded digitally from day one; admin time spent identifying overdue members reduced from ~30 min/day (manual spreadsheet check) to under 5 min/day
**Evidence:** Current state — the gym owner tracks cash payments in a spreadsheet or notebook. There is no automated way to identify members who have not paid this month. Members cannot verify their own payment history without asking the admin. This is one of the three core pain points identified in the project overview (`/docs/business/overview.md`).

---

## Problem Statement

### Current Situation

The gym owner records payments manually:
- Each cash payment is noted in a spreadsheet or on paper
- No automatic detection of overdue members (admin must compare member list vs. payment list manually each month)
- Members have no self-service access to their own payment history
- No audit trail of who recorded a payment or when

### Pain Points

- Admin wastes 20–30 minutes every month cross-referencing spreadsheets to find who has not paid
- No digital proof of payment for members
- If the spreadsheet is lost or corrupted, payment history is gone
- Admin cannot quickly answer "has this member paid this month?" during check-in

### Impact if Not Solved

- Admin workload remains high despite the new platform
- Members may dispute unpaid dues with no digital record to reference
- Overdue members may continue accessing the gym undetected

---

## Proposed Solution

Build a Payment Tracking module within the `Billing/Payment` bounded context. The admin can record a cash payment for a member, specifying the amount, date, and which membership plan it covers. The admin can view a list of overdue members (members who have no payment recorded for the current month). Members can view their own payment history.

No payment gateway integration. Cash only. The admin is the only actor who can record or manage payments.

### User Stories

#### US-001: Admin records a cash payment

**As an** admin
**I want** to record a cash payment for a member
**So that** the payment is digitally stored and the member is no longer considered overdue

**Acceptance Criteria:**
- [ ] Admin can submit: member_id, amount (in cents), payment_date, membership_plan_id (the plan this payment covers), and optional notes
- [ ] System creates a Payment record linked to the member and plan
- [ ] Payment is associated with a billing month (derived from payment_date: year + month)
- [ ] If the same member already has a payment for that billing month, the system returns a 409 error with a clear message (`PAYMENT_ALREADY_EXISTS_FOR_MONTH`)
- [ ] Admin sees the newly created payment detail after creation
- [ ] Only admin role can perform this action

#### US-002: Admin views overdue members

**As an** admin
**I want** to see a list of members who have not paid for the current month
**So that** I can follow up and collect dues

**Acceptance Criteria:**
- [ ] The list shows all active members who do NOT have a payment recorded for the current calendar month
- [ ] Each row shows: member number, full name, email, active membership plan name, last payment date (or "Never" if no payments)
- [ ] List is ordered by member number ascending
- [ ] Only admin role can access this endpoint
- [ ] The "current month" is determined by the server date at the time of the request

#### US-003: Admin lists all payments (with filters)

**As an** admin
**I want** to view a list of all recorded payments, optionally filtered by member or month
**So that** I have a complete audit trail of cash payments

**Acceptance Criteria:**
- [ ] Admin can filter by: member_id, year, month
- [ ] List is paginated (default 20 per page)
- [ ] Each row shows: payment id, member number, member full name, amount, payment_date, plan name, billing_month (YYYY-MM), recorded_at
- [ ] List is ordered by payment_date descending by default
- [ ] Only admin role can access this endpoint

#### US-004: Admin views a single payment detail

**As an** admin
**I want** to view the full detail of a specific payment
**So that** I can verify the recorded data

**Acceptance Criteria:**
- [ ] Admin can access any payment by ID
- [ ] Shows: payment id, member id, member full name, member number, amount, payment_date, plan name, billing_month, notes, recorded_by (admin user id), recorded_at

#### US-005: Member views their own payment history

**As a** member
**I want** to see a list of my own payments
**So that** I can verify my payment record and know when my next payment is due

**Acceptance Criteria:**
- [ ] Member can only see their own payments (no access to other members' payments)
- [ ] List is ordered by payment_date descending
- [ ] Each row shows: amount, payment_date, plan name, billing_month
- [ ] Returns 403 if a non-member role tries to access this endpoint
- [ ] No pagination required in MVP (members typically have at most 12–24 payments)

---

## Entities

| Entity | Description | States |
|--------|-------------|--------|
| Payment | A single recorded cash payment for a member | N/A — no status lifecycle; payments are immutable once created |

### Notes on Entity Design

- Payments are **immutable** — there is no edit or void flow in MVP. If a mistake is made, the admin records a corrective note (out of scope). This simplifies the state machine.
- The `billing_month` field (format: `YYYY-MM`) is derived from `payment_date` and stored explicitly for fast overdue queries. It is NOT a user input — the system derives it.
- `recorded_by` stores the admin's UserId who created the record (audit trail).
- `amount` is stored in cents (integer) to avoid floating-point issues.

### State Machine: Payment

Payments have no state machine — they are created and remain immutable.

```
[Admin submits payment] ──► CREATED (immutable)
```

No transitions. No delete in MVP.

---

## Use Cases

### UC-001: Record Payment

**Actor:** Admin
**Preconditions:** Admin is authenticated. Member exists and is active. Membership plan exists.
**Postconditions:** A new Payment record exists linked to the member.

**Main Flow:**
1. Admin sends `POST /api/admin/payments` with member_id, amount, payment_date, membership_plan_id, optional notes
2. System validates: member exists, plan exists, no payment already recorded for this member+billing_month
3. System derives billing_month from payment_date (e.g. "2026-06" from "2026-06-05")
4. System creates Payment record with recorded_by = authenticated admin's user_id
5. System returns 201 with payment detail

**Alternative Flows:**
- Admin corrects a wrong date: not supported in MVP — admin must note the error in the `notes` field

**Error Scenarios:**
- E1: Member not found → 404 `MEMBER_NOT_FOUND`
- E2: Plan not found → 422 `MEMBERSHIP_PLAN_NOT_FOUND`
- E3: Payment already exists for member+month → 409 `PAYMENT_ALREADY_EXISTS_FOR_MONTH`
- E4: Amount is zero or negative → 422 `INVALID_PAYMENT_AMOUNT`

---

### UC-002: Get Overdue Members

**Actor:** Admin
**Preconditions:** Admin is authenticated.
**Postconditions:** List of overdue active members returned.

**Main Flow:**
1. Admin sends `GET /api/admin/payments/overdue`
2. System determines current billing month (server date)
3. System queries: all active members who do NOT have a payment with billing_month = current month
4. Returns list ordered by member_number ASC

**Error Scenarios:**
- None expected — empty list is a valid result

---

### UC-003: List Payments (Admin)

**Actor:** Admin
**Preconditions:** Admin is authenticated.
**Postconditions:** Filtered, paginated list of payments returned.

**Main Flow:**
1. Admin sends `GET /api/admin/payments?member_id=xxx&year=2026&month=6&page=1&per_page=20`
2. System applies filters (all optional) and pagination
3. Returns list with meta (total, page, per_page)

---

### UC-004: Get Payment Detail (Admin)

**Actor:** Admin
**Preconditions:** Admin is authenticated. Payment exists.
**Postconditions:** Full payment detail returned.

**Main Flow:**
1. Admin sends `GET /api/admin/payments/{id}`
2. System returns full payment detail

**Error Scenarios:**
- E1: Payment not found → 404 `PAYMENT_NOT_FOUND`

---

### UC-005: Get My Payments (Member)

**Actor:** Member (authenticated)
**Preconditions:** Member is authenticated with role=member.
**Postconditions:** Member's own payment history returned.

**Main Flow:**
1. Member sends `GET /api/member/payments`
2. System identifies member by authenticated user_id
3. Returns all payments for that member, ordered by payment_date DESC

**Error Scenarios:**
- E1: No member profile found for this user → 404 (should not happen in normal flow)

---

## Collateral Impact

| Component | Impact | Action Required |
|-----------|--------|-----------------|
| Core/Member (MemberRepositoryInterface) | Overdue query requires fetching all active members and their current plan names | Reuse existing MemberRepositoryInterface or add a specific method for overdue logic |
| Shared/Auth/User | `recorded_by` references UserId from the Auth context | No changes — reference by UserId value only |
| routes/api.php | New routes under `/api/admin/payments` and `/api/member/payments` | Add routes in this epic |
| epic-members | Payment entity references MemberId and MembershipPlanId — these must exist before implementing payments | epic-members must be designed before payments; MemberId and MembershipPlanId are already defined |

---

## Database Notes

A new `payments` table must be created with these columns:
`id` (ULID PK), `member_id` (FK → members.id), `membership_plan_id` (FK → membership_plans.id), `recorded_by` (FK → users.id), `amount_cents` (INT NOT NULL), `payment_date` (DATE NOT NULL), `billing_month` (CHAR(7) NOT NULL — format YYYY-MM), `notes` (TEXT NULL), `created_at`, `updated_at`.

Unique constraint: `(member_id, billing_month)` — one payment per member per month.

---

## Definition of Done

- [ ] Admin can record a cash payment for a member
- [ ] System prevents duplicate payments for the same member+month (409 response)
- [ ] Admin can list all payments with optional filters (member, year, month)
- [ ] Admin can view a single payment detail
- [ ] Admin can view the overdue members list (active members with no payment this month)
- [ ] Member can view their own payment history
- [ ] All endpoints protected by correct role middleware (admin vs. member)
- [ ] `billing_month` is derived from `payment_date` — never taken from user input
- [ ] `recorded_by` always captures the authenticated admin's user_id
- [ ] Unit tests: Payment entity creation, billing_month derivation
- [ ] Feature tests: all endpoints (happy path + main error scenarios)
- [ ] PHPStan passes at configured level
- [ ] No N+1 queries in overdue list (verified by code review)
- [ ] API contracts documented

---

## Time Constraints

**Deadline:** None (MVP ongoing)
**Type:** None
**Reason:** Phase 3 feature — requires epic-members to be complete first
**Calendar Conflicts:** None

---

## Open Questions

None — all business decisions resolved based on available context. The one-payment-per-month constraint is a deliberate simplification for the MVP (cash gym does not support partial payments or installments).
