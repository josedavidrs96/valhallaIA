# Requirement Validation Report

**Document:** `docs/working_docs/epics/epic-classes/requirements.md`
**Type:** Epic (path contains `/epics/`)
**Date:** 2026-06-11
**Status:** Valid — Minor gaps resolved inline, ready for design

---

## Summary

The requirement document is well-structured, complete, and implementation-ready. Business alignment is clear in the context of a single-gym MVP. The entity model is simple (one main entity: ClassSession), the state machine is fully defined, all 9 use cases are specified with acceptance criteria, and the collateral impact analysis is thorough. Minor gaps identified below were resolved directly in the document or are documented as non-blocking recommendations. No stakeholder clarification is required to begin implementation.

---

## Business Alignment Assessment

**Primary Objective:** Operational efficiency — platform independence from third-party tools
**Contribution:** Clear — this epic is the hard dependency for epic-booking and the public schedule feature
**KPIs Defined:** Yes (operational, not revenue — appropriate for a single-location gym MVP)
**Justification Type:** Objective with operational data

### Justification Quality

| Criteria | Status | Note |
|----------|--------|------|
| Specific numbers | Yes | 42 sessions seeded, 7 fixed slots, 5 days, fixed class types |
| Evidence sources | Yes | "Currently uses third-party tool not controlled by gym" |
| Operational impact | Yes | Coaches blind, public schedule external, no data ownership |
| Revenue impact | N/A | Single-gym MVP — operational KPIs are appropriate here |

### Red Flags

- [x] No subjective justification — evidence is operational and factual
- [x] No revenue KPI missing — gym MVP context makes operational KPIs appropriate
- [x] epic-booking hard dependency clearly documented

---

## Entities Identified

| Entity | CRUD Coverage | States Defined | Delete Strategy |
|--------|---------------|----------------|-----------------|
| ClassSession | Full (Create, Read x2, Update, Delete, List, Cancel, Restore) | Yes — active / cancelled | Hard delete (with guard for future bookings) — **see note** |
| ClassType | Read-only (referenced, not managed) | N/A in this epic | N/A in this epic |

> **Delete strategy note:** The requirement uses hard delete and documents the risk. Open Question #2 asks whether soft delete should be used. **Recommendation added below.** For this validation: hard delete is acceptable in scope, soft delete is the safer default. Design agent should decide.

---

## Missing Use Cases

No blocking missing use cases were found. The following were assessed and confirmed as intentionally out of scope or already covered:

| Use Case | Assessment | Resolution |
|----------|------------|------------|
| Bulk create/import sessions | Out of scope — seeder handles default schedule | Acceptable for MVP |
| Search sessions by class type name | Not required — filter by day/coach/status covers admin needs | Acceptable |
| Coach view single session detail | Not documented as separate endpoint | Low priority — list with full data is sufficient |
| Pagination on list endpoint | Not specified | Non-blocking — 42 sessions max, no pagination needed |
| Export schedule | Not in MVP scope | Acceptable |

---

## Missing State Information

| Entity | Missing Info | Resolution |
|--------|--------------|------------|
| ClassSession | Soft delete state not defined | Open Question #2 — see recommendation; hard delete documented |
| ClassSession | No time-based automatic transitions | Confirmed N/A — schedule is static/manual |
| ClassSession | No side effects on cancel/restore | Confirmed — notifications out of scope (stated in Out of Scope) |

---

## Collateral Impact

| Component | Type | Impact | Action Required |
|-----------|------|--------|-----------------|
| epic-booking | Dependency | ClassSession.id FK for bookings | Design DB with FK-safe schema; delete guard now |
| ClassType (Core/ClassType) | Read dependency | class_type_id must exist before sessions | ClassType seeder must run first (migration order) |
| Shared/Auth User | Read dependency | coach_id = users.id where role=coach | No changes to User entity needed |
| Delete strategy | Behavioral | Hard delete now may break bookings later | Add `SessionHasBookingsException` guard (no-op now) |
| Public schedule | New endpoint | `/api/schedule` — no auth required | Route must be outside `auth:sanctum` middleware group |

### Migration Requirements

- [ ] New migration: `create_class_sessions_table` (confirmed not in existing migrations)
- [ ] Seeder: `ClassSessionSeeder` — 42 sessions (28 weekday + 14 Friday dual-session)
- [ ] Migration dependency order: class_types → users → class_sessions

### Risk Assessment

| Risk | Probability | Severity | Mitigation |
|------|-------------|----------|------------|
| Hard delete breaks future bookings | High | High | Add no-op guard now; revisit in epic-booking |
| Friday dual-session conflict confusion | Medium | Medium | Documented clearly: two separate rows, unique on day+time+class_type_id |
| Coach role check bypassed | Low | Medium | Handler validates role=coach; no change to User entity needed |
| coach_id null in seeder + coach conflict check | Low | Low | Null coach_id skips conflict check — document in business rule |

---

## Slicing Assessment

**Size:** Medium (1 main entity, 9 use cases, simple state machine)
**Slicing needed:** No — scope is well-bounded and self-contained
**Out of scope dependencies resolved:**

