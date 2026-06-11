# Development Process Guide

Complete guide for the development workflow, covering different scenarios from new epics to bug fixes.

## Overview

This project uses AI-assisted development with specialized agents for each phase. The process varies depending on the type of work.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              WORK TYPES                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   📋 EPIC          📦 FEATURE        🐛 HOTFIX         🔍 CASE             │
│   New major        Feature in        Bug fix           Investigation        │
│   initiative       existing epic     (urgent)          (support)            │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Quick Reference

| Work Type | Path | Full Process | Urgency |
|-----------|------|--------------|---------|
| Roadmap | `/docs/working_docs/roadmap.md` | Planning + tracking | **MANDATORY** |
| Epic | `/docs/working_docs/epics/[epic-name]/` | Full (all agents) | Normal |
| Feature | `/docs/working_docs/features/[feature-name]/` | Simplified | Normal |
| Hotfix | `/docs/working_docs/hotfixes/[hotfix-name]/` | Problem-focused | High |
| Case | `/docs/working_docs/cases/[case-id]/` | Investigation only | Varies |

---

## 0. Roadmap & Progress Tracking (MANDATORY)

**Use when:** Any project with multiple features to implement - new or existing.

### Two Key Documents for Tracking

| Document | Location | Purpose |
|----------|----------|---------|
| **roadmap.md** | `/docs/working_docs/roadmap.md` | High-level epic progress, dependencies, phases |
| **slicing.md** | `/docs/working_docs/epics/[epic-name]/slicing.md` | Feature-level progress within an epic |

### roadmap.md - Epic-Level Tracking

The roadmap is essential for:
- **Detecting dependencies** between features before starting
- **Visualizing progress** at a glance
- **Planning order** of implementation
- **Avoiding blockers** by implementing dependencies first

Create a roadmap document at `/docs/working_docs/roadmap.md` that defines:

1. **Implementation order** - Which epics/features to build first
2. **Dependencies** - Which features depend on others
3. **Phases/Milestones** - Logical groupings of work
4. **Priority rationale** - Why this order makes sense

### slicing.md - Feature-Level Tracking

When an epic is sliced (divided into features), create a `slicing.md` file in the epic folder:

```
/docs/working_docs/epics/[epic-name]/slicing.md
```

This document tracks:
- **Feature breakdown** - What features make up the epic
- **Feature dependencies** - Which features depend on others
- **Feature status** - Validated / Designed / Tasks / Implemented / Done
- **User story assignment** - Which stories belong to which feature

### Progress Tracking Workflow

```
1. Create epic requirement
2. Validate requirement (Agent 2)
3. IF epic is large → Slice it (Agent 2.5) → Create slicing.md
4. Add epic/features to roadmap.md
5. As you work:
   - Update slicing.md with feature progress (per feature)
   - Update roadmap.md when epic status changes
6. When deployed:
   - Mark ✅ in both documents
   - Add notes about what was implemented
```

### Status Symbols

| Symbol | Meaning |
|--------|---------|
| ⬜ | Not started |
| 🚧 | In progress |
| ✅ | Completed |
| ❌ | Blocked |

### Roadmap Structure

```markdown
# Project Roadmap

**Last Updated:** [date]
**Current Phase:** [Phase N]

## Phase 1: Foundation
| Status | Order | Epic/Feature | Branch | Dependencies | Rationale |
|--------|-------|--------------|--------|--------------|-----------|
| [x] | 1.1 | User Authentication | `feature/user-auth` | None | Required for all features |
| [x] | 1.2 | Core Entity X | `feature/core-entity-x` | 1.1 | Base entity for the domain |

## Phase 2: Core Features
| Status | Order | Epic/Feature | Branch | Dependencies | Rationale |
|--------|-------|--------------|--------|--------------|-----------|
| [~] | 2.1 | Feature A | `feature/feature-a` | 1.1, 1.2 | Main user flow |
| [ ] | 2.2 | Feature B | `feature/feature-b` | 2.1 | Extends Feature A |

## Phase 3: Advanced Features
| Status | Order | Epic/Feature | Branch | Dependencies | Rationale |
|--------|-------|--------------|--------|--------------|-----------|
| [ ] | 3.1 | Feature C | `feature/feature-c` | 2.1 | Advanced functionality |
```

