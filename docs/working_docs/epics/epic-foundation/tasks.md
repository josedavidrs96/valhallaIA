# Implementation Tasks: Foundation — Auth, Roles & Base Setup

**Requirement:** [requirements.md](requirements.md)
**Solution Design:** [design.md](design.md)
**Created:** 2026-06-10
**Total Tasks:** 58
**Overall Complexity:** XL (greenfield project — includes full environment setup)

---

## Summary

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Phase 0: Environment setup | 4 | L |
| Phase 1: Domain layer | 11 | M |
| Phase 2: Infrastructure layer | 16 | M |
| Phase 3: Application layer | 8 | M |
| Phase 4: HTTP layer (backend) | 15 | M |
| Phase 5: Frontend (React) | 7 | M |
| Phase 6: Tests | 8 | M |

---

## Phase 0: Environment Setup

### T-001: Docker Compose environment

**Phase:** Setup · **Complexity:** L · **Dependencies:** None

**Description:**
Create the full Docker Compose environment with all services.

**Files:**
- `docker-compose.yml`
- `docker/php/Dockerfile` (PHP 8.3 FPM + Composer)
- `docker/node/Dockerfile` (Node 20 LTS)
- `.env.example`

**Acceptance Criteria:**
- [ ] Services: `app` (Laravel PHP-FPM :8000), `mysql` (MySQL 8.0 :3306), `redis` (Redis :6379), `node` (Vite :5173)
- [ ] `docker-compose up -d` starts all services without errors
- [ ] MySQL volume is persisted between restarts
- [ ] `.env.example` includes: `DB_*`, `APP_KEY`, `SANCTUM_STATEFUL_DOMAINS`, `ADMIN_DEFAULT_PASSWORD`, `FRONTEND_URL`
- [ ] `.env` is in `.gitignore`

---

### T-002: Laravel 11 project initialization

**Phase:** Setup · **Complexity:** M · **Dependencies:** T-001

**Description:**
Bootstrap Laravel 11 inside `backend/` with DDD folder structure and required packages.

**Files:**
- `backend/` (Laravel project root)
- `backend/src/` (DDD source root — added to composer autoload as `App\\Src\\`)
- `backend/composer.json`

**Packages to install:**
- `laravel/sanctum`
- `symfony/uid` (ULID)

**Acceptance Criteria:**
- [ ] `php artisan key:generate` succeeds
- [ ] `backend/src/` is autoloaded via PSR-4 in `composer.json`
- [ ] Sanctum installed and configured
- [ ] `php artisan migrate` runs without errors on a clean DB
- [ ] CORS configured to allow `FRONTEND_URL` (from `.env`)

---

### T-003: React + TypeScript + Vite + Tailwind project initialization

**Phase:** Setup · **Complexity:** M · **Dependencies:** T-001

**Description:**
Bootstrap the React SPA inside `frontend/` with all required dependencies.

**Files:**
- `frontend/` (React project root)
- `frontend/src/`
- `frontend/vite.config.ts`
- `frontend/tailwind.config.ts`

**Packages to install:**
- `react-router-dom`
- `axios`
- `@tanstack/react-query`
- `react-hot-toast`
- `tailwindcss` + `@tailwindcss/forms`

**Tailwind brand config:**
```
primary:   #2563eb   → blue-600
dark:      #0f172a   → slate-950
accent:    #60a5fa   → blue-400
```

**Acceptance Criteria:**
- [ ] `npm run dev` starts Vite on port 5173
- [ ] `npm run build` produces a production build without errors
- [ ] Tailwind brand colors configured as custom theme tokens
- [ ] TypeScript strict mode enabled
- [ ] Path aliases configured: `@/` → `src/`

---

### T-004: Makefile with project commands

**Phase:** Setup · **Complexity:** S · **Dependencies:** T-001, T-002, T-003

**Files:** `Makefile`

**Commands to implement:**
```makefile
make up          # docker-compose up -d
make down        # docker-compose down
make migrate     # php artisan migrate
make seed        # php artisan db:seed
make fresh       # migrate:fresh --seed
make test        # php artisan test
make install     # composer install + npm install
```

**Acceptance Criteria:**
- [ ] All commands run inside Docker (never on host directly)
- [ ] `make fresh` resets DB and re-seeds in one step

---

## Phase 1: Domain Layer

### T-005: UserRole and UserStatus enums

**Phase:** Domain · **Complexity:** S · **Dependencies:** None

