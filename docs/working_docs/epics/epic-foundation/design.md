# Solution Design: Foundation — Auth, Roles & Base Setup

**Requirement:** [requirements.md](requirements.md)
**Date:** 2026-06-10
**Status:** Draft
**Bounded Context:** `Shared/Auth` (primary) + schema placeholders for `Core/Member`, `Core/Staff`

---

## Summary

Greenfield project. No existing codebase. This design covers:
1. Full Docker environment (Laravel API + React SPA + MySQL)
2. Authentication via Laravel Sanctum (token-based, Bearer header)
3. Role-based access control (admin, coach, member) via middleware guards
4. Base DB schema: `users`, `members`, `staff`, `membership_plans`, `class_types`
5. Seed data: 3 plans, 5 class types, 1 default admin (forced password change)

---

## Architecture Decision

**Token-based Sanctum (Bearer token)** instead of SPA cookie-based auth.

Reason: React SPA runs on a different port in development (Vite :5173 vs Laravel :8000).
Cookie-based Sanctum requires same-origin — token-based works cross-origin cleanly.
Token stored in `localStorage` for MVP simplicity. Acceptable for a local gym admin tool.

**Single `users` table** for all roles. `members` and `staff` tables hold profile data
and reference `users.id`. This keeps auth in one place and profiles decoupled.

**`LoginQuery` architectural exception**: Login generates a token (modifies state) but
must return it immediately. Uses Query pattern per `critical-rules.md` exception for
security/infrastructure operations.

---

## Existing Code Analysis

| Component | Location | Reusable | Modifications Needed |
|-----------|----------|----------|---------------------|
| — | — | N/A — greenfield project | N/A |

---

## Project Structure

```
valhallaIA/
├── backend/                        # Laravel 11 (PHP 8.3)
│   ├── app/
│   │   └── Http/
│   │       ├── Actions/Auth/       # Thin HTTP orchestrators
│   │       ├── Requests/Auth/      # DTO mappers (no framework validation)
│   │       ├── Resources/Auth/     # Response transformers
│   │       └── Middleware/         # Role guards
│   ├── src/
│   │   ├── Core/
│   │   │   ├── Member/Domain/      # Schema-only in this epic
│   │   │   └── Staff/Domain/       # Schema-only in this epic
│   │   └── Shared/
│   │       └── Auth/
│   │           ├── Domain/
│   │           ├── Application/
│   │           └── Infrastructure/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/api.php
├── frontend/                       # React 18 + TypeScript + Vite
│   └── src/
│       ├── pages/auth/
│       ├── contexts/
│       ├── services/
│       └── types/
├── docker/
│   ├── php/Dockerfile
│   └── node/Dockerfile
├── docker-compose.yml
└── Makefile
```

---

## Implementation Plan

### 1. Domain Layer — `src/Shared/Auth/`

#### Entities

| Entity | File Path | Description |
|--------|-----------|-------------|
| User | `src/Shared/Auth/Domain/Entities/User.php` | Auth entity. Holds credentials, role, status. No profile data. |

```
// User invariants (domain rules enforced in entity methods):
// - email must be valid format
// - password must be hashed (never plain text)
// - role transitions are controlled (no direct property mutation)
// - status transitions follow the defined state machine

class User:
    readonly id: UserId
    readonly email: UserEmail
    private password: HashedPassword
    readonly role: UserRole
    private status: UserStatus
    private mustChangePassword: bool
    readonly deletedAt: ?DateTime
    readonly createdAt: DateTime

    // Domain methods
    function changePassword(newHash: HashedPassword): void
    function activate(): void
    function deactivate(): void
    function suspend(): void         // members only
    function approve(): void         // pending_approval → active
    function reject(): void          // pending_approval → inactive
    function clearPasswordChangeFlag(): void
    function softDelete(): void
    function isActive(): bool
    function canLogin(): bool        // active only
```

#### Value Objects

| VO | File Path | Description |
|----|-----------|-------------|
| UserId | `src/Shared/Auth/Domain/ValueObjects/UserId.php` | ULID-based ID |
| UserEmail | `src/Shared/Auth/Domain/ValueObjects/UserEmail.php` | Validated email format |
| HashedPassword | `src/Shared/Auth/Domain/ValueObjects/HashedPassword.php` | Wraps bcrypt hash |