**Status Legend:**
- `[ ]` - Not started
- `[~]` - In progress
- `[x]` - Completed

### When to Update Roadmap
- Mark `[~]` when starting an epic/feature
- Mark `[x]` when deployed to production
- When priorities change
- When new requirements emerge
- After slicing large epics (Agent 2.5) - add slices to roadmap

### Git Branch Naming Convention

When developing features, use **git branches with the same name as the feature folder**:

```
Feature folder:  /docs/working_docs/features/user-registration/
Git branch:      feature/user-registration
```

This makes it easy to:
- Know what's being worked on by looking at branches
- Link code changes to requirements
- Track progress across the team

**Branch naming pattern:**
| Work Type | Branch Pattern | Example |
|-----------|----------------|---------|
| Epic | `epic/[epic-name]` | `epic/payments-system` |
| Feature | `feature/[feature-name]` | `feature/user-registration` |
| Hotfix | `hotfix/[hotfix-name]` | `hotfix/login-crash` |

---

## 1. New Epic Workflow

Use when: Starting a completely new initiative or major feature set.

### Process Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           NEW EPIC WORKFLOW                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  REQUIREMENTS PHASE                                                         │
│  ─────────────────                                                          │
│                                                                             │
│  ┌─────────────┐      ┌─────────────┐      ┌─────────────┐                  │
│  │ PO writes   │─────▶│ Agent 1     │─────▶│ Agent 2     │                  │
│  │ draft       │  OR  │ writes      │      │ validates   │                  │
│  │ (optional)  │      │ requirement │      │             │                  │
│  └─────────────┘      └─────────────┘      └─────────────┘                  │
│         │                    │                    │                         │
│         └────────────────────┼────────────────────┘                         │
│                              ▼                                              │
│                    Validated Requirement                                    │
│                              │                                              │
│  SLICING PHASE (OPTIONAL)    │                                              │
│  ────────────────────────    ▼                                              │
│                    ┌─────────────┐                                          │
│                    │ Agent 2.5   │  ← Only if requirement is too large      │
│                    │ slices      │                                          │
│                    │ requirement │                                          │
│                    └─────────────┘                                          │
│                              │                                              │
│  DESIGN PHASE                │                                              │
│  ────────────                ▼                                              │
│                    ┌─────────────┐      ┌─────────────┐                     │
│                    │ Agent 3     │─────▶│ Agent 4     │                     │
│                    │ designs     │      │ creates     │                     │
│                    │ solution    │      │ tasks       │                     │
│                    └─────────────┘      └─────────────┘                     │
│                                                │                            │
│  IMPLEMENTATION PHASE                          ▼                            │
│  ────────────────────                   Task List                           │
│                                                │                            │
│                    ┌─────────────────────────────────────────┐              │
│                    │          Developer implements           │              │
│                    │     (following architecture guides)     │              │
│                    └─────────────────────────────────────────┘              │
│                                                │                            │
│  VALIDATION PHASE                              ▼                            │
│  ────────────────                                                           │
│                    ┌─────────────┐      ┌─────────────┐                     │
│                    │ Agent 9     │─────▶│ Agent 10    │                     │
│                    │ Linter      │      │ Testing     │                     │
│                    └─────────────┘      └─────────────┘                     │
│                           │                    │                            │
│                           ▼                    ▼                            │
│                    ┌─────────────┐      ┌─────────────┐                     │
│                    │ Agent 6     │      │ Agent 7     │                     │
│                    │ Arch check  │      │ Quality     │                     │
│                    └─────────────┘      └─────────────┘                     │
│                           │                    │                            │
│                           ▼                    ▼                            │
│                    ┌─────────────┐      ┌─────────────┐                     │
│                    │ Agent 8     │─────▶│ Agent 5     │                     │
│                    │ Performance │      │ DoD Check   │                     │
│                    └─────────────┘      └─────────────┘                     │
│                                                │                            │
│  DEPLOY                                        ▼                            │
│  ──────                              Deploy to Production                   │
│                                                │                            │
│  POST-PRODUCTION                               ▼                            │
│  ───────────────                                                            │
│                    ┌─────────────────────────────────────────┐              │
│                    │       Update Documentation              │              │
│                    │  (User Manuals, Marketing, Support)     │              │
│                    └─────────────────────────────────────────┘              │
│                                                │                            │
│                                                ▼                            │
│                                         ✅ DONE                             │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Step by Step

