# Implementation Tasks: Class Schedule Management (epic-classes)

**Requirement:** `docs/working_docs/epics/epic-classes/requirements.md`
**Solution Design:** `docs/working_docs/epics/epic-classes/design.md`
**Created:** 2026-06-11
**Total Tasks:** 35
**Estimated Complexity:** XL

## Summary

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Domain | 9 | S–M |
| Infrastructure | 5 | S–M |
| Application – Commands | 5 | S–M |
| Application – Queries | 4 | S–M |
| HTTP Layer | 4 | M–L |
| Tests | 4 | M |
| Collateral | 4 | S |

---

## Phase 1: Domain Layer

### TASK-001: Create DayOfWeek Enum

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the `DayOfWeek` backed enum with weekday values (Monday–Friday) and a `sortOrder()` helper for display ordering.

**File:** `src/Core/ClassSession/Domain/Enums/DayOfWeek.php`

**Acceptance Criteria:**
- [ ] Backed enum with values: `monday`, `tuesday`, `wednesday`, `thursday`, `friday`
- [ ] `sortOrder(): int` method returns 1–5 for Mon–Fri
- [ ] No Saturday/Sunday values (prevents weekend sessions at enum level)

---

### TASK-002: Create ClassSessionStatus Enum

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the `ClassSessionStatus` backed enum with `active` and `cancelled` values.

**File:** `src/Core/ClassSession/Domain/Enums/ClassSessionStatus.php`

**Acceptance Criteria:**
- [ ] Backed enum with values: `active`, `cancelled`

---

### TASK-003: Create ClassSessionId Value Object

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the `ClassSessionId` VO extending `Ulid`, following the same pattern as `UserId` in `Shared/Auth`.

**File:** `src/Core/ClassSession/Domain/ValueObjects/ClassSessionId.php`

**Acceptance Criteria:**
- [ ] Extends `Symfony\Component\Uid\Ulid`
- [ ] `random(): static` factory method
- [ ] `fromString(string $value): static` factory method
- [ ] `value(): string` returns base32 representation

---

### TASK-004: Create ClassTypeId Value Object

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the `ClassTypeId` VO extending `Ulid` — cross-BC reference to a ClassType entity.

**File:** `src/Core/ClassSession/Domain/ValueObjects/ClassTypeId.php`

**Acceptance Criteria:**
- [ ] Extends `Symfony\Component\Uid\Ulid`
- [ ] `random(): static` factory method
- [ ] `fromString(string $value): static` factory method
- [ ] `value(): string` returns base32 representation

---

### TASK-005: Create TimeSlot Value Object

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the `TimeSlot` VO that validates the input against the 7 fixed gym time slots. Creates `InvalidTimeSlotException` in the same task.

**Files:**
- `src/Core/ClassSession/Domain/ValueObjects/TimeSlot.php`
- `src/Core/ClassSession/Domain/Exceptions/InvalidTimeSlotException.php`

**Acceptance Criteria:**
- [ ] `readonly` class with `public readonly string $value`
- [ ] Constructor validates against `['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15']`
- [ ] Throws `InvalidTimeSlotException` on invalid input
- [ ] `static validValues(): array` method
- [ ] `InvalidTimeSlotException` extends `\DomainException` or `\InvalidArgumentException`

---

### TASK-006: Create Remaining Domain Exceptions

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create all domain exceptions for the `ClassSession` bounded context (except `InvalidTimeSlotException` created in TASK-005).

**Files (one class per file):**
- `src/Core/ClassSession/Domain/Exceptions/ClassSessionNotFoundException.php` → 404
- `src/Core/ClassSession/Domain/Exceptions/InvalidCapacityException.php` → 422
- `src/Core/ClassSession/Domain/Exceptions/SessionAlreadyCancelledException.php` → 422
- `src/Core/ClassSession/Domain/Exceptions/SessionNotCancelledException.php` → 422
- `src/Core/ClassSession/Domain/Exceptions/CoachConflictException.php` → 409
- `src/Core/ClassSession/Domain/Exceptions/ClassTypeNotFoundException.php` → 422
- `src/Core/ClassSession/Domain/Exceptions/CoachNotFoundException.php` → 422
- `src/Core/ClassSession/Domain/Exceptions/SessionHasBookingsException.php` → 409
- `src/Core/ClassSession/Domain/Exceptions/WeekendSessionNotAllowedException.php` → 422