**Files:**
- `backend/src/Shared/Auth/Domain/Enums/UserRole.php`
- `backend/src/Shared/Auth/Domain/Enums/UserStatus.php`

**Acceptance Criteria:**
- [ ] `UserRole`: values `admin`, `coach`, `member`
- [ ] `UserStatus`: values `active`, `inactive`, `suspended`, `pending_approval`
- [ ] Both use PHP 8.1+ backed enums (string-backed)
- [ ] `UserStatus::canLogin()`: returns true only for `active`

---

### T-006: UserId Value Object

**Phase:** Domain · **Complexity:** S · **Dependencies:** None

**File:** `backend/src/Shared/Auth/Domain/ValueObjects/UserId.php`

**Acceptance Criteria:**
- [ ] Extends `Ulid` (from `symfony/uid`)
- [ ] Static `random()` factory method
- [ ] Static `fromString(string $value)` factory method
- [ ] `value(): string` method

---

### T-007: UserEmail Value Object

**Phase:** Domain · **Complexity:** S · **Dependencies:** None

**File:** `backend/src/Shared/Auth/Domain/ValueObjects/UserEmail.php`

**Acceptance Criteria:**
- [ ] Constructor validates format with `filter_var(FILTER_VALIDATE_EMAIL)`
- [ ] Throws `InvalidUserEmailException` on invalid format
- [ ] `value(): string` method
- [ ] `equals(UserEmail $other): bool` method

---

### T-008: HashedPassword Value Object

**Phase:** Domain · **Complexity:** S · **Dependencies:** None

**File:** `backend/src/Shared/Auth/Domain/ValueObjects/HashedPassword.php`

**Acceptance Criteria:**
- [ ] Wraps a bcrypt hash string (does NOT hash — hashing happens in handler)
- [ ] Static `fromPlainText(string $plain): self` — hashes and wraps
- [ ] `verify(string $plain): bool` — checks against hash
- [ ] `value(): string` — returns the hash
- [ ] `isSameAs(string $plain): bool` — alias for verify, used in change-password logic

---

### T-009: User Entity

**Phase:** Domain · **Complexity:** M · **Dependencies:** T-005, T-006, T-007, T-008

**File:** `backend/src/Shared/Auth/Domain/Entities/User.php`

**Acceptance Criteria:**
- [ ] Public readonly properties: `id`, `email`, `role`, `createdAt`
- [ ] Private mutable: `password`, `status`, `mustChangePassword`, `deletedAt`
- [ ] Static `create()` factory method — records `UserCreatedEvent`
- [ ] `changePassword(HashedPassword $new): void` — validates new != current
- [ ] `activate(): void` — from `inactive` or `pending_approval` → `active`
- [ ] `deactivate(): void` — from `active` → `inactive`
- [ ] `suspend(): void` — only for `member` role, from `active` → `suspended`
- [ ] `approve(): void` — from `pending_approval` → `active`
- [ ] `reject(): void` — from `pending_approval` → `inactive`
- [ ] `clearPasswordChangeFlag(): void` — sets `mustChangePassword = false`
- [ ] `softDelete(): void` — sets `deletedAt`
- [ ] `isActive(): bool`
- [ ] `canLogin(): bool` — delegates to `UserStatus::canLogin()`
- [ ] `mustChangePassword(): bool`
- [ ] All state transitions throw domain exceptions on invalid transitions
- [ ] No getters/setters — public properties where readonly, methods for mutations

---

### T-010: Domain Exceptions

**Phase:** Domain · **Complexity:** S · **Dependencies:** T-005

**Files:**
- `backend/src/Shared/Auth/Domain/Exceptions/UserNotFoundException.php`
- `backend/src/Shared/Auth/Domain/Exceptions/InvalidCredentialsException.php`
- `backend/src/Shared/Auth/Domain/Exceptions/UserCannotLoginException.php`
- `backend/src/Shared/Auth/Domain/Exceptions/InvalidUserEmailException.php`
- `backend/src/Shared/Auth/Domain/Exceptions/WrongCurrentPasswordException.php`
- `backend/src/Shared/Auth/Domain/Exceptions/WeakPasswordException.php`
- `backend/src/Shared/Auth/Domain/Exceptions/InvalidStatusTransitionException.php`

**Acceptance Criteria:**
- [ ] Each extends `\DomainException`
- [ ] Each has a meaningful default message in English

---

### T-011: Domain Events

**Phase:** Domain · **Complexity:** S · **Dependencies:** T-006