#### Step 1: Create Requirement

**Option A: Product Owner writes draft**
1. PO creates folder: `/docs/working_docs/epics/[epic-name]/`
2. PO creates `requirements.md` with initial requirement (can be incomplete)
3. Use **Agent 1** to complete/improve the draft

**Option B: AI writes from scratch**
1. Describe the business need to **Agent 1**
2. Agent creates the folder and `requirements.md`
3. PO reviews and adjusts

#### Step 2: Validate Requirement

1. Run **Agent 2: Requirement Validator**
2. Review validation report
3. Fix any gaps or issues identified
4. Re-run until validation passes

**Key validations for Epics:**
- Business alignment (Revenue/Churn/Sales)
- KPIs with measurable targets
- Evidence (customer names, ticket IDs, data)
- All entities and states defined
- Collateral impact analyzed
- Time constraints clear

#### Step 2.5: Slice Requirement (OPTIONAL)

**Use only when** Agent 2 flags the requirement as "too large" or "slicing needed".

1. Run **Agent 2.5: Requirement Slicing**
2. Review slicing strategy and proposed slices
3. Approve or adjust slice boundaries
4. Agent creates individual feature documents for each slice

**When to slice:**
- 4+ entities involved
- 10+ use cases
- Complex state machines
- Multiple independent user journeys
- Different parts have different priorities

**After slicing:**
- Each slice becomes a separate feature document
- Proceed with Agent 3 (Design) for each slice independently
- Slices can be implemented in parallel or sequentially

#### Step 3: Design Solution

1. Run **Agent 3: Requirement Design**
2. Agent analyzes codebase and designs solution
3. Review architecture decisions
4. Verify alignment with existing patterns

**Output:** Solution design document with:
- Architecture decisions
- Implementation plan per layer
- Database schema
- Collateral changes

#### Step 4: Create Tasks

1. Run **Agent 4: Requirement Make Tasks**
2. Review task breakdown
3. Adjust estimates if needed
4. Prioritize tasks

**Output:** Task list following development phases:
- Phase 1: Domain
- Phase 2: Infrastructure
- Phase 3: Application + HTTP
- Phase 4: Tests

#### Step 5: Implementation

Developer implements following:
- `/ai_docs/architecture/development-workflow.md`
- `/ai_docs/architecture/critical-rules.md`
- Task list from Step 4

#### Step 6: Validation

Run validation agents (can run in parallel):

| Agent | Purpose | When to Run |
|-------|---------|-------------|
| Agent 9 | Linter & Compile | After code changes |
| Agent 10 | Testing | After code changes |
| Agent 6 | Architecture | After implementation |
| Agent 7 | Code Quality | After implementation |
| Agent 8 | Performance | For DB-heavy features |
| Agent 5 | Definition of Done | Before merge |

#### Step 7: Deploy

1. All validations pass
2. Code review approved
3. Merge to main branch
4. Deploy to staging
5. UAT sign-off
6. Deploy to production
7. Monitor for issues

#### Step 8: Post-Production (Documentation Updates)

**MANDATORY** after production deployment:

| Documentation | Update If | Location |
|---------------|-----------|----------|
| User Manuals | UI changed, new features visible to users | `/docs/users_manuals/` |
| Marketing | New feature to promote, messaging needs update | `/docs/marketing/` |
| Support | New troubleshooting scenarios, FAQ updates | `/docs/support/` |
| API Docs | API endpoints changed | `/docs/development/` |

