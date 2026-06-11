# Project Roadmap — Valhalla Gym

**Status:** Active
**Last Updated:** 2026-06-11
**Current Phase:** MVP Complete — All phases done

## Project Summary

Web application for Valhalla Gym (Los Palacios y Villafranca, Sevilla). Single-location gym
management: public website + member management + class schedule + payment tracking.

---

## Epic Dependency Graph

```
┌──────────────────────────────────────────────────────────────────────────────────┐
│                           MVP IMPLEMENTATION ORDER                                │
├──────────────────────────────────────────────────────────────────────────────────┤
│                                                                                   │
│  PHASE 1: Foundation                                                              │
│  ────────────────────                                                             │
│                                                                                   │
│  ┌─────────────────────────────────────────────────────────────────────────┐      │
│  │                        epic-foundation                                   │      │
│  │   Auth (3 roles) + Base entities (Member, Staff, ClassType, Plan)       │      │
│  │                        (NO DEPENDENCIES)                                 │      │
│  └─────────────────────────────────────────────────────────────────────────┘      │
│                   │                │               │                              │
│         ┌─────────┘     ┌──────────┘     ┌─────────┘                             │
│         ▼               ▼               ▼                                        │
│                                                                                   │
│  PHASE 2: Core Modules (can be worked in parallel)                               │
│  ─────────────────────────────────────────────────                               │
│                                                                                   │
│  ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐             │
│  │ epic-public-site  │  │ epic-members      │  │ epic-classes      │             │
│  │ Public website    │  │ Member management │  │ Schedule & types  │             │
│  │ (info + schedule) │  │ (register, plans) │  │ (weekly agenda)   │             │
│  └───────────────────┘  └───────────────────┘  └───────────────────┘             │
│                                  │                       │                        │
│                         ┌────────┘             ┌─────────┘                        │
│                         ▼                      ▼                                  │
│  PHASE 3: Value Delivery                                                          │
│  ──────────────────────                                                           │
│                                                                                   │
│  ┌───────────────────────────┐    ┌───────────────────────────┐                  │
│  │   epic-booking            │    │   epic-payments           │                  │
│  │   Class booking system    │    │   Payment tracking        │                  │
│  │   (members book slots)    │    │   (dues + cash control)   │                  │
│  └───────────────────────────┘    └───────────────────────────┘                  │
│                                                                                   │
└──────────────────────────────────────────────────────────────────────────────────┘
```

---

## Epic Details

### Phase 1: Foundation

| Epic | Description | Dependencies | Status |
|------|-------------|--------------|--------|
| [epic-foundation](epics/epic-foundation/requirements.md) | Auth (Admin/Coach/Member/Guest), base entities, DB schema | None | ✅ Done |

**Scope:**
- User authentication (Laravel Sanctum)
- Role-based access control (Admin, Coach, Member)
- Base entities: Member, Staff, MembershipPlan, ClassType
- Database migrations
- Docker environment setup

---

### Phase 2: Core Modules

| Epic | Description | Dependencies | Status |
|------|-------------|--------------|--------|
| [epic-public-site](epics/epic-public-site/requirements.md) | Public info page: about, services, schedule, pricing, contact | epic-foundation | ✅ Done |
| [epic-members](epics/epic-members/requirements.md) | Member CRUD, assign plan, view profile | epic-foundation | ✅ Done |
| [epic-classes](epics/epic-classes/requirements.md) | Class types, weekly schedule (Mon-Fri slots), coach assignment | epic-foundation | ✅ Done |

**epic-public-site features:**
- Landing page (hero, tagline, brand)
- Services / About section
- Public class schedule display
- Membership pricing section
- Contact info + Google Maps embed
- No login required

**epic-members features:**
- Admin: register member, assign plan, view/edit profile
- Member: view own profile and membership status
- Membership plan display (2 days / 3 days / 4-5 days)

**epic-classes features:**
- Admin: create class types (name, description, duration, capacity)
- Admin: create weekly schedule (day + time slot + class type + coach)
- Coach: view assigned classes
- Public: read-only schedule view

---

### Phase 3: Value Delivery

| Epic | Description | Dependencies | Status |
|------|-------------|--------------|--------|
| [epic-booking](epics/epic-booking/requirements.md) | Members book class slots, capacity enforcement, cancellation | epic-members + epic-classes | ✅ Done |
| [epic-payments](epics/epic-payments/requirements.md) | Admin records cash payments, tracks overdue members, monthly dues | epic-members | ✅ Done |

**epic-booking features:**
- Member: view available slots and book a class
- System: enforce capacity limit per session
- Member: cancel booking
- Admin/Coach: view class roster

**epic-payments features:**
- Admin: record payment (member + amount + date + plan)
- Admin: view overdue members (no payment this month)
- Member: view own payment history
- No payment gateway (cash only)

---

## Implementation Progress

```
✅ 1. epic-foundation         ────────────────────────────────────► DONE
   │
   ├─► ✅ 2a. epic-public-site    ──────────────────────────────── DONE
   ├─► ✅ 2b. epic-members        ──────────────────────────────── DONE
   └─► ✅ 2c. epic-classes        ──────────────────────────────── DONE
                │                      │
                ▼                      ▼
   ✅ 3a. epic-booking         ────────────────────────────────────► DONE
   ✅ 3b. epic-payments        ────────────────────────────────────► DONE
```

## Epic Status Summary

| Phase | Epic | Status | Progress |
|:-----:|------|:------:|:--------:|
| 1 | epic-foundation | ✅ Done | 100% |
| 2 | epic-public-site | ✅ Done | 100% |
| 2 | epic-members | ✅ Done | 100% |
| 2 | epic-classes | ✅ Done | 100% |
| 3 | epic-booking | ✅ Done | 100% |
| 3 | epic-payments | ✅ Done | 100% |

## Status Legend

| Status | Symbol | Meaning |
|--------|--------|---------|
| Not Started | ⬜ | Work has not begun |
| In Progress | 🚧 | Currently being worked on |
| Done | ✅ | Completed and deployed |
| Blocked | ❌ | Blocked by dependencies or issues |

---

## Notes

- Update this document when starting or completing any epic
- All Phase 2 epics can be worked independently after epic-foundation is done
- epic-booking requires BOTH epic-members and epic-classes to be complete
- epic-payments only requires epic-members
- Weekend class schedule is not in scope for MVP — TBD with gym owner

## Bug Fixes & Improvements Log

```
2026-06-11:
- Done: Phase 3 complete — 183/183 tests passing.
  - epic-booking: Core/Booking BC. CreateBooking (capacity enforcement), CancelBooking, GetMemberBookings, GetClassRoster, GetAdminMemberBookings. 20 new tests.
  - epic-payments: Billing/Payment BC. RecordPayment (immutable), ListPayments, GetPaymentDetail, GetOverdueMembers (NOT EXISTS subquery), GetMyPayments. 26 new tests.
- Done: Phase 2 complete — 138/138 tests passing.
  - epic-public-site: React landing page (hero, schedule, pricing, contact). 20/20 frontend tests.
  - epic-members: Full CRUD + plan assignment + member profile. Backend tests green.
  - epic-classes: ClassSession bounded context (CQRS, DDD). Weekly schedule, coach sessions, admin CRUD.
- Done: epic-foundation — 52/52 tests passing. Auth, roles, base DB schema, seeders, React SPA login flow complete.

2026-06-10:
- Added: Initial roadmap created for Valhalla Gym MVP
```