**Files:**
- `backend/src/Shared/Auth/Domain/Events/UserCreatedEvent.php`
- `backend/src/Shared/Auth/Domain/Events/UserPasswordChangedEvent.php`
- `backend/src/Shared/Auth/Domain/Events/UserStatusChangedEvent.php`

**Acceptance Criteria:**
- [ ] `UserCreatedEvent`: payload `userId: UserId`
- [ ] `UserPasswordChangedEvent`: payload `userId: UserId`
- [ ] `UserStatusChangedEvent`: payload `userId: UserId`, `oldStatus: UserStatus`, `newStatus: UserStatus`
- [ ] All are readonly value classes

---

### T-012: UserRepositoryInterface

**Phase:** Domain · **Complexity:** S · **Dependencies:** T-006, T-007, T-009

**File:** `backend/src/Shared/Auth/Domain/Repositories/UserRepositoryInterface.php`

**Acceptance Criteria:**
- [ ] `getById(UserId $id): User` — throws `UserNotFoundException` if not found
- [ ] `findByEmail(UserEmail $email): ?User`
- [ ] `save(User $user): void`
- [ ] `softDelete(UserId $id): void`
- [ ] All signatures use domain Value Objects (not primitives)

---

### T-013: ReadModels

**Phase:** Domain · **Complexity:** S · **Dependencies:** T-005, T-006

**Files:**
- `backend/src/Shared/Auth/Domain/ReadModels/AuthTokenRM.php`
- `backend/src/Shared/Auth/Domain/ReadModels/AuthUserRM.php`

**Acceptance Criteria:**
- [ ] `AuthTokenRM`: `readonly` properties: `userId: UserId`, `token: string`, `expiresAt: \DateTimeImmutable`, `role: UserRole`, `mustChangePassword: bool`
- [ ] `AuthUserRM`: `readonly` properties: `id: UserId`, `email: string`, `role: UserRole`, `status: UserStatus`, `mustChangePassword: bool`
- [ ] Both are pure data classes, no business logic

---

## Phase 2: Infrastructure Layer

### T-014: Database Migrations (5 tables)

**Phase:** Infrastructure · **Complexity:** M · **Dependencies:** T-009

**Files:**
- `backend/database/migrations/001_create_users_table.php`
- `backend/database/migrations/002_create_members_table.php`
- `backend/database/migrations/003_create_staff_table.php`
- `backend/database/migrations/004_create_membership_plans_table.php`
- `backend/database/migrations/005_create_class_types_table.php`

**Acceptance Criteria:**
- [ ] All schemas match exactly the SQL in `design.md`
- [ ] All migrations have working `down()` methods
- [ ] `member_number` is `AUTO_INCREMENT UNIQUE` (not primary key)
- [ ] `price_cents` stores integers (3500 = €35.00) — no `decimal` type
- [ ] Foreign keys: `members.user_id` → `users.id`, `staff.user_id` → `users.id`
- [ ] `php artisan migrate` and `php artisan migrate:rollback` both run cleanly

---

### T-015: Table Constant Classes

**Phase:** Infrastructure · **Complexity:** S · **Dependencies:** T-014

**Files:**
- `backend/src/Shared/Auth/Infrastructure/Tables/UserTable.php`
- `backend/src/Core/Member/Infrastructure/Tables/MemberTable.php`
- `backend/src/Core/Staff/Infrastructure/Tables/StaffTable.php`
- `backend/src/Core/Member/Infrastructure/Tables/MembershipPlanTable.php`
- `backend/src/Core/Class/Infrastructure/Tables/ClassTypeTable.php`

**Acceptance Criteria:**
- [ ] Each has `const TABLE_NAME = 'table_name'`
- [ ] Each has all column names documented as `@property` phpDoc with types
- [ ] Used in all repository queries — never string literals

---

### T-016: UserModel (Eloquent)

**Phase:** Infrastructure · **Complexity:** S · **Dependencies:** T-014, T-015

**File:** `backend/src/Shared/Auth/Infrastructure/Persistence/UserModel.php`

**Acceptance Criteria:**
- [ ] `$table = UserTable::TABLE_NAME`
- [ ] `$incrementing = false`, `$keyType = 'string'`
- [ ] `$fillable` covers all DB columns
- [ ] `$casts`: `deleted_at` → datetime, `must_change_password` → boolean
- [ ] `$hidden = ['password', 'remember_token']`
- [ ] Soft deletes trait enabled

---

### T-017: UserHydrator

