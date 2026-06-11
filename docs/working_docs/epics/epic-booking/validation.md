# Requirement Validation Report — epic-booking

**Document:** `docs/working_docs/epics/epic-booking/requirements.md`
**Date:** 2026-06-11
**Validator:** Requirement Validator Agent
**Status:** Valid

---

## Summary

The epic-booking requirement document is complete, coherent, and ready to proceed to solution design. All entities, state machines, use cases, and acceptance criteria are clearly defined. The scope is appropriately constrained for MVP (no booking windows, no notification system, no plan-limit enforcement). No blocking issues found. Four open questions are documented; all are deferred to post-MVP with explicit justification.

---

## Business Alignment Assessment

**Primary Objective:** Operational Efficiency / Member Retention — replaces the external third-party booking app
**Contribution:** Clear — this feature is the direct replacement for the external app, which is the stated reason the project exists
**KPIs Defined:** Yes (qualitative MVP milestone: 100% of bookings managed on-platform)
**Justification Type:** Objective with data (the external app dependency is explicitly documented in `/docs/business/overview.md` as a problem being solved)

### Justification Quality

| Criteria | Status | Issue |
|----------|--------|-------|
| Specific numbers | Yes | All bookings migrate to platform |
| Evidence sources | Yes | `/docs/business/overview.md` — "external third-party app" problem |
| Revenue impact | Indirect | Reduces third-party cost and dependency |
| Customer names/tickets | N/A | Single gym, not SaaS — no ticket IDs applicable |

### RED FLAGS

- [x] No red flags — justification is objective and grounded in stated business goals

---

## Entities Identified

| Entity | CRUD Coverage | States Defined | Delete Strategy |
|--------|---------------|----------------|-----------------|
| Booking | Create (book), Read (roster, own list, admin list), Update (cancel) | confirmed, cancelled | Never hard-deleted — status transition only |
| ClassSession | Read only (dependency from epic-classes) | N/A | N/A |
| Member | Read only (dependency from epic-members) | N/A | N/A |

**Missing CRUD operations:**

| Operation | Entity | Decision |
|-----------|--------|----------|
| Update booking (reschedule) | Booking | Out of scope for MVP — noted |
| Admin force-cancel booking | Booking | Out of scope for MVP — member-only cancel |
| Delete booking (hard) | Booking | Explicitly excluded — history preserved |

---

## State Machine Assessment

### Booking

```
(new) → confirmed → cancelled
```

| Transition | Trigger | Conditions | Documented |
|------------|---------|------------|------------|
| new → confirmed | Member books | Session active, has capacity, no duplicate | Yes |
| confirmed → cancelled | Member cancels | Booking owned by requester | Yes |

**Missing transitions check:**
- Admin force-cancel: Not in scope. Acceptable for MVP.
- Re-confirm a cancelled booking: Not in scope. Member would create a new booking.
- Session cancellation cascades to bookings: Not in scope for MVP. When a ClassSession is cancelled, existing bookings remain `confirmed` in DB — member sees the session is cancelled via session status. This is acceptable for MVP.

---

## Missing Use Cases

| Use Case | Reason | Priority | Disposition |
|----------|--------|----------|-------------|
| Reschedule booking | Change session without cancelling | Low | Post-MVP |
| Admin force-cancel booking | Override member booking | Low | Post-MVP |
| Session cancellation cascades | Auto-cancel bookings when session cancelled | Medium | Post-MVP |
| Booking notifications | Email/push on booking/cancellation | Low | Post-MVP |
| Plan limit enforcement | Enforce `classes_per_month` from membership plan | Medium | Explicitly deferred with justification |

All deferred items are explicitly documented in Open Questions with justification. No missing use cases block MVP delivery.

---

## Collateral Impact Analysis

| Component | Type | Impact | Action Required |
|-----------|------|--------|-----------------|
| `ClassSessionRepositoryInterface` | Read dependency | `getById()` already exists — no change needed | None |
| `MemberRepositoryInterface` | Read dependency | `getById()` already exists — no change needed | None |
| `routes/api.php` | Modification | New booking routes | Extend |
| `AppServiceProvider` | Modification | New DI binding | Add binding |
| `database/migrations/` | New file | `bookings` table | Create migration |
| `epic-classes` delete logic | Future consideration | Soft-deleting a session with confirmed bookings is safe (bookings remain, session is filtered) | Document in design |