#### Enums

| Enum | File Path | Values |
|------|-----------|--------|
| UserRole | `src/Shared/Auth/Domain/Enums/UserRole.php` | `admin`, `coach`, `member` |
| UserStatus | `src/Shared/Auth/Domain/Enums/UserStatus.php` | `active`, `inactive`, `suspended`, `pending_approval` |

#### Domain Events

| Event | Trigger | Payload |
|-------|---------|---------|
| UserPasswordChangedEvent | `changePassword()` called | `userId` |
| UserStatusChangedEvent | Any status transition | `userId`, `oldStatus`, `newStatus` |

> Events are defined now but listeners are added in future epics as needed.

#### Repository Interface

```php
// src/Shared/Auth/Domain/Repositories/UserRepositoryInterface.php

interface UserRepositoryInterface:
    function getById(id: UserId): User                    // throws UserNotFoundException
    function findByEmail(email: UserEmail): ?User
    function save(user: User): void
    function softDelete(id: UserId): void
```

---

### 2. Application Layer — `src/Shared/Auth/Application/`

#### Commands

| Command | Handler | Returns | Description |
|---------|---------|---------|-------------|
| LogoutCommand | LogoutHandler | void | Revokes the current Sanctum token |
| ChangePasswordCommand | ChangePasswordHandler | void | Changes password, clears force-change flag |

```
// LogoutCommand
class LogoutCommand implements CommandInterface:
    constructor(userId: UserId, tokenId: string)

// ChangePasswordCommand
class ChangePasswordCommand implements CommandInterface:
    constructor(userId: UserId, currentPassword: string, newPassword: string)
    // Handler throws: WrongCurrentPasswordException, WeakPasswordException
```

#### Queries (including auth exception)

| Query | Handler | Returns | Description |
|-------|---------|---------|-------------|
| AuthenticateQuery | AuthenticateHandler | AuthTokenRM | **ARCHITECTURAL EXCEPTION** — generates + stores token |
| GetAuthenticatedUserQuery | GetAuthenticatedUserHandler | AuthUserRM | Returns current user data for UI |

```
// ARCHITECTURAL EXCEPTION: generates + stores token. Acceptable ONLY for auth.
// See critical-rules.md — security operations that must return value immediately.
class AuthenticateQuery implements QueryInterface:
    constructor(email: string, password: string, rememberMe: bool)

class AuthTokenRM:               // ReadModel returned by AuthenticateHandler
    readonly userId: UserId
    readonly token: string
    readonly expiresAt: DateTime
    readonly role: UserRole
    readonly mustChangePassword: bool

class AuthUserRM:                // ReadModel for current user info
    readonly id: UserId
    readonly email: string
    readonly role: UserRole
    readonly status: UserStatus
    readonly mustChangePassword: bool
```

---

### 3. Infrastructure Layer

#### Repository Implementation

| Interface | Implementation | Table |
|-----------|----------------|-------|
| UserRepositoryInterface | `UserRepository` | `users` |

#### Tables (constants — never use string literals in queries)

| Class | File Path | Table Name |
|-------|-----------|------------|
| UserTable | `src/Shared/Auth/Infrastructure/Tables/UserTable.php` | `users` |
| MemberTable | `src/Core/Member/Infrastructure/Tables/MemberTable.php` | `members` |
| StaffTable | `src/Core/Staff/Infrastructure/Tables/StaffTable.php` | `staff` |
| MembershipPlanTable | `src/Core/Member/Infrastructure/Tables/MembershipPlanTable.php` | `membership_plans` |
| ClassTypeTable | `src/Core/Class/Infrastructure/Tables/ClassTypeTable.php` | `class_types` |

#### Hydrators

| Hydrator | File Path |
|----------|-----------|
| UserHydrator | `src/Shared/Auth/Infrastructure/Hydrators/UserHydrator.php` |

#### Migrations (in order)

| # | Migration | Description |
|---|-----------|-------------|
| 1 | `create_users_table` | Auth users with role, status, must_change_password |
| 2 | `create_members_table` | Member profiles (FK → users.id). Full schema reserved. |
| 3 | `create_staff_table` | Staff profiles (FK → users.id). Full schema reserved. |
| 4 | `create_membership_plans_table` | Plans with pricing and access rules |
| 5 | `create_class_types_table` | Class type catalogue |