**Phase:** Infrastructure · **Complexity:** M · **Dependencies:** T-009, T-016

**File:** `backend/src/Shared/Auth/Infrastructure/Hydrators/UserHydrator.php`

**Acceptance Criteria:**
- [ ] `hydrate(UserModel $model): User` — maps all columns to domain objects
- [ ] `dehydrate(User $user): array` — maps entity back to array for persistence
- [ ] Handles nullable `deletedAt`
- [ ] Uses `UserRole::from()` and `UserStatus::from()` for enum mapping
- [ ] Uses `UserId::fromString()`, `UserEmail`, `HashedPassword` for VOs

---

### T-018: UserRepository

**Phase:** Infrastructure · **Complexity:** M · **Dependencies:** T-012, T-016, T-017

**File:** `backend/src/Shared/Auth/Infrastructure/Repositories/UserRepository.php`

**Acceptance Criteria:**
- [ ] Implements `UserRepositoryInterface`
- [ ] `getById()`: throws `UserNotFoundException` if null
- [ ] `findByEmail()`: case-insensitive search
- [ ] `save()`: uses `updateOrCreate` keyed on `id`
- [ ] `softDelete()`: sets `deleted_at` timestamp
- [ ] All queries use `UserTable::TABLE_NAME` — no string literals
- [ ] Soft-deleted users are excluded from all queries (`whereNull('deleted_at')`)

---

### T-019: Service Provider binding

**Phase:** Infrastructure · **Complexity:** S · **Dependencies:** T-012, T-018

**File:** `backend/app/Providers/AppServiceProvider.php` (or dedicated `AuthServiceProvider.php`)

**Acceptance Criteria:**
- [ ] `UserRepositoryInterface` bound to `UserRepository` in the container
- [ ] All command/query handlers registered
- [ ] Binding verified: `app(UserRepositoryInterface::class)` resolves correctly

---

### T-020: MembershipPlan and ClassType Seeders

**Phase:** Infrastructure · **Complexity:** S · **Dependencies:** T-014

**Files:**
- `backend/database/seeders/MembershipPlanSeeder.php`
- `backend/database/seeders/ClassTypeSeeder.php`
- `backend/database/seeders/DefaultAdminSeeder.php`
- `backend/database/seeders/DatabaseSeeder.php` (orchestrates all)

**Acceptance Criteria:**
- [ ] `MembershipPlanSeeder`: inserts exactly 3 plans from `requirements.md` with correct slugs and `price_cents`
- [ ] `ClassTypeSeeder`: inserts exactly 5 class types with correct slugs
- [ ] `DefaultAdminSeeder`: reads `ADMIN_DEFAULT_PASSWORD` from env; throws if missing
- [ ] Default admin has `must_change_password = true`
- [ ] All seeders are idempotent (`updateOrCreate` on slug/email)
- [ ] `php artisan db:seed` runs all in correct order

---

## Phase 3: Application Layer

### T-021: AuthenticateQuery + Handler

**Phase:** Application · **Complexity:** M · **Dependencies:** T-012, T-013

**Files:**
- `backend/src/Shared/Auth/Application/Queries/Authenticate/AuthenticateQuery.php`
- `backend/src/Shared/Auth/Application/Queries/Authenticate/AuthenticateHandler.php`

**Note:** Architectural exception — this Query generates and stores a Sanctum token (modifies state). Documented per `critical-rules.md`.

**Acceptance Criteria:**
- [ ] `AuthenticateQuery`: `email: string`, `password: string`, `rememberMe: bool`
- [ ] Handler finds user by email via `UserRepositoryInterface`
- [ ] Throws `UserNotFoundException` (mapped to `InvalidCredentialsException`) if email not found — no enumeration
- [ ] Throws `InvalidCredentialsException` if password fails `HashedPassword::verify()`
- [ ] Throws `UserCannotLoginException` with specific status if `!user.canLogin()`
- [ ] Creates Sanctum token: 7-day expiry normally; 365-day if `rememberMe = true`
- [ ] Returns `AuthTokenRM` with token string, expiry, role, and `mustChangePassword`

---

### T-022: LogoutCommand + Handler

**Phase:** Application · **Complexity:** S · **Dependencies:** T-012

**Files:**
- `backend/src/Shared/Auth/Application/Commands/Logout/LogoutCommand.php`
- `backend/src/Shared/Auth/Application/Commands/Logout/LogoutHandler.php`

**Acceptance Criteria:**
- [ ] `LogoutCommand`: `userId: UserId`, `tokenId: string`
- [ ] Handler revokes the specific Sanctum token (not all tokens)
- [ ] Returns `void`

