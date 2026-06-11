# AI Agents

This folder contains agent definitions for AI-assisted development workflow.

## Overview

Agents are specialized AI assistants designed for specific tasks in the development process. Each agent has a focused responsibility and follows defined patterns to ensure consistency and quality.

## Pre-requisite: Roadmap

Before starting the agent pipeline, create/update the project roadmap:

```
/docs/working_docs/roadmap.md
```

The roadmap defines implementation order, dependencies between features, and tracks progress. See `/docs/development/development_process.md` for details.

## Agent Pipeline

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           REQUIREMENTS PHASE                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐     │
│  │ 1. Requirement   │───▶│ 2. Requirement   │───▶│ 2.5 Requirement  │     │
│  │    Writer        │    │    Validator     │    │    Slicing       │     │
│  └──────────────────┘    └──────────────────┘    └──────────────────┘     │
│         │                                                │                 │
│         ▼                                                ▼                 │
│  Create requirement              Validate          (OPTIONAL)              │
│  document                        completeness      Split large reqs        │
│                                                          │                 │
│                                                          ▼                 │
│                                              ┌──────────────────┐          │
│                                              │ 3. Requirement   │          │
│                                              │    Design        │          │
│                                              └──────────────────┘          │
│                                                          │                 │
│                                                          ▼                 │
│                                              ┌──────────────────┐          │
│                                              │ 4. Requirement   │          │
│                                              │    Make Tasks    │          │
│                                              └──────────────────┘          │
│                                                          │                 │
│                                                          ▼                 │
│                                               Create implementation        │
│                                               tasks                        │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                           IMPLEMENTATION PHASE                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  Developer implements tasks following /ai_docs/architecture/ guides         │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                            VALIDATION PHASE                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐     │
│  │ 5. Check         │    │ 6. Check         │    │ 7. Check Code    │     │
│  │    DoD           │    │    Architecture  │    │    Quality       │     │
│  └──────────────────┘    └──────────────────┘    └──────────────────┘     │
│                                                                             │
│  ┌──────────────────┐    ┌──────────────────┐    ┌──────────────────┐     │
│  │ 8. Check         │    │ 9. Linter &      │    │ 10. Testing      │     │
│  │    Performance   │    │    Compile       │    │                  │     │
│  └──────────────────┘    └──────────────────┘    └──────────────────┘     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Agent List

### Requirements Phase

| # | Agent | Purpose | Input | Output |
|---|-------|---------|-------|--------|
| 1 | [Requirement Writer](1.%20requirement_writer.md) | Create requirement documents | Business need | Requirement doc |
| 2 | [Requirement Validator](2.%20requirement_validator.md) | Validate requirement completeness | Requirement doc | Validation report |
| 2.5 | [Requirement Slicing](2.5%20requirement_slicing.md) | Split large requirements (OPTIONAL) | Validated large requirement | Slice plan + Feature docs |
| 3 | [Requirement Design](3.%20requirement_design_solution.md) | Design technical solution | Validated requirement | Solution design |
| 4 | [Requirement Tasks](4.%20requirement_make_tasks.md) | Create implementation tasks | Solution design | Task list |

### Validation Phase

| # | Agent | Purpose | Input | Output |
|---|-------|---------|-------|--------|
| 5 | [Check DoD](5.%20check_definition_of_done.md) | Verify Definition of Done | Implementation | DoD report |
| 6 | [Check Architecture](6.%20check_architecture.md) | Verify architecture compliance | Code changes | Architecture report |
| 7 | [Check Code Quality](7.%20check_code_quality.md) | Review code quality | Code changes | Quality report |
| 8 | [Check Performance](8.%20check_performance.md) | Identify performance issues | Code changes | Performance report |
| 9 | [Linter & Compile](9.%20linter_compile.md) | Run static analysis | Code | Linter report |
| 10 | [Testing](10.%20testing.md) | Run and analyze tests | Code | Test report |

## Workflow

### 1. New Feature Development

```
1. Requirement Writer    → Create requirement
2. Requirement Validator → Validate (fix issues if any)
2.5 Requirement Slicing  → Split if too large (OPTIONAL)
3. Requirement Design    → Design solution
4. Requirement Tasks     → Create tasks
5. [IMPLEMENT]          → Developer codes
6. Linter & Compile     → Static analysis
7. Testing              → Run tests
8. Check Architecture   → Verify architecture
9. Check Code Quality   → Review quality
10. Check Performance   → Check performance
11. Check DoD           → Final verification
```

### 2. Bug Fix / Hotfix

```
1. Document problem (manual)            → Create requirements.md
2. Requirement Validator (hotfix mode)  → Validate problem definition
3. [IMPLEMENT]                          → Developer fixes
4. Linter & Compile                     → Static analysis
5. Testing                              → Run tests
6. Check DoD                            → Verify fix
```

### 3. Code Review

```
1. Check Architecture   → Architecture compliance
2. Check Code Quality   → SOLID, naming, etc.
3. Check Performance    → Performance issues
4. Linter & Compile     → Static analysis
5. Testing              → Test coverage
```

## Agent Output Locations

All documents for one item are in ONE folder:

| Agent | Output Location |
|-------|-----------------|
| Requirement Writer | `/docs/working_docs/[type]/[name]/requirements.md` |
| Requirement Validator | `/docs/working_docs/[type]/[name]/validation.md` |
| Requirement Slicing | `/docs/working_docs/[type]/[name]/slicing.md` + feature docs |
| Requirement Design | `/docs/working_docs/[type]/[name]/design.md` |
| Requirement Tasks | `/docs/working_docs/[type]/[name]/tasks.md` |
| Check * Agents | Report in conversation or `/docs/reviews/` |

**Example:** All content for "user-registration" feature:
```
/docs/working_docs/features/user-registration/
├── requirements.md
├── validation.md
├── design.md
└── tasks.md
```

## Key Principles

### 1. Analysis Informs, Never Blocks

Agents identify risks and issues but **never block** development. The user always decides whether to proceed.

```
✅ "X is missing/risky. Proceed anyway or define first?"
❌ "Cannot proceed until X is defined"
```

### 2. Value Over Code

The goal is to add business value, not to add code. Every requirement should tie to business objectives.

### 3. Architecture Compliance

All code must follow the project's DDD, Hexagonal, and CQRS architecture as defined in `/ai_docs/architecture/`.

### 4. Quality Gates

Validation agents ensure code meets quality standards before deployment.

## Related Documentation

- `/ai_docs/architecture/` - Architecture guides
- `/ai_docs/development_process/` - Development process docs
- `/docs/business/overview.md` - Business context (MANDATORY)

## Tips for Using Agents

1. **Follow the order** - Requirements phase before implementation
2. **Don't skip validation** - Run all check agents before merge
3. **Iterate when needed** - If validation fails, fix and re-run
4. **Use appropriate agent** - Match agent to task type
5. **Provide context** - Give agents access to relevant documents
6. **Consider slicing** - If Agent 2 flags requirement as "too large", use Agent 2.5 before design