#### Seeders

| Seeder | Description |
|--------|-------------|
| MembershipPlanSeeder | Inserts 3 predefined plans |
| ClassTypeSeeder | Inserts 5 predefined class types |
| DefaultAdminSeeder | Inserts admin user from `ADMIN_DEFAULT_PASSWORD` env |

---

### 4. HTTP Layer

#### Endpoints

| Method | Route | Action | Request | Resource | Middleware |
|--------|-------|--------|---------|----------|------------|
| POST | `/api/auth/login` | LoginAction | LoginRequest | AuthResource | `throttle:5,1` |
| POST | `/api/auth/logout` | LogoutAction | — | — | `auth:sanctum` |
| PUT | `/api/auth/password` | ChangePasswordAction | ChangePasswordRequest | — | `auth:sanctum` |
| GET | `/api/auth/me` | GetCurrentUserAction | — | CurrentUserResource | `auth:sanctum` |

#### Middleware (Role Guards)

| Middleware | Class | Registered As |
|------------|-------|---------------|
| Admin only | `AdminRoleMiddleware` | `role.admin` |
| Coach only | `CoachRoleMiddleware` | `role.coach` |
| Member only | `MemberRoleMiddleware` | `role.member` |
| Admin or Coach | `StaffRoleMiddleware` | `role.staff` |
| Force password change | `ForcePasswordChangeMiddleware` | `password.change` |

```
// ForcePasswordChangeMiddleware — applied after auth:sanctum on all routes
// except PUT /api/auth/password itself
// If user.mustChangePassword === true → return 403 with code MUST_CHANGE_PASSWORD
// Frontend intercepts this code and redirects to password change screen
```

#### Request DTOs (no framework validation — only getDto())

```
// LoginRequest → LoginDto
class LoginDto:
    readonly email: string
    readonly password: string
    readonly rememberMe: bool

// ChangePasswordRequest → ChangePasswordDto
class ChangePasswordDto:
    readonly currentPassword: string
    readonly newPassword: string
    readonly newPasswordConfirmation: string
```

#### Resources

```
// AuthResource (login response)
{
    "token": "...",
    "expires_at": "2026-06-17T10:00:00Z",
    "user": {
        "id": "...",
        "email": "admin@valhallagym.com",
        "role": "admin",
        "must_change_password": true
    }
}

// CurrentUserResource (GET /me)
{
    "id": "...",
    "email": "...",
    "role": "admin|coach|member",
    "status": "active",
    "must_change_password": false
}
```

---

### 5. Frontend — React SPA

#### Key Files

| File | Purpose |
|------|---------|
| `src/services/api.ts` | Axios instance with base URL + 401 interceptor |
| `src/contexts/AuthContext.tsx` | Auth state: user, token, login(), logout() |
| `src/hooks/useAuth.ts` | Hook to consume AuthContext |
| `src/pages/auth/LoginPage.tsx` | Login form with brand colors |
| `src/pages/auth/ChangePasswordPage.tsx` | Forced change password form |
| `src/router/ProtectedRoute.tsx` | Route guard — redirects if not authenticated |
| `src/router/RoleRoute.tsx` | Route guard — redirects on role mismatch |
| `src/router/index.tsx` | React Router config for all roles |

#### Axios 401 Interceptor (session expired)

```typescript
// On every 401 response:
api.interceptors.response.use(null, (error) => {
  if (error.response?.status === 401) {
    localStorage.removeItem('auth_token')
    showToast('Tu sesion ha expirado. Por favor, vuelve a iniciar sesion.')
    window.location.href = '/login'
  }
  return Promise.reject(error)
})
```

#### Route Structure

```
/login                    → LoginPage (public)
/cambiar-contrasena       → ChangePasswordPage (auth:sanctum + any role)

/admin/*                  → AdminLayout (auth:sanctum + role.admin)
  /admin/dashboard        → AdminDashboard

/entrenador/*             → CoachLayout (auth:sanctum + role.coach)
  /entrenador/dashboard   → CoachDashboard

/socio/*                  → MemberLayout (auth:sanctum + role.member)
  /socio/dashboard        → MemberDashboard
```