---

### T-023: ChangePasswordCommand + Handler

**Phase:** Application · **Complexity:** M · **Dependencies:** T-009, T-012

**Files:**
- `backend/src/Shared/Auth/Application/Commands/ChangePassword/ChangePasswordCommand.php`
- `backend/src/Shared/Auth/Application/Commands/ChangePassword/ChangePasswordHandler.php`

**Acceptance Criteria:**
- [ ] `ChangePasswordCommand`: `userId: UserId`, `currentPassword: string`, `newPassword: string`
- [ ] Handler validates `currentPassword` via `HashedPassword::verify()`
- [ ] Throws `WrongCurrentPasswordException` if current is wrong
- [ ] Throws `WeakPasswordException` if new password < 8 chars
- [ ] Validates new password is not the same as current
- [ ] Calls `user.changePassword()` and `user.clearPasswordChangeFlag()`
- [ ] Saves via repository
- [ ] Returns `void`

---

### T-024: GetAuthenticatedUserQuery + Handler

**Phase:** Application · **Complexity:** S · **Dependencies:** T-012, T-013

**Files:**
- `backend/src/Shared/Auth/Application/Queries/GetAuthenticatedUser/GetAuthenticatedUserQuery.php`
- `backend/src/Shared/Auth/Application/Queries/GetAuthenticatedUser/GetAuthenticatedUserHandler.php`

**Acceptance Criteria:**
- [ ] `GetAuthenticatedUserQuery`: `userId: UserId`
- [ ] Returns `AuthUserRM`
- [ ] Throws `UserNotFoundException` if user doesn't exist

---

## Phase 4: HTTP Layer (Backend)

### T-025: Role Middleware

**Phase:** HTTP · **Complexity:** S · **Dependencies:** T-005

**Files:**
- `backend/app/Http/Middleware/RequireAdminRole.php`
- `backend/app/Http/Middleware/RequireCoachRole.php`
- `backend/app/Http/Middleware/RequireMemberRole.php`
- `backend/app/Http/Middleware/RequireStaffRole.php` (admin OR coach)

**Acceptance Criteria:**
- [ ] Each checks `auth()->user()->role`
- [ ] Returns 403 JSON `{"error": "Forbidden", "code": "INSUFFICIENT_ROLE"}` on mismatch
- [ ] `RequireStaffRole` allows both `admin` and `coach`
- [ ] All registered in `bootstrap/app.php` middleware aliases

---

### T-026: ForcePasswordChangeMiddleware

**Phase:** HTTP · **Complexity:** S · **Dependencies:** T-009

**File:** `backend/app/Http/Middleware/ForcePasswordChange.php`

**Acceptance Criteria:**
- [ ] Applied after `auth:sanctum` on all protected routes
- [ ] Exempts `PUT /api/auth/password` itself
- [ ] If `user.mustChangePassword() === true` → returns 403 JSON `{"error": "Password change required", "code": "MUST_CHANGE_PASSWORD"}`
- [ ] Frontend intercepts `MUST_CHANGE_PASSWORD` code and redirects to `/cambiar-contrasena`

---

### T-027: LoginRequest and ChangePasswordRequest

**Phase:** HTTP · **Complexity:** S · **Dependencies:** None

**Files:**
- `backend/app/Http/Actions/Auth/Login/LoginRequest.php` + `LoginDto.php`
- `backend/app/Http/Actions/Auth/ChangePassword/ChangePasswordRequest.php` + `ChangePasswordDto.php`

**Acceptance Criteria:**
- [ ] `LoginRequest::getDto()` returns `LoginDto` — NO `rules()` method
- [ ] `LoginDto`: `email: string`, `password: string`, `rememberMe: bool`
- [ ] `ChangePasswordRequest::getDto()` returns `ChangePasswordDto`
- [ ] `ChangePasswordDto`: `currentPassword: string`, `newPassword: string`, `newPasswordConfirmation: string`
- [ ] Both use helper methods for type-safe field access (no `$request->input()` directly)

---

### T-028: LoginAction

**Phase:** HTTP · **Complexity:** S · **Dependencies:** T-021, T-027, T-029

**File:** `backend/app/Http/Actions/Auth/Login/LoginAction.php`