---

## Slicing Assessment

**Size:** Small-Medium
**Slicing needed:** No — scope is well-contained
**Out of scope dependencies:**

| Item | Info Needed Now | Why |
|------|----------------|-----|
| ClassSession `status` field | Already designed in epic-classes | Booking must check session is `active` |
| ClassSession `max_capacity` | Already designed in epic-classes | Capacity enforcement reads this field |
| Member `id` and status | Already designed in epic-members | Booking is linked to MemberId |
| MemberId VO | Already in `src/Core/Member/Domain/ValueObjects/MemberId.php` | Used as FK in booking |
| ClassSessionId VO | Already in `src/Core/ClassSession/Domain/ValueObjects/ClassSessionId.php` | Used as FK in booking |

All dependencies are already designed. No blocking information gaps.

---

## Time Constraints Assessment

**Deadline:** Not specified
**Type:** None
**Reason:** Internal MVP milestone, no business calendar event
**Realistic:** Yes — scope is small
**Calendar conflicts:** None
**Buffer included:** N/A

### Deadline Risk Analysis

None — no deadline defined.

---

## Testing Assessment

**Tests defined:** Yes
| Test Type | Required | Defined | Gap |
|-----------|----------|---------|-----|
| Unit | Yes | Yes — Booking entity state transitions | None |
| Feature/Integration | Yes | Yes — all endpoints | None |
| E2E | No | N/A — out of scope for MVP | None |
| UAT | No | N/A — single gym, admin validates | None |

**Critical scenarios identified:**
- Session full → 422 (`SESSION_FULL`)
- Duplicate booking → 409 (`BOOKING_ALREADY_EXISTS`)
- Cancel non-owned booking → 403 (`BOOKING_NOT_OWNED`)
- Cancel already-cancelled booking → 422 (`BOOKING_ALREADY_CANCELLED`)

**Test data requirements:** Requires at least one active ClassSession with a member to test booking flows.

---

## Definition of Done Assessment

**DoD defined:** Yes

| Criteria | Defined | Clear |
|----------|---------|-------|
| Acceptance criteria | Yes | Yes — per user story |
| Quality gates | Yes | PHPStan + no N+1 queries |
| Sign-off process | N/A | Single gym, admin is the owner |
| Training needs | No | No formal training needed for MVP |

---

## Red Flags

- None detected.

---

## Open Questions for Stakeholder

1. **Plan limit enforcement:** Should the system enforce `classes_per_month` from the membership plan? Recommendation: no in MVP. **Jose David to confirm.**
2. **Booking window:** Should there be a cutoff time before which a member must book (e.g., no booking within 1 hour of session)? Recommendation: no restriction in MVP.
3. **Coach roster access:** The requirement gives coaches access to the roster for any session (not restricted to their own). Is this acceptable? Recommendation: yes — coaches need to prepare regardless of assignment.
4. **Session cancellation cascade:** When a ClassSession is cancelled, should existing bookings be auto-cancelled? Recommendation: not in MVP — notify manually or leave bookings intact (session status is visible).

---

## Checklist Summary

| Area | Result |
|------|--------|
| Business Alignment | 4/4 passed |
| Content Completeness | 5/5 passed |
| Use Case Coverage | 5/5 documented (missing ones explicitly deferred) |
| Entity States | 2/2 passed (Booking state machine complete) |
| Collateral Impact | 5/5 passed |
| Slicing | Passed — no slicing needed |
| Time Constraints | Passed — no deadline |
| Testing | 4/4 test types assessed |
| Definition of Done | Passed |

---

## Recommendations

1. Proceed to solution design (`/requirement-design`) with this requirement as-is.
2. Confirm with Jose David that plan limit enforcement (`classes_per_month`) is out of scope for MVP.
3. In the design document, explicitly define the capacity-count strategy: **live count of confirmed bookings** (no denormalized counter column), which avoids race condition complexity at this gym's scale (~100 members max).
4. In the design, consider the session-cancellation cascade as a future task and add a note in the `bookings` table design to make that migration additive later.
