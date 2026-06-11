# Implementation Tasks: Class Booking System (epic-booking)

**Requirement:** `docs/working_docs/epics/epic-booking/requirements.md`
**Solution Design:** `docs/working_docs/epics/epic-booking/design.md`
**Created:** 2026-06-11
**Total Tasks:** 29
**Estimated Complexity:** M (Medium overall — clean greenfield bounded context)

---

## Summary

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Domain | 6 | S–M |
| Infrastructure | 5 | S–M |
| Application | 8 | S–M |
| HTTP | 6 | S–M |
| Tests | 4 | M |

---

## Phase 1: Domain Layer

### TASK-001: Create BookingId Value Object

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the ULID-based ID value object for the `Booking` entity, following the same pattern as `MemberId` and `ClassSessionId`.

**File:** `backend/src/Core/Booking/Domain/ValueObjects/BookingId.php`

**Acceptance Criteria:**
- [ ] `final class BookingId extends Ulid`
- [ ] `public static function random(): static`
- [ ] `public static function fromString(string $value): static`
- [ ] `public function value(): string` returns `$this->toBase32()`

---

### TASK-002: Create BookingStatus Enum

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the status enum for the `Booking` entity with values `confirmed` and `cancelled`.

**File:** `backend/src/Core/Booking/Domain/Enums/BookingStatus.php`

**Acceptance Criteria:**
- [ ] `enum BookingStatus: string`
- [ ] Case `Confirmed = 'confirmed'`
- [ ] Case `Cancelled = 'cancelled'`

---