**Acceptance Criteria:**
- [ ] Max 20 lines
- [ ] Dispatches `AuthenticateQuery` via QueryBus
- [ ] Returns `AuthResource`
- [ ] Catches domain exceptions and maps to HTTP errors:
  - `InvalidCredentialsException` → 401 `{"error": "Credenciales incorrectas"}`
  - `UserCannotLoginException` → 403 with status-specific message

---

### T-029: AuthResource

**Phase:** HTTP · **Complexity:** S · **Dependencies:** T-013

**File:** `backend/app/Http/Actions/Auth/Shared/AuthResource.php`

**Response shape:**
```json
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
```

**Acceptance Criteria:**
- [ ] All fields from `AuthTokenRM`
- [ ] `expires_at` in ISO 8601 UTC format
- [ ] `role` as string (enum value)

---

### T-030: LogoutAction

**Phase:** HTTP · **Complexity:** S · **Dependencies:** T-022

**File:** `backend/app/Http/Actions/Auth/Logout/LogoutAction.php`

**Acceptance Criteria:**
- [ ] Dispatches `LogoutCommand` with current user ID and token ID
- [ ] Returns 204 No Content

---

### T-031: ChangePasswordAction

**Phase:** HTTP · **Complexity:** S · **Dependencies:** T-023, T-027

**File:** `backend/app/Http/Actions/Auth/ChangePassword/ChangePasswordAction.php`

**Acceptance Criteria:**
- [ ] Dispatches `ChangePasswordCommand`
- [ ] Validates that `newPassword === newPasswordConfirmation` (HTTP-level check, not business rule)
- [ ] Returns 204 No Content on success
- [ ] Catches: `WrongCurrentPasswordException` → 422, `WeakPasswordException` → 422

---

### T-032: GetCurrentUserAction and CurrentUserResource

**Phase:** HTTP · **Complexity:** S · **Dependencies:** T-024

**Files:**
- `backend/app/Http/Actions/Auth/Me/GetCurrentUserAction.php`
- `backend/app/Http/Actions/Auth/Me/CurrentUserResource.php`

**Acceptance Criteria:**
- [ ] `GET /api/auth/me` returns current user data
- [ ] `CurrentUserResource` shape: `id`, `email`, `role`, `status`, `must_change_password`

---

### T-033: AuthController and Routes

**Phase:** HTTP · **Complexity:** S · **Dependencies:** T-028, T-030, T-031, T-032

**Files:**
- `backend/app/Http/Controllers/Auth/AuthController.php`
- `backend/routes/api.php`

**Routes:**
```
POST   /api/auth/login          throttle:5,1
POST   /api/auth/logout         auth:sanctum
PUT    /api/auth/password       auth:sanctum, force.password.change exempt
GET    /api/auth/me             auth:sanctum, force.password.change
```

**Acceptance Criteria:**
- [ ] Rate limiting on login: 5 attempts/minute/IP → 429 response
- [ ] All other auth routes require `auth:sanctum`
- [ ] `PUT /api/auth/password` is exempt from `ForcePasswordChange` middleware
- [ ] All other protected routes have `ForcePasswordChange` applied

---

### T-034: API error response format

**Phase:** HTTP · **Complexity:** S · **Dependencies:** None

**File:** `backend/app/Exceptions/Handler.php`

**Acceptance Criteria:**
- [ ] All domain exceptions produce JSON error responses (never HTML)
- [ ] Format: `{"error": "message", "code": "ERROR_CODE"}`
- [ ] `DomainException` → 422 by default
- [ ] `NotFoundException` subclasses → 404
- [ ] Unauthenticated → 401 JSON (not redirect)
- [ ] Unauthorized → 403 JSON

---

## Phase 5: Frontend (React SPA)

### T-035: Axios service with 401 interceptor

**Phase:** Frontend · **Complexity:** S · **Dependencies:** T-033

**File:** `frontend/src/services/api.ts`

**Acceptance Criteria:**
- [ ] Axios instance with `baseURL` from `VITE_API_URL` env variable
- [ ] Request interceptor: adds `Authorization: Bearer {token}` from localStorage
- [ ] Response interceptor: on 401 → clears token, shows toast "Tu sesion ha expirado...", redirects to `/login`
- [ ] Response interceptor: on 403 with `code === 'MUST_CHANGE_PASSWORD'` → redirects to `/cambiar-contrasena`
- [ ] Exported as singleton

---

### T-036: AuthContext and useAuth hook

**Phase:** Frontend · **Complexity:** M · **Dependencies:** T-035

**Files:**
- `frontend/src/contexts/AuthContext.tsx`
- `frontend/src/hooks/useAuth.ts`

