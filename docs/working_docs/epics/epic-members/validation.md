# Requirement Validation Report

**Document:** `docs/working_docs/epics/epic-members/requirements.md`
**Date:** 2026-06-10
**Type:** Epic
**Status:** Valid — minor gaps self-resolved below

---

## Summary

The requirements document for epic-members is well-structured, complete, and ready for design and implementation. It covers all CRUD operations for the Member entity, a clear state machine (reusing the existing User state machine), collateral impact on the Auth bounded context, and an identified missing database column (plan assignment). All gaps found during this validation were minor and have been resolved inline within this report — no stakeholder questions are required.

---

## Business Alignment Assessment

**Primary Objective:** Operational Efficiency (digitize manual member management)
**Contribution:** Clear — replaces paper/spreadsheet member registry with digital platform
**KPIs Defined:** Yes — 100% digital registration within first month, admin time reduction from ~2h/day to <30 min/day
**Justification Type:** Objective — aligned with one of the three core pain points in `docs/business/overview.md`

### Justification Quality

| Criteria | Status | Issue |
|----------|--------|-------|
| Specific numbers | Yes | Admin time targets defined (2h → 30min) |
| Evidence sources | Yes | References business/overview.md pain points |
| Revenue impact | Indirect | Core dependency for Booking/Billing epics — without this, no revenue features |
| Customer names/tickets | N/A | Single-gym internal tool, no ticket system |

**RED FLAGS:** None.

---

## Entities Identified

| Entity | CRUD Coverage | States Defined | Delete Strategy |
|--------|---------------|----------------|-----------------|
| Member | Create ✓, Read ✓, Update ✓, Delete ✗, List ✓ | N/A (status in User) | Not defined — see below |
| User (role=member) | Create ✓ (via member creation), Read indirect, Update indirect, Delete ✗ | pending_approval → active ↔ inactive ✓ | Soft delete (inherited from User entity via deleted_at) |
| MemberPlanAssignment | Create ✓, Read ✓, Update N/A (append-only), Delete ✗ | N/A (historical record) | Not applicable — append-only |
| MembershipPlan | Read ✓, List ✓ | Not in scope (read-only) | Not in scope |

### Gap — Member Delete

**Assessment:** Member deletion is not in scope for this epic. This is acceptable for MVP:
- The gym is small and single-location
- "Deleting" a member in practice means deactivating them (already covered by UC-005)
- Hard deletion of user accounts is a GDPR concern deferred to a future epic
- **Decision:** Soft delete for Member should be added to the DB schema (deleted_at column on members table) for forward compatibility, but no delete endpoint is exposed in this epic. Noted in Design as a migration requirement.

---

## Missing Use Cases

| Use Case | Reason | Priority | Resolution |
|----------|--------|----------|------------|
| Member delete (admin) | CRUD completeness | Low | Deferred — deactivation covers operational need. Soft delete column added to schema for future use. |
| Bulk activate/deactivate | Operational efficiency | Low | Out of scope for MVP — gym has ~50-100 members, manual is fine. |
| Member search by name | Usability | Medium | Covered by list with filters. Free-text search deferred — not required for MVP. |
| Export member list | Reporting | Low | Out of scope MVP. |
| Admin resets member password | Operational | Medium | Out of scope — covered by existing force-password-change flow at login. Admin can use a future "reset password" endpoint. Not a blocker. |

**No blocking gaps.** All missing cases are either deferred or covered by existing mechanisms.

---

## Missing State Information

| Entity | Missing Info | Resolution |
|--------|--------------|------------|
| Member | No delete state | Soft delete column to be added via migration; no endpoint exposed now. |
| MemberPlanAssignment | No end_date for plan assignment | Append-only approach means "current plan" = latest assignment by assigned_at. No end_date needed in MVP. |

---

## Collateral Impact