---

## Database Schema

### `users` table

```sql
CREATE TABLE users (
    id              CHAR(26) PRIMARY KEY,           -- ULID
    email           VARCHAR(191) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,           -- bcrypt
    role            ENUM('admin','coach','member') NOT NULL,
    status          ENUM('active','inactive','suspended','pending_approval')
                    NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    remember_token  VARCHAR(100) NULL,
    deleted_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role_status (role, status)
);
```

### `members` table (profile — reserved schema)

```sql
CREATE TABLE members (
    id                          CHAR(26) PRIMARY KEY,   -- ULID
    user_id                     CHAR(26) NOT NULL UNIQUE,
    member_number               INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
    first_name                  VARCHAR(100) NOT NULL,
    last_name                   VARCHAR(100) NOT NULL,
    phone                       VARCHAR(20) NULL,
    date_of_birth               DATE NULL,
    profile_photo               VARCHAR(500) NULL,
    join_date                   DATE NOT NULL,
    emergency_contact_name      VARCHAR(200) NULL,
    emergency_contact_phone     VARCHAR(20) NULL,
    notes                       TEXT NULL,
    deleted_at                  TIMESTAMP NULL,
    created_at                  TIMESTAMP NULL,
    updated_at                  TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_member_number (member_number),
    INDEX idx_user_id (user_id)
);
```

### `staff` table (profile — reserved schema)