**Acceptance Criteria:**
- [ ] Context exposes: `user`, `isAuthenticated`, `isLoading`, `login()`, `logout()`
- [ ] `login(email, password, rememberMe)` calls `POST /api/auth/login`, stores token in `localStorage`
- [ ] `logout()` calls `POST /api/auth/logout`, clears localStorage, redirects to `/login`
- [ ] On app load: reads token from `localStorage`, calls `GET /api/auth/me` to restore session
- [ ] `isLoading = true` while restoring session (prevents flash of login page)

---

### T-037: LoginPage

**Phase:** Frontend · **Complexity:** M · **Dependencies:** T-036

**File:** `frontend/src/pages/auth/LoginPage.tsx`

**Acceptance Criteria:**
- [ ] Fields: email, password, "Recuerdame" checkbox
- [ ] Uses brand colors: dark bg `#0f172a`, primary button `#2563eb`
- [ ] Shows gym logo (placeholder) and tagline "Donde los guerreros se forjan"
- [ ] On submit: calls `login()` from `useAuth`
- [ ] Shows inline error message on login failure (does not clear password)
- [ ] Submit button disabled and shows spinner while loading
- [ ] Redirects to role-specific dashboard on success

---

### T-038: ChangePasswordPage (forced)

**Phase:** Frontend · **Complexity:** S · **Dependencies:** T-036

**File:** `frontend/src/pages/auth/ChangePasswordPage.tsx`

**Acceptance Criteria:**
- [ ] Fields: current password, new password, confirm new password
- [ ] Shown automatically when `user.mustChangePassword === true`
- [ ] Navigation is blocked — user cannot go elsewhere until complete
- [ ] On success: updates user context and redirects to role dashboard
- [ ] Informational message: "Por seguridad, debes cambiar tu contrasena antes de continuar."

---

### T-039: Route guards and React Router config

**Phase:** Frontend · **Complexity:** M · **Dependencies:** T-036

**Files:**
- `frontend/src/router/ProtectedRoute.tsx`
- `frontend/src/router/RoleRoute.tsx`
- `frontend/src/router/index.tsx`

**Acceptance Criteria:**
- [ ] `ProtectedRoute`: redirects to `/login` if not authenticated
- [ ] `RoleRoute`: redirects to role dashboard if wrong role (e.g. member tries to access `/admin`)
- [ ] Route tree matches design: `/login`, `/cambiar-contrasena`, `/admin/dashboard`, `/entrenador/dashboard`, `/socio/dashboard`
- [ ] Lazy-loaded route components (code splitting)

---

### T-040: Placeholder dashboards

**Phase:** Frontend · **Complexity:** S · **Dependencies:** T-039

**Files:**
- `frontend/src/pages/admin/AdminDashboard.tsx`
- `frontend/src/pages/coach/CoachDashboard.tsx`
- `frontend/src/pages/member/MemberDashboard.tsx`

**Acceptance Criteria:**
- [ ] Each shows role-appropriate greeting: "Bienvenido, Admin" / "Bienvenido, Entrenador" / "Bienvenido, Socio"
- [ ] Each shows a logout button that calls `logout()` from `useAuth`
- [ ] Styled with brand colors using Tailwind
- [ ] Not a blank white page — minimal but complete shell

---

### T-041: Frontend environment config

**Phase:** Frontend · **Complexity:** S · **Dependencies:** T-003

**Files:** `frontend/.env.example`, `frontend/.env`

**Acceptance Criteria:**
- [ ] `VITE_API_URL=http://localhost:8000` in `.env.example`
- [ ] `.env` is in `.gitignore`

---

## Phase 6: Tests

### T-042: Unit tests — User entity

**Phase:** Tests · **Complexity:** M · **Dependencies:** T-009

**File:** `backend/tests/Unit/Shared/Auth/UserTest.php`

**Scenarios:**
- [ ] `create()` produces active user with correct properties
- [ ] `activate()` from inactive succeeds
- [ ] `deactivate()` from active succeeds
- [ ] `suspend()` only works for members
- [ ] `suspend()` on admin throws `InvalidStatusTransitionException`
- [ ] `approve()` from `pending_approval` succeeds
- [ ] `reject()` from `pending_approval` → inactive
- [ ] `canLogin()` returns false for inactive/suspended/pending
- [ ] `changePassword()` updates hash correctly
- [ ] `mustChangePassword()` flag cleared by `clearPasswordChangeFlag()`

---

