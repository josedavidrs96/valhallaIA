# Epic: Foundation — Authentication, Roles & Base Setup

**Type:** Epic
**Status:** Approved
**Created:** 2026-06-10
**Author:** AI (Requirement Writer Agent)

---

## Business Alignment

**Objective:** Operational efficiency — enable the gym to manage its operations digitally.

**Contribution:** This epic is the technical foundation. Without it, no other feature can exist.
It has no direct user-facing value on its own, but it is the mandatory prerequisite for all MVP
epics. It represents 100% of the technical risk at the start of the project.

**KPI Target:** N/A — this is an infrastructure epic. Success is measured by the epics it unlocks.

**Evidence:** All 5 subsequent epics (public site, members, classes, booking, payments) depend
on this foundation being in place.

**Deadline:** None. However, all other Phase 2 epics are blocked until this is complete.

---

## Problem Statement

### Current Situation

Valhalla Gym has no owned digital platform. There is no authentication system, no database,
no API, and no frontend application. The current setup consists of:

- A static HTML website (no management features)
- A third-party app for class bookings (no data ownership)
- Manual cash payment tracking (paper/spreadsheet)

### Pain Points

- No way to identify who is accessing any digital tool
- No role separation — admin, coach, and member have no differentiated access
- No persistent data store owned by the gym
- Impossible to build any feature without this base

### Impact if Not Solved

Nothing else can be built. All other epics are blocked.

---

## Proposed Solution

Set up the full project infrastructure and implement authentication with role-based access
control (RBAC). This includes:

1. **Project setup** — Docker environment, Laravel 11 API, React SPA, MySQL database
2. **Authentication** — Login, logout, session management (Laravel Sanctum)
3. **Role-based access** — Admin, Coach, Member (with route-level guards)
4. **Base seed data** — Predefined membership plans and class types (used by later epics)

No user-facing screens beyond the login page are included in this epic.

---

## User Stories

### US-001: Admin Login

**As an** Admin
**I want to** log in with my email and password
**So that** I can access the admin panel to manage the gym

**Acceptance Criteria:**
- [ ] Login form accepts email and password
- [ ] Correct credentials redirect to the admin dashboard
- [ ] Incorrect credentials show an error message (no account enumeration)
- [ ] After login, a session token is stored (Sanctum SPA cookie)
- [ ] Admin can only access admin routes

### US-002: Coach Login

**As a** Coach
**I want to** log in with my email and password
**So that** I can access my assigned classes and member lists

**Acceptance Criteria:**
- [ ] Same login form as Admin
- [ ] Correct credentials redirect to coach dashboard
- [ ] Coach cannot access admin-only routes (receives 403)
- [ ] Coach can only see coach-level content

### US-003: Member Login

**As a** Member
**I want to** log in with my email and password
**So that** I can book classes and view my membership status

**Acceptance Criteria:**
- [ ] Same login form (role is detected server-side, not selected by user)
- [ ] Correct credentials redirect to member dashboard
- [ ] Member cannot access admin or coach routes (receives 403)
- [ ] Inactive or suspended members see a clear message and cannot proceed

### US-004: Logout (all roles)

**As any** authenticated user
**I want to** log out
**So that** my session is closed and no one else can use my account

**Acceptance Criteria:**
- [ ] Logout button available in all authenticated layouts
- [ ] Session token is invalidated server-side (not just client-side)
- [ ] User is redirected to the public login page after logout

### US-005: Password Change (self-service)

**As any** authenticated user
**I want to** change my own password
**So that** I can keep my account secure

**Acceptance Criteria:**
- [ ] Requires current password confirmation
- [ ] New password must be at least 8 characters
- [ ] Password confirmation field must match
- [ ] Success shows confirmation message; failure shows specific error

### US-006: Admin resets another user's password

**As an** Admin
**I want to** reset the password of any user (coach or member)
**So that** I can help users who cannot access their accounts

**Acceptance Criteria:**
- [ ] Admin can set a new password for any user from the admin panel
- [ ] The affected user is NOT notified automatically (in MVP — no email system yet)
- [ ] Admin must communicate the new password manually (out of scope: email)

---

## Entities

| Entity | Description | Initial Status |
|--------|-------------|---------------|
| User | Single auth table for all roles. Role field distinguishes admin/coach/member | active |
| MembershipPlan | Predefined plan (2-day, 3-day, 4-5-day). Read-only in this epic | active |
| ClassType | Predefined class type (tren-superior, etc.). Read-only in this epic | active |