**Acceptance Criteria:**
- [ ] Each exception class created in the correct file path
- [ ] Each extends an appropriate base exception (`\DomainException`, `\RuntimeException`, etc.)
- [ ] Exception messages are descriptive

---

### TASK-007: Create ClassSession Entity

**Phase:** Domain
**Complexity:** M
**Dependencies:** TASK-001, TASK-002, TASK-003, TASK-004, TASK-005, TASK-006

**Description:**
Create the main `ClassSession` aggregate with all state transition methods, a static `create()` factory, and `update()`.

**File:** `src/Core/ClassSession/Domain/Entities/ClassSession.php`

**Acceptance Criteria:**
- [ ] All constructor properties from design: `id`, `classTypeId`, `coachId` (nullable), `dayOfWeek`, `timeSlot`, `maxCapacity`, `status`, `createdAt`, `deletedAt`
- [ ] `readonly` properties for immutable fields (`id`, `classTypeId`, `dayOfWeek`, `timeSlot`, `createdAt`); `coachId` immutable too
- [ ] `static create(...)` factory — throws `InvalidCapacityException` if `maxCapacity < 1`, sets `status = active`
- [ ] `update(classTypeId, coachId, maxCapacity): void` — throws `InvalidCapacityException` if `maxCapacity < 1`
- [ ] `cancel(): void` — throws `SessionAlreadyCancelledException` if already cancelled
- [ ] `restore(): void` — throws `SessionNotCancelledException` if not cancelled
- [ ] `softDelete(): void` — sets `deletedAt` to current datetime
- [ ] `status(): ClassSessionStatus` accessor
- [ ] `deletedAt(): ?\DateTimeImmutable` accessor
- [ ] `isDeleted(): bool` accessor
- [ ] No getters/setters for other fields (public readonly properties)

---

### TASK-008: Create ClassSessionRM Read Model

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-001, TASK-002, TASK-003

**Description:**
Create the denormalized read model `ClassSessionRM` used by all query handlers. It includes joined class_type and coach fields.

**File:** `src/Core/ClassSession/Domain/ReadModels/ClassSessionRM.php`

**Acceptance Criteria:**
- [ ] `final readonly class` with all properties from design
- [ ] Properties: `id`, `classTypeId`, `classTypeName`, `classTypeSlug`, `classTypeColor` (nullable), `coachId` (nullable), `coachEmail` (nullable), `dayOfWeek`, `timeSlot`, `maxCapacity`, `status`, `createdAt`
- [ ] Constructor injection only, no setters

---

### TASK-009: Create ClassSessionRepositoryInterface

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-007, TASK-008

**Description:**
Create the repository interface in the Domain layer with all methods needed by commands and queries.

**File:** `src/Core/ClassSession/Domain/Repositories/ClassSessionRepositoryInterface.php`

**Acceptance Criteria:**
- [ ] `getById(ClassSessionId $id): ClassSession` — throws `ClassSessionNotFoundException`
- [ ] `findById(ClassSessionId $id): ?ClassSession`
- [ ] `findAll(?DayOfWeek $day, ?UserId $coachId, ?ClassSessionStatus $status): array`
- [ ] `findByCoach(UserId $coachId): array`
- [ ] `findWeeklySchedule(): array` — returns `ClassSessionRM[]`
- [ ] `hasCoachConflict(UserId $coachId, DayOfWeek $day, TimeSlot $slot, ?ClassSessionId $excludeId): bool`
- [ ] `save(ClassSession $session): void`
- [ ] `softDelete(ClassSessionId $id): void`
- [ ] All signatures use domain value objects / enums (no primitives)