**Checklist:**
- [ ] Review if user-facing changes require manual updates
- [ ] Update screenshots if UI changed
- [ ] Notify marketing of new features for announcements
- [ ] Update support FAQ if new common questions expected
- [ ] Close working_docs item (mark as DONE in requirements.md)

---

## 2. Feature in Existing Epic Workflow

Use when: Adding a feature that belongs to an already defined epic.

### Process Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         FEATURE WORKFLOW (SIMPLIFIED)                       │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────┐      ┌─────────────┐      ┌─────────────┐                  │
│  │ Reference   │─────▶│ Agent 1/2   │─────▶│ Agent 3     │                  │
│  │ parent epic │      │ (simplified)│      │ designs     │                  │
│  └─────────────┘      └─────────────┘      └─────────────┘                  │
│         │                                         │                         │
│         │    ┌────────────────────────────────────┘                         │
│         │    │                                                              │
│         ▼    ▼                                                              │
│  ┌─────────────┐      ┌─────────────┐      ┌─────────────┐                  │
│  │ Agent 4     │─────▶│ Implement   │─────▶│ Validate    │                  │
│  │ tasks       │      │             │      │ (5-10)      │                  │
│  └─────────────┘      └─────────────┘      └─────────────┘                  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Step by Step

#### Step 1: Reference Parent Epic

1. Identify the parent epic in `/docs/working_docs/epics/`
2. Create feature folder: `/docs/working_docs/features/[feature-name]/`
3. Create `requirements.md` with **MANDATORY** epic reference:

```markdown
# Feature: [Name]

**Parent Epic:** [../epics/epic-name/requirements.md](link)
**Epic Scope:** [Which part of the epic this covers]
```

#### Step 2: Write/Complete Feature Requirement