### User — State Machine

```
[Self-register] ──► pending_approval ──► active ◄──► inactive ──► [Soft Deleted]
[Admin creates] ──────────────────────► active         │
                                                   suspended (members only)
```

### User State Transitions

| From | To | Trigger | Conditions | Actor |
|------|----|---------|------------|-------|
| — | pending_approval | Member self-registers | Public form submitted | Guest |
| — | active | Admin creates user | Required fields filled | Admin |
| pending_approval | active | Admin approves | Account review complete | Admin |
| pending_approval | inactive | Admin rejects | Admin decides to reject | Admin |
| active | inactive | Admin deactivates | Any time | Admin |
| inactive | active | Admin reactivates | Any time | Admin |
| active | suspended | Admin suspends | Members only (non-payment, etc.) | Admin |
| suspended | active | Admin reactivates | Any time | Admin |
| any | soft deleted | Admin deletes | Irreversible in MVP | Admin |

**Login rules by status:**
- `active` → allowed
- `pending_approval` → blocked — "Tu cuenta esta pendiente de aprobacion. Contacta con el gimnasio."
- `inactive` → blocked — "Tu cuenta esta inactiva. Contacta con el gimnasio."
- `suspended` → blocked — "Tu cuenta esta suspendida. Contacta con el gimnasio."

**Delete strategy:** Soft delete (set `deleted_at`). Hard delete not exposed in MVP.
**Restore:** Not in MVP scope — flag for future.

### MembershipPlan — States

Plans are predefined and seeded. Admin cannot create new plans in MVP (CRUD is out of scope).

| Status | Meaning |
|--------|---------|
| active | Plan is available to assign to members |
| inactive | Plan is not shown for new assignments (grandfathered members keep it) |

### ClassType — States

Class types are predefined and seeded. Admin can create/deactivate in `epic-classes`.
In this epic they are **seed-only** (no CRUD UI).

| Status | Meaning |
|--------|---------|
| active | Class type can be used in the schedule |
| inactive | Class type is hidden from schedule creation |

---

## Use Cases

### UC-001: User Login

**Actor:** Admin, Coach, or Member
**Preconditions:** User account exists and is active
**Postconditions:** Authenticated session created; user redirected to role dashboard

**Main Flow:**
1. User navigates to `/login`
2. User enters email and password
3. System validates credentials
4. System detects role and redirects to the appropriate dashboard:
   - Admin → `/admin/dashboard`
   - Coach → `/coach/dashboard`
   - Member → `/member/dashboard`

**Alternative Flow — Inactive account:**
- Step 3: System finds account but status is `inactive` or `suspended`
- System returns error: "Tu cuenta esta inactiva. Contacta con el gimnasio."
- No session is created

**Error Scenarios:**
- Wrong password: generic message "Credenciales incorrectas" (no enumeration)
- Account does not exist: same generic message
- Empty fields: frontend validation before submit

### UC-002: User Logout

**Actor:** Any authenticated user
**Preconditions:** User is authenticated
**Postconditions:** Session token revoked; user redirected to `/login`

**Main Flow:**
1. User clicks "Cerrar sesion" in the navigation
2. System calls `POST /logout` with the session token
3. Sanctum invalidates the token server-side
4. Frontend clears local session state
5. User is redirected to `/login`

### UC-003: Change Own Password

**Actor:** Any authenticated user
**Preconditions:** User is authenticated and active
**Postconditions:** Password updated; same session continues

**Main Flow:**
1. User navigates to account settings
2. User enters current password, new password, and confirmation
3. System validates current password is correct
4. System validates new password meets requirements (min 8 chars)
5. System validates confirmation matches new password
6. Password is updated; success message shown

**Error Scenarios:**
- Current password wrong: "La contrasena actual no es correcta"
- New password too short: "La contrasena debe tener al menos 8 caracteres"
- Confirmation mismatch: "Las contrasenas no coinciden"

### UC-004: Expired Session Handling

**Actor:** Any authenticated user
**Preconditions:** User has an active session that expires while using the app
**Postconditions:** User sees a clear message and is redirected to login

**Main Flow:**
1. User performs any action that requires authentication
2. API returns 401 (token expired or invalidated)
3. Frontend intercepts the 401 response globally
4. Frontend shows toast/modal: "Tu sesion ha expirado. Por favor, vuelve a iniciar sesion."
5. Frontend clears local session state and redirects to `/login`

**Note:** This applies to ALL authenticated routes uniformly via a global API interceptor.