---

## Phase 2: Infrastructure Layer

### TASK-010: Create ClassSessionTable Constants

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** None

**Description:**
Create the table constants class following the existing `ClassTypeTable` pattern.

**File:** `src/Core/ClassSession/Infrastructure/Tables/ClassSessionTable.php`

**Acceptance Criteria:**
- [ ] `final class` with `TABLE_NAME = 'class_sessions'`
- [ ] Constants for all columns: `ID`, `CLASS_TYPE_ID`, `COACH_ID`, `DAY_OF_WEEK`, `TIME_SLOT`, `MAX_CAPACITY`, `STATUS`, `CREATED_AT`, `UPDATED_AT`, `DELETED_AT`

---

### TASK-011: Create ClassSessions Migration

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-010

**Description:**
Create the database migration for `class_sessions` table with FK to `class_types` and nullable FK to `users`, soft deletes, and composite unique key.

**File:** `database/migrations/2026_06_11_000005_create_class_sessions_table.php`

**Acceptance Criteria:**
- [ ] All columns from design: `id` CHAR(26) PK, `class_type_id`, `coach_id` (nullable), `day_of_week` ENUM, `time_slot` VARCHAR(5), `max_capacity` INT UNSIGNED DEFAULT 20, `status` ENUM DEFAULT 'active', timestamps, `deleted_at`
- [ ] `UNIQUE KEY uq_day_slot_type (day_of_week, time_slot, class_type_id)`
- [ ] Indexes on `coach_id`, `status`, `deleted_at`
- [ ] FK `class_type_id → class_types(id)`
- [ ] FK `coach_id → users(id) ON DELETE SET NULL`
- [ ] Reversible `down()` method

---

### TASK-012: Create ClassSessionModel

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-010, TASK-011

**Description:**
Create the Eloquent ORM model for `class_sessions` with soft deletes.

**File:** `src/Core/ClassSession/Infrastructure/Persistence/ClassSessionModel.php`

**Acceptance Criteria:**
- [ ] Extends `Illuminate\Database\Eloquent\Model`
- [ ] Uses `SoftDeletes` trait
- [ ] `$table = ClassSessionTable::TABLE_NAME`
- [ ] `$primaryKey = ClassSessionTable::ID`
- [ ] `$incrementing = false`, `$keyType = 'string'`
- [ ] `$fillable` lists all columns (except auto-timestamps)
- [ ] `$casts` includes `deleted_at => 'datetime'`

---

### TASK-013: Create ClassSessionHydrator

**Phase:** Infrastructure
**Complexity:** M
**Dependencies:** TASK-007, TASK-012

**Description:**
Create the hydrator to convert between `ClassSessionModel` and `ClassSession` entity.

**File:** `src/Core/ClassSession/Infrastructure/Hydrators/ClassSessionHydrator.php`

**Acceptance Criteria:**
- [ ] `hydrate(ClassSessionModel $model): ClassSession` maps all columns to VOs/enums
- [ ] `dehydrate(ClassSession $session): array` returns array suitable for `updateOrCreate`
- [ ] `hydrate` correctly handles nullable `coach_id` and `deleted_at`
- [ ] All VO/enum conversions: `ClassSessionId::fromString()`, `ClassTypeId::fromString()`, `UserId::fromString()` or null, `DayOfWeek::from()`, `new TimeSlot()`, `ClassSessionStatus::from()`, `\DateTimeImmutable` for dates

---

### TASK-014: Create ClassSessionRepository

**Phase:** Infrastructure
**Complexity:** M
**Dependencies:** TASK-009, TASK-012, TASK-013

**Description:**
Create the repository implementation. Query handlers that return `ClassSessionRM` must use JOIN queries — never N+1.

**File:** `src/Core/ClassSession/Infrastructure/Repositories/ClassSessionRepository.php`

