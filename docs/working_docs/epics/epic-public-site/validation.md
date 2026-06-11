# Requirement Validation Report

**Document:** `docs/working_docs/epics/epic-public-site/requirements.md`
**Type:** Epic (full validation)
**Date:** 2026-06-11
**Validator:** Requirement Validator Agent
**Status:** Valid — Ready to Design

---

## Summary

The requirements document is well-structured, complete, and implementable. It correctly identifies this epic as a read-only, frontend-only feature with no new backend entities. The problem statement is clear, the user stories are specific and testable, the API contract is well-defined, and collateral impact has been correctly mapped (particularly the router change at `/`).

Four open questions exist — none of them block design or implementation. They are content/asset questions that can be resolved during or before final QA. The requirement proceeds to design.

---

## Business Alignment Assessment

**Primary Objective:** Member Acquisition / Digital Presence
**Contribution:** Clear — reduces friction in the discovery-to-signup funnel
**KPIs Defined:** Qualitative (self-service info availability) — acceptable for an infrastructure/presence epic with no measurable conversion baseline
**Justification Type:** Objective with context — specific problem described (no public page, reliance on DMs/calls)

### Justification Quality

| Criteria | Status | Notes |
|----------|--------|-------|
| Specific numbers | Partial | No conversion data (expected — gym has no prior web presence to benchmark against) |
| Evidence sources | Yes | Instagram, DM/call reliance, missing `/` route confirmed by codebase inspection |
| Revenue impact | Implicit | Acquisition funnel improvement — quantifiable only after launch |
| Customer names/tickets | N/A | B2C gym, not enterprise; evidence is structural (missing digital presence) |

**Assessment:** Justification is solid for a local gym with no prior digital baseline. KPI gap is acceptable — there is no existing traffic data to benchmark against. The objective (reduce info-discovery friction → increase member acquisition) is self-evident.

**RED FLAGS:** None.

---

## Entities Identified

| Entity | Type | CRUD Coverage | States Defined | Delete Strategy |
|--------|------|---------------|----------------|-----------------|
| Schedule (read from backend) | External/consumed | Read only — correct for this epic | N/A (read-only, no state managed by this epic) | N/A |
| ClassType (embedded in schedule) | External/consumed | Read only | N/A | N/A |
| MembershipPlan (static frontend) | Frontend constant | Read only — correct for MVP | N/A (static data) | N/A |

**Assessment:** No new entities are introduced. This is correct — the epic is purely presentational. Entity analysis confirms there are no missing CRUD operations to define.

---

## Missing Use Cases

| Use Case | Reason | Priority | Question for Stakeholder |
|----------|--------|----------|--------------------------|
| Mobile menu (hamburger nav) | On mobile, a horizontal header nav does not fit — a mobile menu is needed | Must Have | Confirmed implicitly by responsive requirement — design should include it |
| Footer with secondary nav | Common pattern for public sites — contact, social links, copyright | Should Have | Not blocking; can be part of ContactSection or a separate Footer component |
| Scroll-to-top button | Nice-to-have for long single-page sites | Could Have | Out of scope for MVP — no action needed |

**Assessment:** The mobile menu is a gap that the design agent must address. It is not a business logic gap — it is a UX implementation detail. No stakeholder question needed; it follows from the responsive requirement. The other items are low-priority and non-blocking.

**Gap resolved inline:** The design document must include a `MobileMenu` or collapsible nav for viewports < 768px.

---

## Missing State Information

No state machines apply to this epic. All entities are read-only or static. The only "state" is the schedule data loading state, which is already covered (loading spinner → data or fallback mock).

**Assessment:** Complete. No missing state information.

---

## Collateral Impact Assessment

| Component | Type | Impact | Action Required | Assessed |
|-----------|------|--------|-----------------|----------|
| `router/index.tsx` | Behavioral | `/` route missing — must be added before catch-all | Add `<Route path="/" element={<PublicHomePage />} />` | Correct |
| `DefaultRedirect` (catch-all) | Behavioral | Will not affect `/` once explicit route is added | No action needed | Correct |
| `api.ts` 401 interceptor | None | Public schedule call will never return 401 | No action needed | Correct |
| `AuthContext` | None | Public page does not need auth context | No action needed | Correct |
| Backend `GET /api/schedule` | New dependency | Must be exposed as public endpoint (epic-classes) | Use mock until available | Correct |
| `LoginPage` | None | No changes | None | Correct |

**Additional collateral impact identified (not in requirements — minor, resolved here):**

| Component | Type | Impact | Resolution |
|-----------|------|--------|------------|
| `main.tsx` / app entry point | None | `AppRouter` wraps `AuthProvider` — public page renders inside this tree. `AuthProvider` fires `GET /auth/me` on mount regardless. On the public page, this call will fail (no token) and correctly set `user = null`. | No action needed — existing behavior is correct |
| Browser back button from `/login` → `/` | Behavioral | If visitor navigates to `/login` and presses back, they return to `/`. This is correct behavior. | No action needed |

**Assessment:** Collateral impact is complete and correctly identified. No breaking changes.

---

## Slicing Assessment

**Size:** Small–Medium (0 new entities, 8 user stories, 3 use cases, 1 API dependency)
**Slicing needed:** No — the scope is already appropriately sized for a single sprint
**Phases:** None needed — all sections can be developed and delivered together