### T-043: Unit tests — AuthenticateHandler

**Phase:** Tests · **Complexity:** M · **Dependencies:** T-021

**File:** `backend/tests/Unit/Shared/Auth/AuthenticateHandlerTest.php`

**Scenarios:**
- [ ] Valid credentials + active user → returns `AuthTokenRM`
- [ ] Wrong password → `InvalidCredentialsException`
- [ ] Email not found → `InvalidCredentialsException` (same exception, no enumeration)
- [ ] Valid credentials + inactive user → `UserCannotLoginException`
- [ ] Valid credentials + suspended user → `UserCannotLoginException`
- [ ] Valid credentials + pending_approval user → `UserCannotLoginException`
- [ ] `rememberMe = true` → token expiry is 365 days
- [ ] `rememberMe = false` → token expiry is 7 days

---

### T-044: Unit tests — ChangePasswordHandler

**Phase:** Tests · **Complexity:** S · **Dependencies:** T-023

**File:** `backend/tests/Unit/Shared/Auth/ChangePasswordHandlerTest.php`

**Scenarios:**
- [ ] Correct current password + strong new password → success
- [ ] Wrong current password → `WrongCurrentPasswordException`
- [ ] New password too short (< 8 chars) → `WeakPasswordException`
- [ ] New password same as current → `WeakPasswordException`
- [ ] `must_change_password` is cleared after successful change

---

### T-045: Integration tests — Login endpoint

**Phase:** Tests · **Complexity:** M · **Dependencies:** T-028, T-033

**File:** `backend/tests/Feature/Auth/LoginTest.php`

**Scenarios:**
- [ ] Valid credentials → 200 with token and user data
- [ ] Wrong password → 401 `{"error": "Credenciales incorrectas"}`
- [ ] Unknown email → 401 same message (no enumeration)
- [ ] Inactive user → 403 with inactive message
- [ ] Pending user → 403 with pending message
- [ ] 6th attempt in 1 minute → 429 (rate limit)
- [ ] `remember_me: true` → token has 365-day expiry
- [ ] `must_change_password: true` user → login succeeds but response includes `must_change_password: true`

---

### T-046: Integration tests — Role guards and ForcePasswordChange

**Phase:** Tests · **Complexity:** M · **Dependencies:** T-025, T-026, T-033

**File:** `backend/tests/Feature/Auth/RoleGuardTest.php`

**Scenarios:**
- [ ] Member token accessing `/api/admin/...` → 403
- [ ] Coach token accessing `/api/admin/...` → 403
- [ ] Admin token accessing `/api/admin/...` → 200
- [ ] No token accessing any protected route → 401
- [ ] User with `must_change_password = true` accessing any route except `PUT /api/auth/password` → 403 `MUST_CHANGE_PASSWORD`
- [ ] User with `must_change_password = true` calling `PUT /api/auth/password` → 200 (exempt)

---

### T-047: Integration tests — Logout and password change

**Phase:** Tests · **Complexity:** S · **Dependencies:** T-030, T-031

**File:** `backend/tests/Feature/Auth/LogoutAndPasswordTest.php`

**Scenarios:**
- [ ] `POST /api/auth/logout` → token revoked; subsequent request with same token → 401
- [ ] `PUT /api/auth/password` with correct current password → 204; new password works on login
- [ ] `PUT /api/auth/password` with wrong current password → 422
- [ ] `PUT /api/auth/password` with short new password → 422
- [ ] `PUT /api/auth/password` with mismatching confirmation → 422

---

### T-048: Seed verification test

**Phase:** Tests · **Complexity:** S · **Dependencies:** T-020

**File:** `backend/tests/Feature/SeederTest.php`

**Scenarios:**
- [ ] After `db:seed`: 3 membership plans exist with correct slugs and prices
- [ ] After `db:seed`: 5 class types exist with correct slugs
- [ ] After `db:seed`: default admin user exists with `must_change_password = true`
- [ ] Seeder is idempotent: running twice doesn't create duplicates

---

## Final Checklist

- [ ] All 58 tasks completed
- [ ] `make up && make fresh` runs cleanly from scratch
- [ ] `make test` passes all 8 test files
- [ ] Login flow works end-to-end in the browser (admin, coach, member)
- [ ] Forced password change flow works end-to-end
- [ ] 401 "session expired" toast appears when token is cleared manually
- [ ] Role guard blocks wrong-role access in the frontend
- [ ] No hardcoded credentials anywhere in the codebase
- [ ] Code reviewed before merge