**Acceptance Criteria:**
- [ ] Implements `ClassSessionRepositoryInterface`
- [ ] Constructor receives `ClassSessionHydrator`
- [ ] `getById()` throws `ClassSessionNotFoundException` when not found
- [ ] `findAll()` builds single query with optional `WHERE` clauses (day, coach, status), returns hydrated `ClassSession[]`
- [ ] `findWeeklySchedule()` uses single JOIN query (class_types + users LEFT JOIN), returns `ClassSessionRM[]` ordered by day sort order then time_slot
- [ ] `hasCoachConflict()` uses `.exists()` query excluding `$excludeId` when provided
- [ ] `save()` uses `ClassSessionModel::query()->updateOrCreate()`
- [ ] `softDelete()` sets `deleted_at` via direct update
- [ ] No queries inside loops

---

## Phase 3: Application Layer – Commands

### TASK-015: Create CreateClassSession Command + Handler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-009, TASK-007

**Description:**
Create the command and handler for creating a new class session, including cross-BC existence checks for class type and coach.

**Files:**
- `src/Core/ClassSession/Application/Commands/CreateClassSession/CreateClassSessionCommand.php`
- `src/Core/ClassSession/Application/Commands/CreateClassSession/CreateClassSessionHandler.php`

**Acceptance Criteria:**
- [ ] Command implements `CommandInterface`, all fields readonly: `id`, `classTypeId`, `coachId` (nullable), `dayOfWeek`, `timeSlot`, `maxCapacity`
- [ ] Handler verifies `classTypeId` exists — via `ClassTypeRepositoryInterface` or direct `ClassTypeModel` query (document exception if using model)
- [ ] Handler verifies coach exists with `role = coach` when `coachId` is not null — via `UserRepositoryInterface`
- [ ] Handler checks `repository->hasCoachConflict()` when `coachId` is not null — throws `CoachConflictException`
- [ ] Handler calls `ClassSession::create()` then `repository->save()`
- [ ] Handler returns `void` (CQRS rule)

---

### TASK-016: Create UpdateClassSession Command + Handler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-009, TASK-007

**Description:**
Create the command and handler for updating `classTypeId`, `coachId`, and `maxCapacity` of an existing session. `dayOfWeek` and `timeSlot` are immutable after creation.

**Files:**
- `src/Core/ClassSession/Application/Commands/UpdateClassSession/UpdateClassSessionCommand.php`
- `src/Core/ClassSession/Application/Commands/UpdateClassSession/UpdateClassSessionHandler.php`

**Acceptance Criteria:**
- [ ] Command fields: `id`, `classTypeId`, `coachId` (nullable), `maxCapacity`
- [ ] Handler loads session via `repository->getById()` — throws `ClassSessionNotFoundException` if not found
- [ ] Handler verifies new `classTypeId` exists
- [ ] Handler verifies new coach (if set) exists with `role = coach`
- [ ] Handler checks `repository->hasCoachConflict()` passing `excludeId` when coach changes
- [ ] Handler calls `session->update()` then `repository->save()`
- [ ] Returns `void`

---

### TASK-017: Create CancelClassSession + RestoreClassSession Commands + Handlers

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-009, TASK-007

**Description:**
Create cancel and restore commands and handlers — simple state transitions.

**Files:**
- `src/Core/ClassSession/Application/Commands/CancelClassSession/CancelClassSessionCommand.php`
- `src/Core/ClassSession/Application/Commands/CancelClassSession/CancelClassSessionHandler.php`
- `src/Core/ClassSession/Application/Commands/RestoreClassSession/RestoreClassSessionCommand.php`
- `src/Core/ClassSession/Application/Commands/RestoreClassSession/RestoreClassSessionHandler.php`

**Acceptance Criteria:**
- [ ] Both commands have a single field: `id: ClassSessionId`
- [ ] Cancel handler calls `session->cancel()` — propagates `SessionAlreadyCancelledException`
- [ ] Restore handler calls `session->restore()` — propagates `SessionNotCancelledException`
- [ ] Both save via `repository->save()` and return `void`

---

### TASK-018: Create DeleteClassSession Command + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-009, TASK-007

