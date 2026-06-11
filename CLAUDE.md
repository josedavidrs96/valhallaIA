# CLAUDE.md — Valhalla Gym Project

## Project Overview

Web application for **Valhalla Gym** — a local gym in Los Palacios y Villafranca, Sevilla, Spain.
Single-location gym (not SaaS). Replaces manual/third-party processes with a branded platform.

**Business context:** `/docs/business/overview.md` — READ THIS before every agent run.
**Roadmap:** `/docs/working_docs/roadmap.md` — current implementation order and status.
**Agents guide:** `/ai_docs/agents/README.md` — full pipeline documentation.

---

## Language Rules

| Context | Language |
|---------|----------|
| Conversation with user | Spanish |
| Code (variables, functions, classes, files) | English |
| UI text visible in the app | Spanish (avoid the "n with tilde" character — write "n" instead) |
| Comments in code | English |
| Documentation files (.md) | English |
| Git commit messages | English |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.3) |
| Frontend | React 18 + TypeScript + Vite |
| Styling | Tailwind CSS |
| Database | MySQL 8.0 |
| Auth | Laravel Sanctum (SPA auth) |
| Environment | Docker + Docker Compose |
| API style | REST (JSON) — contract-first |

### Brand Colors (use these in Tailwind / CSS)

```
Primary blue:   #2563eb   → tailwind: blue-600
Dark bg:        #0f172a   → tailwind: slate-950
Accent blue:    #60a5fa   → tailwind: blue-400
Text dark:      #1e293b   → tailwind: slate-800
Light bg:       #f8fafc   → tailwind: slate-50
```

---

## Architecture

DDD + Hexagonal Architecture + CQRS. Full guide: `/ai_docs/architecture/architecture.md`.

### Bounded Contexts

```
src/
├── Core/
│   ├── Member/       # Members (registration, profiles, plans)
│   ├── Staff/        # Trainers and admin users
│   ├── Class/        # Class types and weekly schedule
│   └── Booking/      # Class bookings and attendance
├── Billing/
│   └── Payment/      # Cash payment tracking and dues
└── Shared/
    ├── Auth/         # Authentication and authorization
    └── Framework/    # Shared utilities
```

### Key Architecture Rules (non-negotiable)

1. Actions are THIN — max 3 responsibilities: verify access, dispatch, return resource
2. Commands NEVER return values — generate ID before dispatching
3. IDs are Value Objects, not strings — `MemberId`, `ClassId`, etc.
4. Handlers NEVER access DB directly — always via Repository interfaces
5. Requests NEVER use framework validation — only `getDto()` method
6. NEVER execute queries in loops — fetch all, join in code

Full rules: `/ai_docs/architecture/critical-rules.md`

---

## Agent Workflow

This project uses a pipeline of 10 specialized agents. Always follow this order:

```
/requirement-write   → 1. Write requirement document
/requirement-validate → 2. Validate completeness
/requirement-design  → 3. Design technical solution
/requirement-tasks   → 4. Create implementation tasks
[IMPLEMENT]          → 5. Developer codes (Claude Code)
/linter              → 6. Static analysis
/testing             → 7. Run tests
/check-architecture  → 8. Architecture compliance
/check-quality       → 9. Code quality review
/check-performance   → 10. Performance check
/check-dod           → 11. Definition of Done
```

### Simulated Team Roles

| Role | Who |
|------|-----|
| Product Owner | The user (Jose David) |
| Business Analyst | `/requirement-write` + `/requirement-validate` |
| Tech Lead / Architect | `/requirement-design` |
| Developer | Claude Code (me) |
| QA / Tester | `/testing` + `/check-dod` |
| Code Reviewer | `/check-architecture` + `/check-quality` + `/check-performance` |
| DevOps | `/linter` + Docker setup |

---

## Key Commands (Docker)

```bash
# Start environment
docker-compose up -d

# Backend
docker-compose exec app composer install
docker-compose exec app php artisan migrate
docker-compose exec app php artisan test

# Frontend
docker-compose exec node npm install
docker-compose exec node npm run dev
docker-compose exec node npm run build
```

---

## Domain Entities (Reference)

| Entity | Bounded Context | Description |
|--------|----------------|-------------|
| Member | Core/Member | Gym member (socio) |
| MembershipPlan | Core/Member | 2-day / 3-day / 4-5-day plans |
| Staff | Core/Staff | Trainers and admin users |
| ClassType | Core/Class | Type of class (e.g. "Calistenia Tren Superior") |
| ClassSession | Core/Class | Specific class slot (day + time + coach + capacity) |
| Booking | Core/Booking | Member reservation for a ClassSession |
| Payment | Billing/Payment | Cash payment recorded by admin |

---

## Important Notes

- **Single gym**: no multi-tenancy, no SaaS features
- **Cash only**: no payment gateway in MVP
- **UI language**: Spanish, but avoid "n with tilde" — write "n" instead
- **Class slots (Mon-Fri)**: 07:45, 12:15, 16:15, 17:30, 18:45, 20:00, 21:15
- **Weekly schedule**: Mon/Wed = Tren Superior · Tue = Tren Inferior · Thu = Full Body · Fri = GAP + Entrenamiento Libre
- **Class types**: tren-superior, tren-inferior, full-body, gap, entrenamiento-libre
- **Weekend slots**: not defined yet — skip in MVP
- **Logo**: from Instagram @itsvallhallaworkout — do not invent logos
- **No frontend until epic-foundation is complete** — auth and base entities first

---

## File Locations

| Document type | Location |
|--------------|----------|
| Business context | `/docs/business/overview.md` |
| Roadmap | `/docs/working_docs/roadmap.md` |
| Epic requirements | `/docs/working_docs/epics/[name]/requirements.md` |
| API contracts | `/docs/api-contracts/[feature]/` |
| Architecture guides | `/ai_docs/architecture/` |
| Agent definitions | `/ai_docs/agents/` |