**Option A: PO writes**
1. PO writes feature-specific details
2. Reference epic for business alignment, KPIs (don't repeat)

**Option B: Agent 1 completes**
1. Give Agent 1 the parent epic context
2. Agent writes feature-specific requirements

#### Step 3: Simplified Validation (Agent 2)

Agent 2 in Feature mode checks:
- [ ] Parent epic reference exists
- [ ] Feature scope aligns with epic
- [ ] Feature-specific acceptance criteria
- [ ] No business alignment needed (reference epic)
- [ ] No KPIs needed (reference epic)

#### Step 4: Design & Tasks (Agents 3 & 4)

Same as Epic workflow but scoped to feature.

#### Step 5: Implementation & Validation

Same as Epic workflow.

#### Step 6: Post-Production

Same as Epic workflow - update documentation if feature affects:
- User interface → Update user manuals
- Public features → Notify marketing
- Support scenarios → Update support docs

---

## 3. Hotfix Workflow

Use when: Urgent bug fix needed in production.

### Process Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         HOTFIX WORKFLOW (URGENT)                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────┐      ┌─────────────┐      ┌─────────────┐                  │
│  │ Document    │─────▶│ Agent 2     │─────▶│ Implement   │                  │
│  │ problem     │      │ (hotfix)    │      │ fix         │                  │
│  └─────────────┘      └─────────────┘      └─────────────┘                  │
│                                                   │                         │
│                              ┌────────────────────┘                         │
│                              ▼                                              │
│                    ┌─────────────┐      ┌─────────────┐                     │
│                    │ Test fix    │─────▶│ Deploy      │                     │
│                    │ (Agent 10)  │      │             │                     │
│                    └─────────────┘      └─────────────┘                     │
│                                                                             │
│  ⚠️  Skip: Agent 1, Agent 3, Agent 4 (no full design needed)                │
│  ⚠️  Minimal: Agents 6, 7, 8 (quick review only)                            │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Step by Step

#### Step 1: Document Problem

Create folder `/docs/working_docs/hotfixes/[hotfix-name]/` and create `requirements.md`:

```markdown
# Hotfix: [Name]

**Severity:** Critical | High | Medium
**Reported by:** [who]
**Date:** [date]
**Affected:** [users/systems affected]

## Problem Description
[What is broken]

## Impact
- [Who is affected]
- [Business impact]
- [Number of users/transactions affected]

## Root Cause
[If known, or "Under investigation"]

## Proposed Fix
[Solution approach]

## Testing Plan
[How to verify the fix works]

## Rollback Plan
[How to revert if fix fails]
```

#### Step 2: Validate Hotfix (Agent 2)

Agent 2 in Hotfix mode checks only:
- [ ] Problem clearly described
- [ ] Impact documented
- [ ] Root cause identified (or being investigated)
- [ ] Proposed solution defined
- [ ] Testing plan exists
- [ ] Rollback plan exists

**Skip:** Business alignment, KPIs, use cases, full entity analysis

#### Step 3: Implement Fix

1. Create hotfix branch
2. Implement minimal fix
3. Focus on solving the problem, not refactoring

#### Step 4: Quick Validation

- **Agent 9:** Linter (must pass)
- **Agent 10:** Tests (must pass, add regression test)
- **Agent 5:** Quick DoD check

**Skip or minimal:** Agents 6, 7, 8 (unless fix is complex)

#### Step 5: Deploy

1. Code review (expedited)
2. Deploy to staging
3. Verify fix
4. Deploy to production
5. Monitor

#### Step 6: Post-Mortem (Optional)

For critical hotfixes, create a Case for analysis:
- Why did this happen?
- How to prevent in future?
- Create Feature/Epic if larger fix needed

#### Step 7: Documentation Updates (If Applicable)

For hotfixes that affect user experience:
- [ ] Update support FAQ with workaround/fix info
- [ ] Update troubleshooting guides in `/docs/support/`
- [ ] If behavior changed, update user manuals

---

## 4. Case Analysis Workflow (Support)

Use when: Support needs to investigate an issue before deciding on action.

### Purpose

Cases are for **investigation only**. They do NOT result in direct implementation. After investigation, the outcome is:
- Create a Hotfix (if urgent bug)
- Create a Feature (if enhancement needed)
- Create an Epic (if major work needed)
- Close (if not a bug / won't fix)

### Process Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      CASE WORKFLOW (INVESTIGATION)                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────┐      ┌─────────────┐      ┌─────────────┐                  │
│  │ Support     │─────▶│ Agent 2     │─────▶│ Decision    │                  │
│  │ documents   │      │ (case mode) │      │             │                  │
│  │ case        │      │             │      │             │                  │
│  └─────────────┘      └─────────────┘      └─────────────┘                  │
│                                                   │                         │
│                              ┌────────────────────┼────────────────┐        │
│                              ▼                    ▼                ▼        │
│                    ┌─────────────┐      ┌─────────────┐  ┌─────────────┐    │
│                    │ Create      │      │ Create      │  │ Close       │    │
│                    │ Hotfix      │      │ Feature/    │  │ (not a bug) │    │
│                    │             │      │ Epic        │  │             │    │
│                    └─────────────┘      └─────────────┘  └─────────────┘    │
│                           │                    │                            │
│                           ▼                    ▼                            │
│                    Hotfix Workflow      Epic/Feature Workflow               │
│                                                                             │
│  ⚠️  Cases do NOT go to implementation directly!                            │
│  ⚠️  Agent 3, 4 are NOT used for cases                                      │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Step by Step

#### Step 1: Support Documents Case

Create folder `/docs/working_docs/cases/[case-id]/` and create `report.md`:

```markdown
# Case: [ID] - [Brief Title]

**Status:** Open | Investigating | Root Cause Found | Closed
**Reported by:** [customer/internal]
**Date:** [date]
**Assignee:** [who is investigating]

## Incident Description
[What was reported]

## Timeline
| Date/Time | Event |
|-----------|-------|
| [datetime] | [what happened] |

## Environment
- Application version: [version]
- User type: [type]
- Browser/Device: [if relevant]

## Investigation

### Steps to Reproduce
1. [step]
2. [step]

### Findings
[What was discovered during investigation]

### Logs/Evidence
[Relevant logs, screenshots, data]

## Root Cause Analysis
[Root cause if found, or "Under investigation"]

## Recommendations
| Action | Type | Priority | Justification |
|--------|------|----------|---------------|
| [action] | Hotfix/Feature/Epic/None | High/Med/Low | [why] |

## Resolution
[How the case was resolved - which Hotfix/Feature was created, or why closed]
```

#### Step 2: Agent 2 Reviews Case

Agent 2 in Case mode checks:
- [ ] Incident clearly described
- [ ] Timeline documented
- [ ] Investigation findings present
- [ ] Root cause analysis (or in progress)
- [ ] Recommendations provided

**Agent 2 does NOT:**
- Generate design documents
- Create tasks
- Suggest implementation

#### Step 3: Decision

Based on investigation:

| Finding | Action | Next Step |
|---------|--------|-----------|
| Urgent bug | Create Hotfix | → Hotfix Workflow |
| Non-urgent bug | Create Feature | → Feature Workflow |
| Major issue | Create Epic | → Epic Workflow |
| Not a bug | Close case | Document resolution |
| User error | Close case | Update documentation |
| Won't fix | Close case | Document decision |

#### Step 4: Link and Close

1. Link case to created Hotfix/Feature/Epic
2. Update case status to "Closed"
3. Document resolution

---

## Agent Quick Reference

### When to Use Each Agent

| Agent | Epic | Feature | Hotfix | Case |
|-------|------|---------|--------|------|
| 1. Requirement Writer | Optional | Optional | Skip | Skip |
| 2. Requirement Validator | Full | Simplified | Hotfix mode | Case mode |
| 2.5 Requirement Slicing | If large | Skip | Skip | Skip |
| 3. Requirement Design | Yes | Yes | Skip | Skip |
| 4. Requirement Tasks | Yes | Yes | Skip | Skip |
| 5. Check DoD | Yes | Yes | Quick | Skip |
| 6. Check Architecture | Yes | Yes | Minimal | Skip |
| 7. Check Code Quality | Yes | Yes | Minimal | Skip |
| 8. Check Performance | Yes | Yes | If relevant | Skip |
| 9. Linter & Compile | Yes | Yes | Yes | Skip |
| 10. Testing | Yes | Yes | Yes | Skip |

### Agent 1: Requirement Writer

**Role:** Optional - assists with writing or completing requirements

**Use when:**
- Starting from scratch (AI writes requirement)
- PO wrote draft that needs completion
- Need to formalize verbal requirements

**Skip when:**
- PO wrote complete requirement
- Hotfix (problem statement is enough)
- Case (investigation document)

### Agent 2: Requirement Validator

**Role:** Validates requirements are complete and ready for development

**Modes:**
- **Epic mode:** Full validation (business alignment, KPIs, all entities)
- **Feature mode:** Simplified (check epic reference, feature-specific criteria)
- **Hotfix mode:** Problem-focused (problem, impact, fix, test plan)
- **Case mode:** Investigation (incident, findings, recommendations)

---

## File Structure

**Principle:** All documents for one item are in ONE folder. To see everything about "Feature X", look in `/docs/working_docs/features/feature-x/`.

```
docs/
└── working_docs/
    ├── roadmap.md                   # High-level progress tracking (MANDATORY)
    │
    ├── epics/
    │   ├── _template_slicing.md     # Template for slicing.md files
    │   └── [epic-name]/
    │       ├── requirements.md      # Business case, full requirements
    │       ├── validation.md        # Validation report
    │       ├── slicing.md           # Feature-level progress tracking (if sliced)
    │       ├── design.md            # Architecture design
    │       └── tasks.md             # Implementation tasks
    │
    ├── features/
    │   └── [feature-name]/
    │       ├── requirements.md      # References parent epic
    │       ├── validation.md        # Feature validation
    │       ├── design.md            # Feature design
    │       └── tasks.md             # Feature tasks
    │
    ├── hotfixes/
    │   └── [hotfix-name]/
    │       ├── requirements.md      # Problem + solution
    │       ├── validation.md        # Quick validation
    │       └── tasks.md             # Fix steps (optional)
    │
    └── cases/
        └── [case-id]/
            ├── report.md            # Investigation report
            └── recommendations.md   # Next steps
```

### Key Tracking Files

| File | Level | Update When |
|------|-------|-------------|
| `roadmap.md` | Project | Epic starts, completes, or status changes |
| `slicing.md` | Epic | Feature validation, design, implementation completes |

---

## Best Practices

### 1. Always Reference Parent Epic

Features MUST reference their parent epic. This:
- Avoids duplicating business justification
- Maintains traceability
- Ensures scope alignment

### 2. Don't Skip Validation

Even for small changes, run at least:
- Agent 9 (Linter)
- Agent 10 (Tests)

### 3. Cases Are Not Tickets

Cases are for investigation. Don't implement from a case:
- Investigate → Document findings → Create proper work item

### 4. Hotfixes Are Temporary

Hotfixes solve immediate problems. For complex issues:
- Apply hotfix for urgency
- Create Feature/Epic for proper solution
- Schedule technical debt cleanup

### 5. Iterate on Validation

If validation fails:
1. Fix the issues identified
2. Re-run validation
3. Don't skip to implementation

---

## Process Summary

| Work Type | Write Req | Validate | Slice | Design | Tasks | Implement | Validate Code | Post-Production |
|-----------|-----------|----------|-------|--------|-------|-----------|---------------|-----------------|
| **Epic** | Agent 1 (optional) | Agent 2 (full) | Agent 2.5 (if large) | Agent 3 | Agent 4 | Dev | Agents 5-10 | **MANDATORY** |
| **Feature** | Agent 1 (optional) | Agent 2 (simple) | Skip | Agent 3 | Agent 4 | Dev | Agents 5-10 | If user-facing |
| **Hotfix** | Manual | Agent 2 (hotfix) | Skip | Skip | Skip | Dev | Agents 9, 10 | If applicable |
| **Case** | Support | Agent 2 (case) | Skip | Skip | Skip | Skip | Skip | N/A |

---

## Post-Production: Documentation Updates

**After deploying to production, documentation must be reviewed and updated.**

This is NOT automatic - it requires manual review to determine what needs updating.

### Documentation Update Checklist

#### User Manuals (`/docs/users_manuals/`)

Update when:
- [ ] New features visible to end users
- [ ] UI/UX changes (new buttons, screens, flows)
- [ ] Changed behavior in existing features
- [ ] New error messages users might see

Actions:
- Update relevant guides
- Add/update screenshots
- Update FAQ if needed

#### Marketing (`/docs/marketing/`)

Update when:
- [ ] New feature ready for promotion
- [ ] Feature changes that affect messaging
- [ ] Deprecated features being removed

Actions:
- Update feature descriptions
- Prepare announcement content
- Update product screenshots

#### Support (`/docs/support/`)

Update when:
- [ ] New troubleshooting scenarios
- [ ] Bug fixes that users asked about
- [ ] New error conditions
- [ ] Workarounds no longer needed (after fix)

Actions:
- Update troubleshooting guides
- Update FAQ
- Remove obsolete workarounds
- Add new known issues if any

#### Development (`/docs/development/`)

Update when:
- [ ] API changes (new endpoints, changed parameters)
- [ ] Database schema changes
- [ ] New environment variables
- [ ] Changed setup procedures

Actions:
- Update API documentation
- Update setup guides
- Update environment configuration docs

### Quick Reference: What to Update

| Change Type | User Manuals | Marketing | Support | Development |
|-------------|--------------|-----------|---------|-------------|
| New feature | Yes | Yes | Maybe | If API |
| UI change | Yes | Maybe | Maybe | No |
| Bug fix | If behavior changed | No | Yes | No |
| API change | No | No | No | Yes |
| Performance fix | No | No | Maybe | Maybe |

### Responsibility

| Role | Documentation Responsibility |
|------|------------------------------|
| Product Engineer | User Manuals, Development docs |
| Product Owner | Marketing coordination |
| Support Team | Support docs, FAQ |
| DevOps | Deployment docs |

### Closing the Work Item

After documentation is updated:
1. Mark working_docs item as DONE
2. Link to updated documentation (if significant changes)
3. Notify relevant teams (marketing, support) of changes