### UC-005: Protected Route Access (Authorization Guard)

**Actor:** Any user (authenticated or not)
**Preconditions:** User tries to access a protected route

**Main Flow — Unauthorized (no session):**
1. User accesses any protected route without a session
2. System redirects to `/login`

**Main Flow — Wrong role:**
1. Member tries to access `/admin/...`
2. System returns 403 Forbidden
3. Frontend shows "No tienes permiso para acceder a esta pagina"

### UC-006: Forced Password Change on First Login

**Actor:** Default admin user (seeded)
**Preconditions:** User logs in for the first time with `must_change_password = true`
**Postconditions:** Password updated; `must_change_password = false`; user proceeds normally

**Main Flow:**
1. Admin logs in successfully with default credentials
2. System detects `must_change_password = true`
3. System redirects immediately to the password change screen
4. Navigation is fully blocked — no other route is accessible
5. Admin enters new password and confirmation
6. System validates and updates the password
7. System sets `must_change_password = false`
8. System redirects to `/admin/dashboard`

**Error Scenarios:**
- New password same as default: "Debes elegir una contrasena diferente a la actual"
- Confirmation mismatch: "Las contrasenas no coinciden"
- Navigating to any other route while in forced-change state: redirected back to password change screen

---

## Seed Data

The following data must be inserted on first migration (`db:seed`):

### Membership Plans

| Slug | Name (ES) | Price (EUR/month) | Classes/month | Access | Extras |
|------|-----------|--------------------|---------------|--------|--------|
| plan-2-dias | Plan 2 Dias | 35 | 8 | 2 days/week | Vestuarios y duchas |
| plan-3-dias | Plan 3 Dias | 38 | 12 | 3 days/week | Vestuarios, duchas, asesoramiento |
| plan-4-5-dias | Plan 4-5 Dias | 40 | 20–25 | Unlimited | Vestuarios, duchas, plan personalizado |

### Class Types

| Slug | Name (ES) | Category |
|------|-----------|----------|
| tren-superior | Calistenia — Tren Superior | Calisthenics |
| tren-inferior | Calistenia — Tren Inferior | Calisthenics |
| full-body | Calistenia — Full Body | Calisthenics |
| gap | GAP | Conditioning |
| entrenamiento-libre | Entrenamiento Libre | Free Training |

### Admin User (default)

| Field | Value |
|-------|-------|
| Name | Administrador |
| Email | admin@valhallagym.com |
| Password | Read from `ADMIN_DEFAULT_PASSWORD` env variable |
| Role | admin |
| Status | active |
| Force password change | true (flag: `must_change_password = true`) |

**First-login flow:** If `must_change_password = true`, the system redirects the user to the
password change screen immediately after login. The user cannot navigate elsewhere until the
password is changed. After changing it, `must_change_password` is set to `false`.

---

## Project Setup Scope (Infrastructure)

The following technical setup is part of this epic:

| Component | Technology | Notes |
|-----------|-----------|-------|
| Backend | Laravel 11 (PHP 8.3) | DDD/Hexagonal structure from day 1 |
| Frontend | React 18 + TypeScript + Vite | SPA served separately |
| Styling | Tailwind CSS | Brand colors configured |
| Database | MySQL 8.0 | |
| Auth | Laravel Sanctum | SPA cookie mode |
| Container | Docker + Docker Compose | All services containerized |
| DB Migrations | Laravel Migrations | users, membership_plans, class_types tables |
| DB Seeder | Laravel Seeders | Plans, class types, default admin user |

### Folder Structure (DDD from day 1)

```
src/
├── Core/
│   ├── Member/
│   │   └── Domain/
│   │       ├── Entities/Member.php
│   │       ├── ValueObjects/MemberId.php
│   │       └── Enums/MemberStatus.php
│   └── Staff/
│       └── Domain/
│           ├── Entities/Staff.php
│           ├── ValueObjects/StaffId.php
│           └── Enums/StaffRole.php
├── Billing/
│   └── Payment/
│       └── Domain/
│           └── ValueObjects/MembershipPlanId.php
└── Shared/
    └── Auth/
        └── Domain/
            ├── Entities/User.php
            └── ValueObjects/UserId.php
```

---

## Collateral Impact

**None.** This is the first epic — no existing functionality is affected.

| Component | Impact | Action Required |
|-----------|--------|-----------------|
| Existing static website | None — it remains live during development | No action needed |
| Third-party booking app | None — it keeps running in parallel during MVP | No action needed |

---