| Component | Type | Impact | Action Required |
|-----------|------|--------|-----------------|
| Shared/Auth/User | Behavioral | Member creation must also create a User record | CreateMemberHandler must coordinate with UserRepository or dispatch a command to create User first |
| Shared/Auth/UserRepository | API | Need to create User from within Member bounded context | Handler may inject UserRepositoryInterface directly (same service, same DB) — acceptable since Member BC is closely coupled to Auth |
| Sanctum tokens | Behavioral | Deactivation must revoke all tokens | DeactivateMemberHandler must revoke tokens via UserModel::tokens()->delete() or via a domain service |
| routes/api.php | API extension | New routes added | Non-breaking addition |
| members table | Data | Missing deleted_at column | New migration required to add deleted_at (soft delete support) |
| members table | Data | Missing plan assignment tracking | New migration: create_member_plan_assignments_table |
| Future Booking epic | Design dependency | Bookings will need MemberId | MemberId must be stable ULID — already the case |
| Future Billing epic | Design dependency | Payments will reference MemberId | Same — no action needed now |

---

## Slicing Assessment

**Size:** Medium (4 entities involved, 8 use cases, one state machine borrowed from existing User entity)
**Slicing needed:** No — the epic is well-bounded and deliverable as a single unit. The User state machine already exists. No new complex state machines introduced.

**Out of scope dependencies:**

| Item | Info Needed Now | Why |
|------|-----------------|-----|
| Booking epic | MemberId as stable ULID | DB schema already accommodates this |
| Billing epic | MemberId as stable ULID | Same |
| Member delete/GDPR | deleted_at column | Add now to schema to avoid future migration on populated table |
| Password reset | No design changes needed | Existing force-password-change covers first login |

**Red Flags:** None.

---

## Time Constraints Assessment

**Deadline:** Not specified
**Type:** None
**Reason:** MVP ongoing — no external deadline
**Realistic:** Yes — scope is well-defined
**Calendar conflicts:** None
**Buffer included:** N/A

### Deadline Risk Analysis

No deadline risks. Core dependency for other epics creates natural priority but no external constraint.

---

## Testing Assessment

**Tests defined:** Yes

| Test Type | Required | Defined in Requirements | Gap |
|-----------|----------|------------------------|-----|
| Unit | Yes | Yes (Member entity state transitions) | None |
| Integration | Yes | Yes (all 8+ endpoints) | None |
| E2E | No | N/A | Not required for MVP admin tool |
| UAT | Implicit | Acceptance criteria per US | Formal UAT process not defined — acceptable for single-user admin tool |

**Critical scenarios identified:** Yes
- Email uniqueness on creation
- Invalid status transitions (activate already active, etc.)
- Member cannot access other member profiles
- Deactivation revokes tokens
- Plan assignment preserves history

---

## Definition of Done Assessment

**DoD defined:** Yes

| Criteria | Defined | Clear |
|----------|---------|-------|
| Acceptance criteria | Yes | Yes — per user story |
| Quality gates | Yes | PHPStan, no N+1 queries |
| Sign-off process | Implicit | Admin tests in browser |
| Training needs | None | Simple CRUD UI |

---

## Red Flags

None identified. The requirement is complete and consistent.

---

## Open Questions for Stakeholder

None — all gaps resolved based on available context and business overview.

---

## Additions to Requirements (self-resolved)

The following items are added by this validation and must be reflected in the design:

1. **Add `deleted_at` column to `members` table** via a new migration (soft delete support for future use, no endpoint exposed now).
2. **The `member_plan_assignments` table** is needed (as noted in requirements.md Database Notes section). Design must specify this table's migration.
3. **Token revocation on deactivation** must be an explicit step in the DeactivateMember handler or a domain service — ensure it is implemented.
4. **CreateMember must atomically create User + Member** in the same transaction to avoid orphan User records.

---

## Checklist Summary

### Business Alignment: 5/5 passed
### Content Completeness: 5/5 passed
### Use Case Coverage: 7/8 (delete deferred — acceptable)
### Entity States: 4/4 passed
### Collateral Impact: 5/5 passed
### Slicing: 5/5 passed (no slicing needed — right size)
### Time Constraints: 4/4 passed (no deadline)
### Testing: 4/4 passed
### Definition of Done: 4/4 passed

---

## Recommendations

1. Ensure CreateMember runs in a DB transaction (User + Member creation must be atomic).
2. Add `deleted_at` to members table migration in this epic (forward-compatible).
3. Design the `member_plan_assignments` table with `assigned_at DATE NOT NULL` and no end_date (append-only history pattern).
4. Token revocation on deactivation: implement via `$userModel->tokens()->delete()` in the DeactivateMember handler — document as explicit step.
5. The admin list endpoint should return a lightweight RM (ReadModel) — avoid hydrating full Member entities for lists.