**Description:**
Create the soft-delete command and handler. Guard: session cannot be deleted if it has bookings (future epic-booking integration point).

**Files:**
- `src/Core/ClassSession/Application/Commands/DeleteClassSession/DeleteClassSessionCommand.php`
- `src/Core/ClassSession/Application/Commands/DeleteClassSession/DeleteClassSessionHandler.php`

**Acceptance Criteria:**
- [ ] Command has single field: `id: ClassSessionId`
- [ ] Handler loads session via `repository->getById()`
- [ ] Handler throws `SessionHasBookingsException` if bookings exist (for MVP: always false / skip check; comment that booking check goes here)
- [ ] Handler calls `repository->softDelete($id)`
- [ ] Returns `void`

---

## Phase 4: Application Layer – Queries

### TASK-019: Create GetClassSessionById Query + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-009, TASK-008

**Description:**
Query to retrieve a single session by ID, returning a denormalized `ClassSessionRM`.

**Files:**
- `src/Core/ClassSession/Application/Queries/GetClassSessionById/GetClassSessionByIdQuery.php`
- `src/Core/ClassSession/Application/Queries/GetClassSessionById/GetClassSessionByIdHandler.php`

**Acceptance Criteria:**
- [ ] Query field: `id: ClassSessionId`
- [ ] Handler calls a repository method that returns `ClassSessionRM` for the given ID (add `getByIdRM` method to interface if needed, or reuse `findWeeklySchedule` filtered)
- [ ] Throws `ClassSessionNotFoundException` if not found
- [ ] Returns `ClassSessionRM`

---

### TASK-020: Create ListClassSessions Query + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-009, TASK-008

**Description:**
Query to list all sessions with optional filters (day, coach, status). Returns `ClassSessionRM[]`.

**Files:**
- `src/Core/ClassSession/Application/Queries/ListClassSessions/ListClassSessionsQuery.php`
- `src/Core/ClassSession/Application/Queries/ListClassSessions/ListClassSessionsHandler.php`

**Acceptance Criteria:**
- [ ] Query fields: `dayOfWeek: ?DayOfWeek`, `coachId: ?UserId`, `status: ?ClassSessionStatus`
- [ ] Handler delegates to `repository->findAll()`, returns `ClassSessionRM[]`
- [ ] No queries inside handler

---

### TASK-021: Create GetWeeklySchedule Query + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-009, TASK-008

**Description:**
Public query returning all active sessions for the weekly schedule view.

**Files:**
- `src/Core/ClassSession/Application/Queries/GetWeeklySchedule/GetWeeklyScheduleQuery.php`
- `src/Core/ClassSession/Application/Queries/GetWeeklySchedule/GetWeeklyScheduleHandler.php`

**Acceptance Criteria:**
- [ ] Query has no fields (returns full schedule)
- [ ] Handler calls `repository->findWeeklySchedule()`, returns `ClassSessionRM[]`
- [ ] Result ordered by day sort order then time_slot (handled in repository)

---

### TASK-022: Create GetCoachSessions Query + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-009, TASK-008

**Description:**
Query for a coach to retrieve their own assigned sessions.

**Files:**
- `src/Core/ClassSession/Application/Queries/GetCoachSessions/GetCoachSessionsQuery.php`
- `src/Core/ClassSession/Application/Queries/GetCoachSessions/GetCoachSessionsHandler.php`

**Acceptance Criteria:**
- [ ] Query field: `coachId: UserId`
- [ ] Handler calls `repository->findByCoach()`, returns `ClassSessionRM[]`

---

## Phase 5: HTTP Layer

### TASK-023: Create DTOs and Request Classes

**Phase:** HTTP
**Complexity:** S
**Dependencies:** None

**Description:**
Create the DTOs and Form Request classes for create, update, and list operations. Requests use `getDto()` only — no `rules()`.