## Out of Scope (this epic)

| Feature | Why Excluded | Info Needed Now |
|---------|-------------|-----------------|
| Member registration by admin | Covered in `epic-members` | DB schema must accommodate member profile fields — see Schema Notes below |
| Coach creation by admin | Covered in `epic-members` | DB schema must accommodate staff profile fields — see Schema Notes below |
| Admin resets other user's password | Moved to `epic-members` — requires user list UI that doesn't exist here | N/A |
| User profile view/edit ("Mi cuenta") | Moved to `epic-members` | N/A |
| Forgot password / email reset | No email system in MVP | N/A |
| Social login (Google, etc.) | Not needed for local gym | N/A |
| Two-factor authentication | Overkill for MVP | N/A |
| Membership plan CRUD (admin) | Plans are predefined — seeded, no UI in MVP | Plans are seeded |
| Class type CRUD (admin) | Covered in `epic-classes` | Class types are seeded |
| Profile photo upload | Out of MVP scope | N/A |

## Schema Notes (for Architect — design these tables with future fields in mind)

These fields will be needed in future epics. The Architect must account for them in the initial
migration to avoid `ALTER TABLE` operations later.

### `members` table (to be populated in `epic-members`)
Fields to reserve: `member_number` (unique, auto-incremented business ID), `first_name`,
`last_name`, `phone`, `date_of_birth`, `profile_photo`, `join_date`,
`emergency_contact_name`, `emergency_contact_phone`, `notes`

### `staff` table (to be populated in `epic-members`)
Fields to reserve: `first_name`, `last_name`, `phone`, `specialization` (coaches only),
`hire_date`

> These fields are **TBD pending PO confirmation** — the above are proposed defaults.
> The Architect should flag if any fields are missing before finalizing the migration.

---

## Definition of Done

- [ ] Docker environment starts with `docker-compose up -d` and all services run
- [ ] Database migrations run cleanly with `php artisan migrate`
- [ ] Seed data is inserted with `php artisan db:seed` (plans, class types, admin user)
- [ ] `POST /api/login` returns a session token for valid credentials
- [ ] `POST /api/login` is rate-limited: max 5 attempts per IP per minute (429 response after limit)
- [ ] `POST /api/logout` invalidates the session token server-side
- [ ] Standard session expires after 7 days
- [ ] "Remember me" session expires after 1 year
- [ ] Admin routes return 403 when accessed by a Coach or Member
- [ ] Coach routes return 403 when accessed by a Member or Guest
- [ ] Member routes return 403 when accessed by a Guest
- [ ] Unauthenticated requests to protected routes return 401
- [ ] Frontend shows "Tu sesion ha expirado" message on 401 and redirects to `/login`
- [ ] `pending_approval` users see specific block message on login attempt
- [ ] `inactive` users see specific block message on login attempt
- [ ] `suspended` users see specific block message on login attempt
- [ ] Password change endpoint works correctly for all roles
- [ ] Admin with `must_change_password = true` is forced to change password before any navigation
- [ ] After forced change, `must_change_password` is set to `false`
- [ ] Login page renders in the React SPA with Tailwind brand colors (#2563eb, #0f172a)
- [ ] Unit tests: login success, wrong password, inactive/suspended/pending user, role guard
- [ ] Integration tests: full login → token → protected route → logout cycle
- [ ] Integration test: `must_change_password` forced flow
- [ ] Code reviewed before merge
- [ ] No hardcoded credentials in the codebase (use `.env`)

---

## Time Constraints

**Deadline:** None — no business event tied to this.
**Type:** None
**Note:** This epic should be completed before starting any Phase 2 work.

---

## Decisions (resolved)

1. **Default admin credentials** — ✅ A known default password is set at first boot.
   The system must force a password change on the first login of the default admin.
   Password is defined in `.env` (`ADMIN_DEFAULT_PASSWORD`) and used only once by the seeder.

2. **Session duration** — ✅ Standard session: **7 days**.
   "Remember me" option on the login form extends the session to **1 year**.

3. **Member registration** — ✅ **Both paths are valid:**
   - Admin can create a member account directly (immediate access, active by default).
   - A member can self-register via a public form. Account is created with status `pending_approval`.
     Admin must activate it before the member can log in.
   > Self-registration form is part of `epic-members`, not this epic.
   > This epic only needs to support the `pending_approval` status in the User entity.

4. **Multiple admin users** — ✅ Yes. Any user with `role = admin` has full admin access.
   There is no super-admin distinction in MVP.