### TASK-003: Create Domain Exceptions

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create all domain exception classes for the Booking bounded context. Each is a simple named exception that extends `\RuntimeException` (or the project's base exception class if one exists).

**Files:**
- `backend/src/Core/Booking/Domain/Exceptions/BookingNotFoundException.php`
- `backend/src/Core/Booking/Domain/Exceptions/BookingAlreadyExistsException.php`
- `backend/src/Core/Booking/Domain/Exceptions/BookingAlreadyCancelledException.php`
- `backend/src/Core/Booking/Domain/Exceptions/BookingNotOwnedException.php`
- `backend/src/Core/Booking/Domain/Exceptions/SessionFullException.php`
- `backend/src/Core/Booking/Domain/Exceptions/SessionNotAvailableException.php`

**Acceptance Criteria:**
- [ ] All 6 exceptions created
- [ ] Each accepts a meaningful message or relevant ID in constructor
- [ ] Each extends `\RuntimeException` (or project base exception)

---

### TASK-004: Create Booking Entity

**Phase:** Domain
**Complexity:** M
**Dependencies:** TASK-001, TASK-002, TASK-003

**Description:**
Create the `Booking` aggregate root entity with a static factory `create()` method and a `cancel()` state transition method.

**File:** `backend/src/Core/Booking/Domain/Entities/Booking.php`

**Acceptance Criteria:**
- [ ] Properties: `id: BookingId`, `memberId: MemberId`, `classSessionId: ClassSessionId`, `status: BookingStatus` (private, mutable), `createdAt: \DateTimeImmutable`
- [ ] `public static function create(BookingId, MemberId, ClassSessionId): self` — creates with `status = confirmed`
- [ ] `public function cancel(): void` — transitions to `cancelled`; throws `BookingAlreadyCancelledException` if already cancelled
- [ ] `public function status(): BookingStatus` getter
- [ ] No getters/setters for other properties — public readonly
- [ ] Imports: `MemberId` from `Core/Member`, `ClassSessionId` from `Core/ClassSession`

---

### TASK-005: Create Read Models

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-002

**Description:**
Create the two read models used by query handlers: `BookingRM` (for member-facing booking data) and `RosterItemRM` (for admin/coach roster data).

**Files:**
- `backend/src/Core/Booking/Domain/ReadModels/BookingRM.php`
- `backend/src/Core/Booking/Domain/ReadModels/RosterItemRM.php`

**Acceptance Criteria:**

`BookingRM` fields:
- [ ] `id: string`, `memberId: string`, `classSessionId: string`, `status: string`
- [ ] `dayOfWeek: string`, `timeSlot: string`, `classTypeName: string`, `classTypeSlug: string`
- [ ] `createdAt: string`
- [ ] All properties `readonly`

`RosterItemRM` fields:
- [ ] `bookingId: string`, `memberId: string`, `memberNumber: int`
- [ ] `firstName: string`, `lastName: string`, `status: string`, `bookedAt: string`
- [ ] All properties `readonly`

---

### TASK-006: Create BookingRepositoryInterface

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-004, TASK-005

**Description:**
Create the repository interface (port) for the Booking entity. Located in the Domain layer.

**File:** `backend/src/Core/Booking/Domain/Repositories/BookingRepositoryInterface.php`

**Acceptance Criteria:**
- [ ] `getById(BookingId $id): Booking` — throws `BookingNotFoundException`
- [ ] `findByMemberAndSession(MemberId $memberId, ClassSessionId $sessionId): ?Booking`
- [ ] `countConfirmedBySession(ClassSessionId $sessionId): int`
- [ ] `save(Booking $booking): void`
- [ ] `findByMember(MemberId $memberId): array` — returns `BookingRM[]`
- [ ] `getRoster(ClassSessionId $sessionId): array` — returns `RosterItemRM[]`
- [ ] All parameters use Value Objects (no raw strings)

---

## Phase 2: Infrastructure Layer

### TASK-007: Create BookingTable Constants

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** None

**Description:**
Create the `BookingTable` class with table name and column name constants.

**File:** `backend/src/Core/Booking/Infrastructure/Tables/BookingTable.php`

**Acceptance Criteria:**
- [ ] `TABLE_NAME = 'bookings'`
- [ ] Constants: `ID`, `MEMBER_ID`, `CLASS_SESSION_ID`, `STATUS`, `CREATED_AT`, `UPDATED_AT`

---

### TASK-008: Create Migration

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-007

**Description:**
Create the database migration for the `bookings` table.

**File:** `backend/database/migrations/2026_06_11_000020_create_bookings_table.php`

**Acceptance Criteria:**
- [ ] Columns: `id CHAR(26) PK`, `member_id CHAR(26)`, `class_session_id CHAR(26)`, `status ENUM('confirmed','cancelled') DEFAULT 'confirmed'`, `created_at`, `updated_at`
- [ ] `UNIQUE KEY uq_member_session (member_id, class_session_id)`
- [ ] `KEY idx_class_session_status (class_session_id, status)`
- [ ] `KEY idx_member_id (member_id)`
- [ ] FK: `member_id` → `members(id) ON DELETE CASCADE`
- [ ] FK: `class_session_id` → `class_sessions(id) ON DELETE CASCADE`
- [ ] `down()` method drops the table

---

### TASK-009: Create BookingModel

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-007, TASK-008

**Description:**
Create the Eloquent model for the `bookings` table.

**File:** `backend/src/Core/Booking/Infrastructure/Persistence/BookingModel.php`

**Acceptance Criteria:**
- [ ] `protected $table = BookingTable::TABLE_NAME`
- [ ] `$primaryKey = BookingTable::ID`
- [ ] `$incrementing = false`
- [ ] `$keyType = 'string'`
- [ ] `$fillable` includes all non-timestamp columns
- [ ] `casts()` returns status as string

---

### TASK-010: Create BookingHydrator

**Phase:** Infrastructure
**Complexity:** M
**Dependencies:** TASK-004, TASK-009

**Description:**
Create the hydrator responsible for transforming between `BookingModel` and `Booking` entity (and vice versa).

**File:** `backend/src/Core/Booking/Infrastructure/Hydrators/BookingHydrator.php`

**Acceptance Criteria:**
- [ ] `hydrate(BookingModel $model): Booking` — maps all columns to VOs and enums
- [ ] `dehydrate(Booking $booking): array` — returns array for `updateOrCreate`
- [ ] Handles `BookingStatus::from()` correctly
- [ ] Uses `BookingId::fromString()`, `MemberId::fromString()`, `ClassSessionId::fromString()`

---

### TASK-011: Create BookingRepository Implementation

**Phase:** Infrastructure
**Complexity:** M
**Dependencies:** TASK-006, TASK-009, TASK-010

**Description:**
Create the Eloquent-backed repository implementation for `BookingRepositoryInterface`.

**File:** `backend/src/Core/Booking/Infrastructure/Repositories/BookingRepository.php`

**Acceptance Criteria:**
- [ ] Implements `BookingRepositoryInterface`
- [ ] `getById()`: finds by PK, throws `BookingNotFoundException` if null
- [ ] `findByMemberAndSession()`: WHERE member_id AND class_session_id AND status = confirmed — returns null if not found
- [ ] `countConfirmedBySession()`: COUNT query — WHERE class_session_id AND status = confirmed
- [ ] `save()`: `BookingModel::query()->updateOrCreate([ID => ...], $hydrator->dehydrate($booking))`
- [ ] `findByMember()`: JOIN query with class_sessions + class_types — returns `BookingRM[]` ordered by `created_at DESC`
- [ ] `getRoster()`: JOIN query with members — returns `RosterItemRM[]` ordered by `created_at ASC`
- [ ] No queries in loops (all JOINs in single query)

---

## Phase 3: Application Layer

### TASK-012: Create CreateBookingCommand

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-004

**Description:**
Create the command data class for booking creation.

**File:** `backend/src/Core/Booking/Application/Commands/CreateBooking/CreateBookingCommand.php`

**Acceptance Criteria:**
- [ ] Properties: `id: BookingId`, `memberId: MemberId`, `classSessionId: ClassSessionId`
- [ ] All properties `readonly`
- [ ] No return type (void command)

---

### TASK-013: Create CreateBookingHandler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-006, TASK-011, TASK-012

**Description:**
Create the handler for `CreateBookingCommand`. Enforces all booking business rules.

**File:** `backend/src/Core/Booking/Application/Commands/CreateBooking/CreateBookingHandler.php`

**Acceptance Criteria:**
- [ ] Returns `void`
- [ ] Injects `BookingRepositoryInterface` and `ClassSessionRepositoryInterface`
- [ ] Step 1: `ClassSessionRepositoryInterface::getById()` — throws `ClassSessionNotFoundException` if not found (re-throw as-is; the Action catches it)
- [ ] Step 2: Checks `session->status() === ClassSessionStatus::Active` — throws `SessionNotAvailableException` if not
- [ ] Step 3: `BookingRepositoryInterface::countConfirmedBySession()` vs `session->maxCapacity` — throws `SessionFullException` if full
- [ ] Step 4: `BookingRepositoryInterface::findByMemberAndSession()` — throws `BookingAlreadyExistsException` if not null
- [ ] Step 5: `Booking::create(command->id, command->memberId, command->classSessionId)` + `repository->save($booking)`

---

### TASK-014: Create CancelBookingCommand

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-004

**Description:**
Create the command data class for booking cancellation.

**File:** `backend/src/Core/Booking/Application/Commands/CancelBooking/CancelBookingCommand.php`

**Acceptance Criteria:**
- [ ] Properties: `id: BookingId`, `requestingMemberId: MemberId`
- [ ] All properties `readonly`

---

### TASK-015: Create CancelBookingHandler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-006, TASK-011, TASK-014

**Description:**
Create the handler for `CancelBookingCommand`. Verifies ownership and delegates state transition to entity.

**File:** `backend/src/Core/Booking/Application/Commands/CancelBooking/CancelBookingHandler.php`

**Acceptance Criteria:**
- [ ] Returns `void`
- [ ] Injects `BookingRepositoryInterface`
- [ ] `getById(command->id)` — throws `BookingNotFoundException` if not found
- [ ] Compares `booking->memberId->value() === command->requestingMemberId->value()` — throws `BookingNotOwnedException` if mismatch
- [ ] `$booking->cancel()` — throws `BookingAlreadyCancelledException` if already cancelled
- [ ] `repository->save($booking)`

---

### TASK-016: Create GetBookingByIdQuery + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-005, TASK-011

**Description:**
Create the query and handler to retrieve a single booking by ID (used by Actions after save to return response).

**Files:**
- `backend/src/Core/Booking/Application/Queries/GetBookingById/GetBookingByIdQuery.php`
- `backend/src/Core/Booking/Application/Queries/GetBookingById/GetBookingByIdHandler.php`

**Acceptance Criteria:**
- [ ] Query has one property: `id: BookingId`
- [ ] Handler calls `repository->getById()` and maps `Booking` to `BookingRM`
- [ ] Throws `BookingNotFoundException` if not found
- [ ] Returns `BookingRM` (read model, not entity)

---

### TASK-017: Create GetMemberBookingsQuery + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-005, TASK-011

**Description:**
Create the query and handler to retrieve all bookings for a given member (used by member self-view and admin view).

**Files:**
- `backend/src/Core/Booking/Application/Queries/GetMemberBookings/GetMemberBookingsQuery.php`
- `backend/src/Core/Booking/Application/Queries/GetMemberBookings/GetMemberBookingsHandler.php`

**Acceptance Criteria:**
- [ ] Query has one property: `memberId: MemberId`
- [ ] Handler calls `repository->findByMember(memberId)`
- [ ] Returns `BookingRM[]` (may be empty array)

---

### TASK-018: Create GetClassRosterQuery + Handler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-005, TASK-011

**Description:**
Create the query and handler to retrieve the class roster for a given session, including confirmed count and max capacity.

**Files:**
- `backend/src/Core/Booking/Application/Queries/GetClassRoster/GetClassRosterQuery.php`
- `backend/src/Core/Booking/Application/Queries/GetClassRoster/GetClassRosterHandler.php`

**Acceptance Criteria:**
- [ ] Query has one property: `classSessionId: ClassSessionId`
- [ ] Handler injects `BookingRepositoryInterface` and `ClassSessionRepositoryInterface`
- [ ] Gets session via `ClassSessionRepositoryInterface::getById()` for `maxCapacity`
- [ ] Calls `repository->getRoster(classSessionId)` → `RosterItemRM[]`
- [ ] Calls `repository->countConfirmedBySession(classSessionId)` → int
- [ ] Returns array: `['items' => RosterItemRM[], 'confirmed_count' => int, 'max_capacity' => int]`

---

### TASK-019: Create GetMemberBookingsByIdQuery + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-017

**Description:**
Create the query and handler for admin to retrieve bookings for any given member ID. This is functionally identical to `GetMemberBookingsQuery` — reuse the same handler or create a thin wrapper. Recommendation: reuse `GetMemberBookingsQuery`.

**File:** (reuse `GetMemberBookingsQuery` — no new file needed)

**Acceptance Criteria:**
- [ ] Admin-facing action reuses `GetMemberBookingsQuery` with the target `memberId` from the URL path
- [ ] If a separate query is deemed cleaner, it delegates to `repository->findByMember(memberId)` — same implementation

---

## Phase 4: HTTP Layer

### TASK-020: Create CreateBookingRequest + DTO

**Phase:** HTTP
**Complexity:** S
**Dependencies:** None

**Description:**
Create the HTTP request class and DTO for booking creation. Only `class_session_id` is in the request body — `member_id` is resolved from the authenticated user.

**Files:**
- `backend/app/Http/Actions/Booking/Create/CreateBookingRequest.php`
- `backend/app/Http/Actions/Booking/Create/CreateBookingDto.php`

**Acceptance Criteria:**
- [ ] `CreateBookingRequest` has only `getDto(): CreateBookingDto` (no `rules()`)
- [ ] `CreateBookingDto` has one property: `classSessionId: string`
- [ ] `authorize()` returns `true`

---

### TASK-021: Create Booking Resources

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-005

**Description:**
Create the three resource classes used to serialize booking data for HTTP responses.

**Files:**
- `backend/app/Http/Actions/Booking/Shared/BookingResource.php`
- `backend/app/Http/Actions/Booking/Shared/BookingListResource.php`
- `backend/app/Http/Actions/Booking/Shared/RosterResource.php`

**Acceptance Criteria:**

`BookingResource`:
- [ ] Takes `BookingRM` in constructor
- [ ] `toResponse(int $status = 200): JsonResponse`
- [ ] Includes: `id`, `member_id`, `class_session_id`, `status`, `session` (day_of_week, time_slot, class_type_name, class_type_slug), `created_at`

`BookingListResource`:
- [ ] Takes `BookingRM[]` in constructor
- [ ] `toResponse(): JsonResponse` — returns array of booking objects

`RosterResource`:
- [ ] Takes `RosterItemRM[]`, `int $confirmedCount`, `int $maxCapacity` in constructor
- [ ] `toResponse(): JsonResponse`
- [ ] Includes `capacity` object: `confirmed`, `available` (max - confirmed), `max`
- [ ] Includes `roster` array with member info per booking

---

### TASK-022: Create CreateBookingAction

**Phase:** HTTP
**Complexity:** M
**Dependencies:** TASK-013, TASK-016, TASK-020, TASK-021

**Description:**
Create the thin action for booking creation. Resolves the authenticated member's `MemberId` from the logged-in user, then dispatches the command.

**File:** `backend/app/Http/Actions/Booking/Create/CreateBookingAction.php`

**Acceptance Criteria:**
- [ ] `<= 20 lines of logic`
- [ ] Injects: `CreateBookingHandler`, `GetBookingByIdHandler`, `MemberRepositoryInterface`
- [ ] Resolves `MemberId` via `MemberRepositoryInterface::findByUserId()` from authenticated user
- [ ] Generates `BookingId::random()` before dispatching command
- [ ] Catches all domain exceptions with appropriate HTTP codes:
  - `ClassSessionNotFoundException` → 404 `SESSION_NOT_FOUND`
  - `SessionNotAvailableException` → 422 `SESSION_NOT_AVAILABLE`
  - `SessionFullException` → 422 `SESSION_FULL`
  - `BookingAlreadyExistsException` → 409 `BOOKING_ALREADY_EXISTS`
- [ ] Returns `BookingResource->toResponse(201)` on success

---

### TASK-023: Create CancelBookingAction

**Phase:** HTTP
**Complexity:** M
**Dependencies:** TASK-015, TASK-016, TASK-021

**Description:**
Create the thin action for booking cancellation. Resolves the authenticated member, then dispatches the cancel command.

**File:** `backend/app/Http/Actions/Booking/Cancel/CancelBookingAction.php`

**Acceptance Criteria:**
- [ ] `<= 20 lines of logic`
- [ ] Injects: `CancelBookingHandler`, `GetBookingByIdHandler`, `MemberRepositoryInterface`
- [ ] Resolves `MemberId` from authenticated user via `findByUserId()`
- [ ] Catches:
  - `BookingNotFoundException` → 404 `BOOKING_NOT_FOUND`
  - `BookingNotOwnedException` → 403 `BOOKING_NOT_OWNED`
  - `BookingAlreadyCancelledException` → 422 `BOOKING_ALREADY_CANCELLED`
- [ ] Returns `BookingResource->toResponse(200)` on success

---

### TASK-024: Create GetMemberBookingsAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-017, TASK-021

**Description:**
Create the action for a member to retrieve their own booking list.

**File:** `backend/app/Http/Actions/Booking/MemberBookings/GetMemberBookingsAction.php`

**Acceptance Criteria:**
- [ ] Injects: `GetMemberBookingsHandler`, `MemberRepositoryInterface`
- [ ] Resolves `MemberId` from authenticated user
- [ ] Returns `BookingListResource->toResponse()`

---

### TASK-025: Create GetClassRosterAction + GetAdminMemberBookingsAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-018, TASK-021

**Description:**
Create the two remaining read-only actions: class roster (for admin and coach) and admin view of member bookings.

**Files:**
- `backend/app/Http/Actions/Booking/Roster/GetClassRosterAction.php`
- `backend/app/Http/Actions/Booking/AdminMemberBookings/GetAdminMemberBookingsAction.php`

**Acceptance Criteria:**

`GetClassRosterAction`:
- [ ] Injects: `GetClassRosterHandler`
- [ ] Route param `{id}` → `ClassSessionId::fromString($id)`
- [ ] Catches `ClassSessionNotFoundException` → 404
- [ ] Returns `RosterResource->toResponse()`

`GetAdminMemberBookingsAction`:
- [ ] Injects: `GetMemberBookingsHandler`
- [ ] Route param `{id}` → `MemberId::fromString($id)`
- [ ] Returns `BookingListResource->toResponse()`

---

### TASK-026: Register Routes

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-022, TASK-023, TASK-024, TASK-025

**Description:**
Add all booking routes to `routes/api.php`.

**File:** `backend/routes/api.php`

**Routes to add:**
```
POST   /api/bookings                              → CreateBookingAction           [auth:sanctum, role.member]
PATCH  /api/bookings/{id}/cancel                  → CancelBookingAction           [auth:sanctum, role.member]
GET    /api/member/bookings                       → GetMemberBookingsAction        [auth:sanctum, role.member]
GET    /api/admin/class-sessions/{id}/roster      → GetClassRosterAction           [auth:sanctum, role.admin]
GET    /api/coach/class-sessions/{id}/roster      → GetClassRosterAction           [auth:sanctum, role.coach]
GET    /api/admin/members/{id}/bookings           → GetAdminMemberBookingsAction   [auth:sanctum, role.admin]
```

**Acceptance Criteria:**
- [ ] All 6 route definitions added
- [ ] Correct middleware groups applied per role
- [ ] `GetClassRosterAction` reused for both admin and coach routes
- [ ] Routes added within existing middleware groups if they share the same prefix
- [ ] Verify `role.member`, `role.admin`, `role.coach` middleware aliases exist

---

### TASK-027: Register Service Provider Bindings

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-011, TASK-013, TASK-015, TASK-016, TASK-017, TASK-018

**Description:**
Register `BookingRepositoryInterface` → `BookingRepository` and all handler bindings in `AppServiceProvider`.

**File:** `backend/app/Providers/AppServiceProvider.php`

**Acceptance Criteria:**
- [ ] `BookingRepositoryInterface` bound to `BookingRepository`
- [ ] `BookingRepository` bound with `BookingHydrator` injected
- [ ] `CreateBookingHandler` bound with dependencies (`BookingRepositoryInterface`, `ClassSessionRepositoryInterface`)
- [ ] `CancelBookingHandler` bound with `BookingRepositoryInterface`
- [ ] `GetBookingByIdHandler` bound with `BookingRepositoryInterface`
- [ ] `GetMemberBookingsHandler` bound with `BookingRepositoryInterface`
- [ ] `GetClassRosterHandler` bound with `BookingRepositoryInterface` and `ClassSessionRepositoryInterface`

---

## Phase 5: Tests

### TASK-028: Unit Tests — Domain

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-004

**Description:**
Create unit tests for the `Booking` entity and its state machine.

**File:** `backend/tests/Unit/Core/Booking/Domain/BookingTest.php`

**Acceptance Criteria:**
- [ ] `create()` returns booking with status `confirmed`
- [ ] `cancel()` on a `confirmed` booking transitions to `cancelled`
- [ ] `cancel()` on an already `cancelled` booking throws `BookingAlreadyCancelledException`
- [ ] All tests are isolated (no DB, mocked dependencies)

---

### TASK-029: Unit Tests — Handlers

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-013, TASK-015

**Description:**
Create unit tests for `CreateBookingHandler` and `CancelBookingHandler` with mocked repositories.

**Files:**
- `backend/tests/Unit/Core/Booking/CreateBookingHandlerTest.php`
- `backend/tests/Unit/Core/Booking/CancelBookingHandlerTest.php`

**Acceptance Criteria:**

`CreateBookingHandlerTest`:
- [ ] Happy path — booking is created and saved
- [ ] Session not found → `ClassSessionNotFoundException` propagated
- [ ] Session cancelled → `SessionNotAvailableException` thrown
- [ ] Session full → `SessionFullException` thrown
- [ ] Duplicate booking → `BookingAlreadyExistsException` thrown

`CancelBookingHandlerTest`:
- [ ] Happy path — booking is cancelled and saved
- [ ] Booking not found → `BookingNotFoundException` thrown
- [ ] Booking not owned by requesting member → `BookingNotOwnedException` thrown
- [ ] Already cancelled → `BookingAlreadyCancelledException` thrown

---

### TASK-030: Feature Tests — HTTP Endpoints

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-026

**Description:**
Create feature (integration) tests for all 6 booking HTTP endpoints using Laravel's test helpers and a test database.

**Files:**
- `backend/tests/Feature/Booking/CreateBookingTest.php`
- `backend/tests/Feature/Booking/CancelBookingTest.php`
- `backend/tests/Feature/Booking/GetMemberBookingsTest.php`
- `backend/tests/Feature/Booking/GetClassRosterTest.php`
- `backend/tests/Feature/Booking/GetAdminMemberBookingsTest.php`

**Acceptance Criteria:**

`CreateBookingTest`:
- [ ] 201 on success with correct body shape
- [ ] 422 `SESSION_FULL` when session is at capacity
- [ ] 409 `BOOKING_ALREADY_EXISTS` when booking exists
- [ ] 422 `SESSION_NOT_AVAILABLE` when session is cancelled
- [ ] 401 when unauthenticated
- [ ] 403 when non-member role attempts

`CancelBookingTest`:
- [ ] 200 on success
- [ ] 404 `BOOKING_NOT_FOUND` for unknown ID
- [ ] 403 `BOOKING_NOT_OWNED` when different member cancels
- [ ] 422 `BOOKING_ALREADY_CANCELLED` on double-cancel

`GetMemberBookingsTest`:
- [ ] 200 with array of bookings for authenticated member
- [ ] 200 with empty array when no bookings

`GetClassRosterTest`:
- [ ] 200 with correct capacity summary and roster
- [ ] 404 when session not found
- [ ] Accessible by admin and coach roles
- [ ] 403 for member role

`GetAdminMemberBookingsTest`:
- [ ] 200 with bookings for specified member
- [ ] 403 for non-admin role

---

## Final Checklist

- [ ] All 30 tasks completed
- [ ] Migration runs without errors (`php artisan migrate`)
- [ ] All unit tests pass
- [ ] All feature tests pass
- [ ] PHPStan passes at project level
- [ ] No N+1 queries (verified by reviewing repository JOIN queries)
- [ ] All domain exceptions caught in Actions with correct HTTP codes
- [ ] Routes registered and accessible
- [ ] Service provider bindings registered
- [ ] Code reviewed against architecture rules (TASK-004 entity is thin, handlers return void, requests have only `getDto()`)