**Files:**
- `src/Core/ClassSession/Application/DTOs/CreateClassSessionDto.php`
- `src/Core/ClassSession/Application/DTOs/UpdateClassSessionDto.php`
- `src/Core/ClassSession/Application/DTOs/ListClassSessionsDto.php`
- `app/Http/Actions/ClassSession/Create/CreateClassSessionRequest.php`
- `app/Http/Actions/ClassSession/Update/UpdateClassSessionRequest.php`
- `app/Http/Actions/ClassSession/List/ListClassSessionsRequest.php`

**Acceptance Criteria:**
- [ ] Each DTO is a `readonly` class with typed constructor properties
- [ ] `CreateClassSessionRequest::getDto()` returns `CreateClassSessionDto` with: `classTypeId`, `coachId` (nullable), `dayOfWeek`, `timeSlot`, `maxCapacity`
- [ ] `UpdateClassSessionRequest::getDto()` returns `UpdateClassSessionDto` with: `classTypeId`, `coachId` (nullable), `maxCapacity`
- [ ] `ListClassSessionsRequest::getDto()` returns `ListClassSessionsDto` with: `dayOfWeek` (nullable), `coachId` (nullable), `status` (nullable)
- [ ] No `rules()` method in any request class — only `getDto()` and `authorize(): bool`

---

### TASK-024: Create Resources

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-008

**Description:**
Create the three API resource classes for responses.

**Files:**
- `app/Http/Actions/ClassSession/Shared/ClassSessionResource.php`
- `app/Http/Actions/ClassSession/Shared/ClassSessionListResource.php`
- `app/Http/Actions/ClassSession/Shared/WeeklyScheduleResource.php`

**Acceptance Criteria:**
- [ ] `ClassSessionResource` serializes a single `ClassSessionRM` with shape: `id`, `class_type` (object with id/name/slug/color), `coach` (nullable object with id/email), `day_of_week`, `time_slot`, `max_capacity`, `status`, `created_at`
- [ ] `ClassSessionListResource` wraps an array of `ClassSessionRM` (returns `data: [...]`)
- [ ] `WeeklyScheduleResource` groups sessions by `day_of_week` key, each day array ordered by `time_slot`
- [ ] All value objects converted to primitives (`.value()`, `.format()`)

---

### TASK-025: Create all 9 Action Classes

**Phase:** HTTP
**Complexity:** M
**Dependencies:** TASK-015, TASK-016, TASK-017, TASK-018, TASK-019, TASK-020, TASK-021, TASK-022, TASK-023, TASK-024

**Description:**
Create the 9 thin action classes (≤ 20 lines each). Each action: validates auth, dispatches command or query, returns resource.

**Files:**
- `app/Http/Actions/ClassSession/Create/CreateClassSessionAction.php`
- `app/Http/Actions/ClassSession/Update/UpdateClassSessionAction.php`
- `app/Http/Actions/ClassSession/Delete/DeleteClassSessionAction.php`
- `app/Http/Actions/ClassSession/Cancel/CancelClassSessionAction.php`
- `app/Http/Actions/ClassSession/Restore/RestoreClassSessionAction.php`
- `app/Http/Actions/ClassSession/Get/GetClassSessionAction.php`
- `app/Http/Actions/ClassSession/List/ListClassSessionsAction.php`
- `app/Http/Actions/ClassSession/WeeklySchedule/GetWeeklyScheduleAction.php`
- `app/Http/Actions/ClassSession/Coach/GetCoachSessionsAction.php`

**Acceptance Criteria:**
- [ ] Each action is `final`, injects handlers directly (no bus)
- [ ] `CreateClassSessionAction`: generates `ClassSessionId::random()` before command, catches domain exceptions with appropriate HTTP codes (see design)
- [ ] `UpdateClassSessionAction`: catches `ClassSessionNotFoundException` (404), `CoachConflictException` (409), `InvalidCapacityException` (422), etc.
- [ ] `DeleteClassSessionAction`: returns 204 no content on success
- [ ] `CancelClassSessionAction` / `RestoreClassSessionAction`: return `ClassSessionResource` on success
- [ ] `GetWeeklyScheduleAction`: no auth required, returns `WeeklyScheduleResource`
- [ ] `GetCoachSessionsAction`: requires coach auth, uses authenticated user's ID
- [ ] All actions ≤ 20 lines