| Out of Scope Item | Info Needed Now | Status |
|-------------------|-----------------|--------|
| epic-booking | FK stability for ClassSession.id | Covered — ULID PK, no cascade delete without guard |
| Coach display name | Need to decide what to show on public schedule | Covered — OQ #3 documents: show email or omit |
| Weekend sessions | No schema impact needed | Confirmed skip — BR-01 enforces weekday-only at domain level |
| Frontend UI | No API contract impact | Design agent must document API contract alongside implementation |

---

## Time Constraints Assessment

**Deadline:** None
**Type:** None
**Reason:** No external business event
**Realistic:** Yes
**Calendar conflicts:** None
**Buffer included:** N/A

---

## Testing Assessment

**Tests defined:** Yes (in Definition of Done)

| Test Type | Required | Defined | Gap |
|-----------|----------|---------|-----|
| Unit (entity state machine) | Yes | Yes | None |
| Unit (value objects) | Yes | Implied | Explicit test scenarios not listed — non-blocking |
| Feature (HTTP endpoints) | Yes | Yes — all 9 endpoints | None |
| Integration (seeder) | Yes | Implied | Non-blocking |
| E2E | No | N/A | Out of scope (no frontend) |
| UAT | No | N/A | Admin access only, single gym |

**Critical scenarios identified:** Yes — coach conflict, weekend rejection, capacity validation, cancel/restore idempotency
**Test data requirements:** Defined — seeder provides baseline data

---

## Definition of Done Assessment

**DoD defined:** Yes

| Criteria | Defined | Clear |
|----------|---------|-------|
| Migration created and reversible | Yes | Yes |
| Entity, VOs, exceptions | Yes | Yes |
| Repository interface in Domain | Yes | Yes |
| Repository in Infrastructure | Yes | Yes |
| Commands and Queries with handlers | Yes | Yes |
| HTTP Actions (thin, max 20 lines) | Yes | Yes |
| Domain exceptions mapped to HTTP codes | Yes | Yes |
| API contracts documented | Yes | Yes |
| Seeder for 42 sessions | Yes | Yes |
| Unit tests for entity | Yes | Yes |
| Feature tests for all 9 endpoints | Yes | Yes |
| PHPStan passes | Yes | Yes |
| No queries in loops | Yes | Yes |
| IDs are Value Objects | Yes | Yes |
| Requests use getDto() only | Yes | Yes |

---

## Red Flags

- [x] None blocking implementation
- [ ] (Low) coach_id nullable in seeder — null coach bypasses conflict check (business rule should document this explicitly; resolved in BR-02 scope)
- [ ] (Low) Hard delete chosen over soft delete — risk documented; reversible in epic-booking if soft delete added then

---

## Open Questions for Stakeholder

The requirements document already lists 4 open questions. Validation assessment per question:

| # | Question | Blocking? | Recommendation |
|---|----------|-----------|----------------|
| OQ-1 | coach_id null in seeder? | No | Use nullable; admin assigns post-deploy. Design proceeds with nullable FK. |
| OQ-2 | Soft delete vs hard delete? | No | **Recommend soft delete** (`deleted_at`) from the start. Cost is minimal; prevents future breaking change. Design agent should apply this unless explicitly told otherwise. |
| OQ-3 | Coach display name on public schedule? | No | Show `email` field from User for now. No schema changes needed. |
| OQ-4 | Cancelled sessions on public schedule? | No | Yes — include cancelled with `status: cancelled` so members are informed. Already recommended in document. |

---

## Checklist Summary

| Section | Passed |
|---------|--------|
| Business Alignment | 4/4 |
| Content Completeness | 5/5 |
| Use Case Coverage | 5/5 |
| Entity States | 7/8 (soft delete strategy TBD by design agent) |
| Collateral Impact | 8/8 |
| Slicing | 6/6 |
| Time Constraints | 3/3 |
| Testing | 6/7 (unit test scenarios not exhaustively listed — non-blocking) |
| Definition of Done | 15/15 |

**Overall: 59/61 — Valid. Proceed to design.**

---

## Recommendations

1. **Use soft delete** (`deleted_at`) for ClassSession from day one — the cost is negligible and it prevents a breaking change when epic-booking lands. Design agent should implement this unless the user explicitly chooses hard delete.
2. **Document `coach_id = null` as valid** in the business rules — a ClassSession without a coach is valid; the conflict check skips null values.
3. **API contracts** should be defined in `/docs/api-contracts/class-sessions/` as part of the design step (before implementation), per critical rules.
4. **Migration order must be enforced** — `class_sessions` migration must run after `class_types` and `users`. Use timestamp prefix ordering.
5. **Public route** (`/api/schedule`) must be placed outside the `auth:sanctum` middleware group in `routes/api.php`.
6. **Friday dual-session uniqueness** — DB unique constraint should be on `(day_of_week, time_slot, class_type_id)`, not on `(day_of_week, time_slot)` alone. This is already in BR-07 — confirm design agent enforces this at the migration level.