### Out of Scope Dependencies

| Out of Scope Item | Depends on Current? | Current Depends on It? | Info Needed Now |
|-------------------|---------------------|------------------------|-----------------|
| Contact form (email) | No | No | None |
| Weekend schedule | No | No | None — slots array can grow without schema change |
| SEO / meta tags | No | No | None |
| Member self-registration | No | No | None |
| Configurable plans via API | No | No | Plan data hardcoded correctly for MVP |

**Red Flags:** None. Out-of-scope items are genuinely independent.

**Slicing Red Flag Check:**
- CRUD completeness: N/A (read-only epic)
- Error handling: Defined (mock fallback for schedule)
- Validation: N/A (no forms)
- Essential states: Defined (loading / loaded / error for schedule)

---

## Time Constraints Assessment

**Deadline:** None
**Type:** None
**Reason:** No business event tied to launch date
**Realistic:** Yes — scope is well-defined and bounded
**Calendar conflicts:** None
**Buffer included:** N/A

### Deadline Risk Analysis

No deadline — no risk analysis needed.

---

## Testing Assessment

**Tests defined:** Yes (in Definition of Done)

| Test Type | Required | Defined | Gap |
|-----------|----------|---------|-----|
| Unit (hook) | Yes | Yes — `usePublicSchedule` success + error cases | None |
| Component tests | Yes | Yes — all section components | None |
| Integration (schedule API) | Yes | Implicitly covered — mock fallback tests the contract | Minor: no explicit contract test mentioned |
| E2E | Should Have | Not explicitly defined | Low priority for MVP — manual smoke test defined instead |
| UAT | Yes | Manual smoke test on mobile + desktop | Sufficient for MVP |

**Critical scenarios identified:** Yes
- Route `/` accessible without auth
- Schedule loads from API or falls back to mock
- Header login link works
- Responsive layout

**Test data requirements:** Minimal — schedule mock data is defined in the API contract section.

**Gap:** No explicit backend contract test for `GET /api/schedule`. This is acceptable for MVP — the contract is defined in the requirements document and can be formalized when the endpoint is implemented in epic-classes.

---

## Definition of Done Assessment

**DoD defined:** Yes — comprehensive

| Criteria | Defined | Clear |
|----------|---------|-------|
| Acceptance criteria | Yes | Yes — per user story |
| Quality gates | Yes | Code review, component tests, hook tests |
| Sign-off process | Partial | Manual smoke test defined; explicit stakeholder sign-off not mentioned — acceptable for internal MVP |
| Training needs | N/A | Public page requires no user training |

**Assessment:** DoD is solid and testable. All items are verifiable.

---

## Open Questions for Stakeholder

The following 4 open questions from the requirements are noted. **None block design or implementation.** They are content/asset decisions:

1. **Phone number** — `+34 91 234 5678` is a placeholder. Confirm real number before launch. Implementors should use the placeholder and mark it with a `TODO` comment.
2. **Hero background** — No image/video asset provided. **Decision made (no stakeholder input needed for implementation):** Use CSS gradient (`from-slate-950 to-slate-900`) for MVP. A real photo can replace it post-launch.
3. **CTA destination** — Hero "Unete" button destination. **Recommended and proceeding:** Scroll to `#planes` section. This is the most useful destination for a prospective member who has not yet seen pricing.
4. **Google Maps embed URL** — Use the address literal in an iframe embed URL for MVP. Exact coordinates can be refined before launch.

**Stakeholder decisions needed before launch (not before implementation):** #1 (phone) and #4 (Maps URL precision).

---

## Checklist Summary

| Category | Result |
|----------|--------|
| Business Alignment | Passed (4/4 applicable criteria) |
| Content Completeness | Passed (5/5) |
| Use Case Coverage | Passed — 1 minor gap (mobile menu) resolved by design |
| Entity States | Passed — N/A (read-only epic) |
| Collateral Impact | Passed (6/6 components analyzed) |
| Slicing | Passed — no slicing needed, out-of-scope items are independent |
| Time Constraints | Passed — no deadline, no risk |
| Testing | Passed (minor gap: no backend contract test — acceptable for MVP) |
| Definition of Done | Passed (15/15 criteria defined) |

**Overall: 9/9 categories passed. Requirement is valid and ready for technical design.**

---

## Recommendations

1. **Design must include mobile navigation** — The responsive requirement implies a hamburger/collapsible menu for viewports < 768px. This is a design-time decision, not a requirement gap.

2. **Mark placeholder phone number** — In the `ContactSection` component, add a `// TODO: replace with real phone number` comment until confirmed by Jose David.

3. **Hero CTA destination resolved** — Proceed with "Unete" scrolling to `#planes`. No stakeholder input needed.

4. **Backend contract for `GET /api/schedule`** — When epic-classes implements this endpoint, a backend contract test should be added to ensure the response shape matches the contract defined here. This is a reminder for epic-classes, not a blocker for epic-public-site.

5. **`PublicLayout` should not wrap `AuthProvider`** — The public page renders inside the existing `AppRouter` which already wraps `AuthProvider`. The `PublicLayout` component must NOT re-wrap auth context. Design should note this.

---

**Validation complete. Proceeding to Agent 3 — Requirement Design.**
