# Epic Slicing: [Epic Name]

**Epic:** [requirement.md](requirement.md)
**Date:** YYYY-MM-DD
**Total Features:** X (including Foundation)

## Slicing Rationale

Brief explanation of why this epic needs to be sliced.

**Why slice:**
- Reason 1
- Reason 2
- Reason 3

## Dependency Graph

```
Feature 0: Foundation (entities, base infrastructure)
    │
    └──► Feature 1: [Name] (User Stories: US-XXX)
              │
              ├──► Feature 2: [Name] (US-XXX)
              │
              ├──► Feature 3: [Name] (US-XXX)
              │
              └──► Feature 4: [Name] (US-XXX)
```

**Note:** Features X, Y, Z can be developed in parallel after Feature 1 is complete.

## Features Summary

| # | Feature | Dependencies | Value Delivered | Complexity |
|---|---------|--------------|-----------------|------------|
| 0 | Foundation | None | Enables other features | M |
| 1 | [Name] | 0 | [Value] | L |
| 2 | [Name] | 1 | [Value] | S |
| 3 | [Name] | 1 | [Value] | M |

## Recommended Order

1. **Feature 0: Foundation** - Must be first
2. **Feature 1: [Name]** - Core functionality
3. **Feature 2: [Name]** - [Reason]
4. **Feature 3: [Name]** - [Reason]

**Parallel opportunity:** After Feature 1, features 2-X can be developed in parallel.

## Entity Ownership

| Entity | Owner Feature | Used By |
|--------|---------------|---------|
| [Entity] | Feature 0 | All features |
| [Entity] | Feature X | Feature X only |

## File Ownership (No Overlap)

| Area | Feature | Files |
|------|---------|-------|
| Domain entities | 0 | `src/[domain]/entities/*` |
| Endpoints group A | 1 | `POST /api/...`, `GET /api/...` |
| Endpoints group B | 2 | `POST /api/...` |

## Slicing Validation

- [ ] No circular dependencies
- [ ] Unidirectional dependency flow
- [ ] Each feature independently deployable
- [ ] Vertical slices (each feature has domain + app + http)
- [ ] Shared foundation identified (Feature 0)
- [ ] No overlapping scope (endpoints clearly assigned)
- [ ] Each feature delivers minimum viable value
- [ ] All epic scope covered (all user stories assigned)

## User Story Assignment

| User Story | Feature | Rationale |
|------------|---------|-----------|
| US-001: [Name] | 1 | [Reason] |
| US-002: [Name] | 1 | [Reason] |
| US-003: [Name] | 2 | [Reason] |

## Risk Notes

1. **Feature 0 is critical path** - All other features blocked until complete
2. **[Risk X]** - [Description and mitigation]
3. **[Risk Y]** - [Description and mitigation]

## Feature Status

| # | Feature | Validated | Designed | Tasks | Implemented | Done |
|---|---------|:---------:|:--------:|:-----:|:-----------:|:----:|
| 0 | Foundation | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ |
| 1 | [Name] | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ |
| 2 | [Name] | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ |
| 3 | [Name] | ⬜ | ⬜ | ⬜ | ⬜ | ⬜ |

**Status:** ⬜ NOT STARTED

## Implemented Endpoints (fill as completed)

```
# Feature 1 endpoints:
- POST /api/v1/... - [Description]
- GET /api/v1/... - [Description]

# Feature 2 endpoints:
- ...
```

---

## How to Update This Document

1. **When starting a feature:** Change its row in Feature Status to show progress (⬜ → 🚧)
2. **When completing a step:** Mark it ✅ in the Feature Status table
3. **When all features done:** Update overall Status to ✅ EPIC COMPLETE (DATE)
4. **After deployment:** Add implemented endpoints to the list
5. **Update roadmap.md** to reflect epic completion