```sql
CREATE TABLE staff (
    id              CHAR(26) PRIMARY KEY,   -- ULID
    user_id         CHAR(26) NOT NULL UNIQUE,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20) NULL,
    specialization  VARCHAR(200) NULL,      -- coaches only
    hire_date       DATE NULL,
    deleted_at      TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

### `membership_plans` table

```sql
CREATE TABLE membership_plans (
    id                      CHAR(26) PRIMARY KEY,
    slug                    VARCHAR(50) NOT NULL UNIQUE,
    name                    VARCHAR(100) NOT NULL,
    price_cents             INT UNSIGNED NOT NULL,      -- store in cents (3500 = €35.00)
    classes_per_month       INT UNSIGNED NOT NULL,
    access_days_per_week    INT UNSIGNED NULL,          -- NULL = unlimited
    unlimited_access        TINYINT(1) NOT NULL DEFAULT 0,
    extras                  TEXT NULL,
    status                  ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    INDEX idx_status (status)
);
```

### `class_types` table

```sql
CREATE TABLE class_types (
    id          CHAR(26) PRIMARY KEY,
    slug        VARCHAR(50) NOT NULL UNIQUE,
    name        VARCHAR(100) NOT NULL,
    category    VARCHAR(50) NOT NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

---

## State Machine

```
User Status:
[Guest self-registers] ──► pending_approval ──┐
[Admin creates]        ──► active ◄────────── ┘ (Admin approves)
                                │
                         ◄──► inactive ──► [soft_deleted]
                                │
                           suspended
                         (members only)

Login allowed: active only
Login blocked: pending_approval, inactive, suspended (distinct messages)
```

---

## Docker Compose Services

```yaml
services:
  app:        # Laravel PHP-FPM (port 8000)
  mysql:      # MySQL 8.0 (port 3306)
  redis:      # Redis for rate limiting / cache (port 6379)
  node:       # React Vite dev server (port 5173)
  nginx:      # Reverse proxy (port 80) — optional for dev
```

---

## Collateral Changes

None — greenfield project.

---

## Dependencies

| Dependency | Type | Description |
|------------|------|-------------|
| `laravel/sanctum` | Backend | Token-based auth |
| `symfony/uid` | Backend | ULID generation for IDs |
| `react-router-dom` | Frontend | Client-side routing |
| `axios` | Frontend | HTTP client |
| `@tanstack/react-query` | Frontend | Server state management |
| `tailwindcss` | Frontend | Utility CSS |
| `react-hot-toast` | Frontend | Toast notifications (session expired, etc.) |

---

## Testing Strategy

| Test Type | Scope | Priority | File Path |
|-----------|-------|----------|-----------|
| Unit | `User` entity — status transitions, domain methods | High | `tests/Unit/Shared/Auth/UserTest.php` |
| Unit | `AuthenticateHandler` — credentials, inactive block, rememberMe | High | `tests/Unit/Shared/Auth/AuthenticateHandlerTest.php` |
| Unit | `ChangePasswordHandler` — wrong current, weak new, same as current | High | `tests/Unit/Shared/Auth/ChangePasswordHandlerTest.php` |
| Integration | `POST /api/auth/login` — success, wrong password, rate limit | High | `tests/Feature/Auth/LoginTest.php` |
| Integration | `POST /api/auth/logout` — token revoked | High | `tests/Feature/Auth/LogoutTest.php` |
| Integration | Role guards — 401, 403 by role | High | `tests/Feature/Auth/RoleGuardTest.php` |
| Integration | `must_change_password` force-change flow | High | `tests/Feature/Auth/ForcePasswordChangeTest.php` |
| Integration | `pending_approval` login block | Medium | `tests/Feature/Auth/LoginTest.php` |

---

## Implementation Order

1. [ ] Docker + environment setup (`docker-compose.yml`, Dockerfiles, `.env.example`)
2. [ ] Laravel project init with DDD folder structure
3. [ ] React + Vite + TypeScript + Tailwind project init
4. [ ] Domain: `UserRole`, `UserStatus` enums
5. [ ] Domain: `UserId`, `UserEmail`, `HashedPassword` value objects
6. [ ] Domain: `User` entity with state machine methods
7. [ ] Domain: `UserRepositoryInterface`
8. [ ] Domain: `AuthTokenRM`, `AuthUserRM` read models
9. [ ] Migrations: `users`, `members`, `staff`, `membership_plans`, `class_types`
10. [ ] Infrastructure: `UserTable`, `MemberTable`, `StaffTable`, `MembershipPlanTable`, `ClassTypeTable`
11. [ ] Infrastructure: `UserHydrator`
12. [ ] Infrastructure: `UserRepository`
13. [ ] Seeders: `MembershipPlanSeeder`, `ClassTypeSeeder`, `DefaultAdminSeeder`
14. [ ] Application: `AuthenticateQuery` + `AuthenticateHandler`
15. [ ] Application: `LogoutCommand` + `LogoutHandler`
16. [ ] Application: `ChangePasswordCommand` + `ChangePasswordHandler`
17. [ ] Application: `GetAuthenticatedUserQuery` + handler
18. [ ] HTTP: Role middleware (`AdminRoleMiddleware`, `CoachRoleMiddleware`, `MemberRoleMiddleware`, `StaffRoleMiddleware`)
19. [ ] HTTP: `ForcePasswordChangeMiddleware`
20. [ ] HTTP: `LoginRequest` → `LoginDto`, `ChangePasswordRequest` → `ChangePasswordDto`
21. [ ] HTTP: `LoginAction`, `LogoutAction`, `ChangePasswordAction`, `GetCurrentUserAction`
22. [ ] HTTP: `AuthResource`, `CurrentUserResource`
23. [ ] HTTP: Routes in `api.php`
24. [ ] Frontend: Axios instance + 401 interceptor
25. [ ] Frontend: `AuthContext` + `useAuth` hook
26. [ ] Frontend: `LoginPage` with brand colors
27. [ ] Frontend: `ChangePasswordPage` (forced)
28. [ ] Frontend: `ProtectedRoute` + `RoleRoute` guards
29. [ ] Frontend: Placeholder dashboards (admin, coach, member)
30. [ ] Tests: Unit + Integration suite
31. [ ] Makefile: `make up`, `make migrate`, `make seed`, `make test`

---

## Open Technical Questions

None — all decisions resolved.

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| CORS issues between Laravel:8000 and Vite:5173 | Medium | Medium | Configure `cors.php` to allow Vite dev origin |
| ULID library not available in chosen Laravel version | Low | Low | `symfony/uid` is well-maintained and Laravel 11 compatible |
| `member_number` AUTO_INCREMENT on non-primary key | Low | Low | Standard MySQL behavior — tested in migration |
| Token stored in localStorage vulnerable to XSS | Low (admin tool) | Medium | Acceptable for MVP local gym tool; note for future hardening |