---

### TASK-026: Register Routes

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-025

**Description:**
Add the 9 new endpoints to `routes/api.php` with proper middleware groups.

**File:** `routes/api.php`

**Acceptance Criteria:**
- [ ] `GET /api/schedule` — no auth (public)
- [ ] Admin group (auth:sanctum + admin role + force_password_change): `GET /class-sessions`, `POST /class-sessions`, `GET /class-sessions/{id}`, `PUT /class-sessions/{id}`, `DELETE /class-sessions/{id}`, `PATCH /class-sessions/{id}/cancel`, `PATCH /class-sessions/{id}/restore`
- [ ] Coach group (auth:sanctum + coach role): `GET /coach/sessions`
- [ ] Middleware aliases verified against `bootstrap/app.php` before writing

---

## Phase 6: Tests

### TASK-027: Unit Tests – ClassSession Entity

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-007

**Description:**
Unit tests covering the `ClassSession` entity state machine and `create()` factory validations.

**File:** `tests/Unit/Core/ClassSession/Domain/ClassSessionTest.php`

**Acceptance Criteria:**
- [ ] `create()` with valid data returns active session
- [ ] `create()` with `maxCapacity < 1` throws `InvalidCapacityException`
- [ ] `cancel()` on active session transitions to cancelled
- [ ] `cancel()` on already cancelled throws `SessionAlreadyCancelledException`
- [ ] `restore()` on cancelled transitions to active
- [ ] `restore()` on active throws `SessionNotCancelledException`
- [ ] `update()` with invalid capacity throws `InvalidCapacityException`
- [ ] `softDelete()` sets `deletedAt` and `isDeleted()` returns true

---

### TASK-028: Unit Tests – TimeSlot VO

**Phase:** Tests
**Complexity:** S
**Dependencies:** TASK-005

**Description:**
Unit tests for the `TimeSlot` value object.

**File:** `tests/Unit/Core/ClassSession/Domain/TimeSlotTest.php`

**Acceptance Criteria:**
- [ ] Each valid slot creates a `TimeSlot` without exception (7 cases)
- [ ] Invalid slot string throws `InvalidTimeSlotException`
- [ ] `validValues()` returns array of 7 slots

---

### TASK-029: Feature Tests – Write Endpoints

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-026

**Description:**
Feature tests for all state-changing endpoints.

**Files:**
- `tests/Feature/ClassSession/CreateClassSessionTest.php`
- `tests/Feature/ClassSession/UpdateClassSessionTest.php`
- `tests/Feature/ClassSession/CancelRestoreClassSessionTest.php`
- `tests/Feature/ClassSession/DeleteClassSessionTest.php`

**Acceptance Criteria:**
- [ ] Create: 201 on success, 401 if unauthenticated, 403 if non-admin, 409 on coach conflict, 422 on invalid time slot / capacity
- [ ] Update: 200 on success, 404 on unknown ID, 409 on coach conflict
- [ ] Cancel: 200 on success, 422 if already cancelled
- [ ] Restore: 200 on success, 422 if not cancelled
- [ ] Delete: 204 on success, 404 on unknown ID

---

### TASK-030: Feature Tests – Read Endpoints

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-026

**Description:**
Feature tests for all read endpoints including public schedule and coach view.

**Files:**
- `tests/Feature/ClassSession/ListClassSessionsTest.php`
- `tests/Feature/ClassSession/GetClassSessionTest.php`
- `tests/Feature/ClassSession/GetWeeklyScheduleTest.php`
- `tests/Feature/ClassSession/GetCoachSessionsTest.php`

**Acceptance Criteria:**
- [ ] `GET /schedule` returns 200 with grouped structure, no auth required
- [ ] `GET /class-sessions` requires admin auth, supports day/coach/status filters
- [ ] `GET /class-sessions/{id}` requires admin auth, 404 on unknown ID
- [ ] `GET /coach/sessions` requires coach auth, returns only authenticated coach's sessions

---

## Phase 7: Collateral Changes

### TASK-031: Add AppServiceProvider Bindings

**Phase:** Collateral
**Complexity:** S
**Dependencies:** TASK-014, TASK-015, TASK-016, TASK-017, TASK-018, TASK-019, TASK-020, TASK-021, TASK-022

**Description:**
Register `ClassSessionRepositoryInterface`, `ClassSessionRepository`, and all command/query handlers in `AppServiceProvider`.

**File:** `app/Providers/AppServiceProvider.php`

**Acceptance Criteria:**
- [ ] `ClassSessionRepositoryInterface::class → ClassSessionRepository::class` binding
- [ ] `ClassSessionRepository` factory binding injects `ClassSessionHydrator`
- [ ] One binding per handler: Create, Update, Cancel, Restore, Delete commands; GetById, List, WeeklySchedule, CoachSessions queries

---

### TASK-032: Create ClassSessionSeeder

**Phase:** Collateral
**Complexity:** M
**Dependencies:** TASK-012, TASK-003

**Description:**
Create a seeder that inserts the full 42-session weekly schedule (no coach assigned, capacity = 20).

**File:** `database/seeders/ClassSessionSeeder.php`

**Acceptance Criteria:**
- [ ] Fetches class type IDs by slug in a single query (`class_types` table)
- [ ] Builds 42 records: Mon/Wed × 7 slots = 14 (tren-superior), Tue × 7 = 7 (tren-inferior), Thu × 7 = 7 (full-body), Fri × 7 = 7 (gap) + Fri × 7 = 7 (entrenamiento-libre)
- [ ] All records: `coach_id = null`, `max_capacity = 20`, `status = active`
- [ ] Uses `ClassSessionId::random()` for IDs
- [ ] Uses `ClassSessionModel::query()->insertOrIgnore()` for idempotency

---

### TASK-033: Wire ClassSessionSeeder into DatabaseSeeder

**Phase:** Collateral
**Complexity:** S
**Dependencies:** TASK-032

**Description:**
Add `ClassSessionSeeder` call to `DatabaseSeeder` after `ClassTypeSeeder`.

**File:** `database/seeders/DatabaseSeeder.php`

**Acceptance Criteria:**
- [ ] `$this->call(ClassSessionSeeder::class)` added after `ClassTypeSeeder` call
- [ ] Order preserved: ClassType seeder must run before ClassSession seeder (FK dependency)

---

### TASK-034: Update API Contract Documentation

**Phase:** Collateral
**Complexity:** S
**Dependencies:** TASK-026

**Description:**
Create or update the API contract doc for ClassSession endpoints.

**File:** `docs/api-contracts/class-sessions/class-sessions.md`

**Acceptance Criteria:**
- [ ] All 9 endpoints documented with method, URL, auth, request body (if any), response shape, error codes
- [ ] Public endpoint (`GET /schedule`) clearly marked as no-auth

---

### TASK-035: Update Roadmap Status

**Phase:** Collateral
**Complexity:** S
**Dependencies:** All previous tasks

**Description:**
Mark `epic-classes` as Done in the roadmap.

**File:** `docs/working_docs/roadmap.md`

**Acceptance Criteria:**
- [ ] `epic-classes` status updated from ⬜ Not Started to ✅ Done
- [ ] Progress table updated
- [ ] Bug/improvement log entry added with today's date

---

## Final Checklist

- [ ] All tasks completed
- [ ] All unit and feature tests passing (`php artisan test`)
- [ ] PHPStan passes at configured level
- [ ] No queries inside loops
- [ ] All actions ≤ 20 lines (thin action rule)
- [ ] No `rules()` in request classes
- [ ] `ClassSessionSeeder` runs without errors after migrations
- [ ] `GET /api/schedule` accessible without authentication
- [ ] Roadmap updated to ✅
