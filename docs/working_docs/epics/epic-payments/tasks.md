# Implementation Tasks: Payment Tracking (epic-payments)

**Requirement:** [requirements.md](requirements.md)
**Validation:** [validation.md](validation.md)
**Solution Design:** [design.md](design.md)
**Created:** 2026-06-11
**Total Tasks:** 38
**Estimated Complexity:** M (single entity, 5 endpoints, 1 cross-BC read)

---

## Summary

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Phase 1: Domain Layer | 8 | S–M |
| Phase 2: Infrastructure Layer | 5 | S–M |
| Phase 3: Application Layer | 10 | S–M |
| Phase 4: HTTP Layer | 13 | S |
| Phase 5: Tests | 8 | M |
| **Total** | **38** | **M** |

---

## Phase 1: Domain Layer

### TASK-001: Create PaymentId Value Object

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the ULID-based ID value object for the Payment entity, following the same pattern as MemberId in `src/Core/Member/Domain/ValueObjects/MemberId.php`.

**File:** `src/Billing/Payment/Domain/ValueObjects/PaymentId.php`

**Acceptance Criteria:**
- [ ] Extends `Ulid` base class
- [ ] Has `random(): static` static factory method
- [ ] Has `fromString(string $value): static` static factory method
- [ ] Has `value(): string` method returning the base32 ULID string
- [ ] Located in `Billing/Payment` bounded context

---

### TASK-002: Create Payment Entity

**Phase:** Domain
**Complexity:** M
**Dependencies:** TASK-001

**Description:**
Create the immutable Payment entity with a `create()` factory method that derives `billing_month` from `paymentDate` automatically. No `update()` method — payments are immutable. Throws `InvalidPaymentAmountException` if `amountCents <= 0`.

**File:** `src/Billing/Payment/Domain/Entities/Payment.php`

**Acceptance Criteria:**
- [ ] All properties are `public readonly`
- [ ] Constructor accepts: `PaymentId`, `MemberId`, `MembershipPlanId`, `UserId` (recordedBy), `int` amountCents, `DateTimeImmutable` paymentDate, `string` billingMonth, `?string` notes, `DateTimeImmutable` createdAt
- [ ] `create()` static factory method derives `billingMonth` via `$paymentDate->format('Y-m')` — never from external input
- [ ] `create()` throws `InvalidPaymentAmountException` if `$amountCents <= 0`
- [ ] No `update()`, `delete()`, or state-transition methods — entity is immutable
- [ ] No getter/setter methods — public readonly properties only

---

### TASK-003: Create PaymentNotFoundException

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create domain exception for when a payment is not found by ID.

**File:** `src/Billing/Payment/Domain/Exceptions/PaymentNotFoundException.php`

**Acceptance Criteria:**
- [ ] Extends `\RuntimeException` (or project base exception class)
- [ ] Has a static `withId(string $id): self` factory method with descriptive message
- [ ] No framework dependencies

---

### TASK-004: Create PaymentAlreadyExistsForMonthException

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create domain exception for duplicate payment (same member + billing month).

**File:** `src/Billing/Payment/Domain/Exceptions/PaymentAlreadyExistsForMonthException.php`

**Acceptance Criteria:**
- [ ] Extends `\RuntimeException`
- [ ] Has a static `forMemberAndMonth(string $memberId, string $billingMonth): self` factory with descriptive message
- [ ] No framework dependencies

---

### TASK-005: Create InvalidPaymentAmountException

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create domain exception for when payment amount is zero or negative.

**File:** `src/Billing/Payment/Domain/Exceptions/InvalidPaymentAmountException.php`

**Acceptance Criteria:**
- [ ] Extends `\RuntimeException`
- [ ] Constructor accepts a descriptive message
- [ ] No framework dependencies

---

### TASK-006: Create Payment Read Models

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the four read models used by query handlers. These are lightweight DTOs, not entities.

**Files:**
- `src/Billing/Payment/Domain/ReadModels/PaymentDetailRM.php`
- `src/Billing/Payment/Domain/ReadModels/PaymentListItemRM.php`
- `src/Billing/Payment/Domain/ReadModels/OverdueMemberRM.php`
- `src/Billing/Payment/Domain/ReadModels/MemberPaymentListItemRM.php`

**Acceptance Criteria:**
- [ ] `PaymentDetailRM`: id, memberId, memberNumber, memberFirstName, memberLastName, membershipPlanId, planName, recordedBy, amountCents, paymentDate (string), billingMonth, notes, createdAt — all public readonly
- [ ] `PaymentListItemRM`: id, memberNumber, memberFirstName, memberLastName, amountCents, paymentDate, billingMonth, planName, createdAt — all public readonly
- [ ] `OverdueMemberRM`: memberId, memberNumber, firstName, lastName, email, planName (nullable), lastPaymentDate (nullable) — all public readonly
- [ ] `MemberPaymentListItemRM`: id, amountCents, paymentDate, billingMonth, planName — all public readonly
- [ ] All classes use `final readonly class` syntax
- [ ] No business logic — data containers only

---

### TASK-007: Create PaymentRepositoryInterface

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-001, TASK-002, TASK-006

**Description:**
Create the repository interface (port) for Payment in the Domain layer. Uses domain Value Objects in all signatures — never raw strings for IDs.

**File:** `src/Billing/Payment/Domain/Repositories/PaymentRepositoryInterface.php`

**Acceptance Criteria:**
- [ ] `getById(PaymentId $id): Payment` — throws PaymentNotFoundException
- [ ] `getDetailById(PaymentId $id): PaymentDetailRM` — throws PaymentNotFoundException
- [ ] `findByMemberAndBillingMonth(MemberId $memberId, string $billingMonth): ?Payment`
- [ ] `save(Payment $payment): void`
- [ ] `findAll(?string $memberId, ?int $year, ?int $month, int $page, int $perPage): array` — returns `PaymentListItemRM[]`
- [ ] `countAll(?string $memberId, ?int $year, ?int $month): int`
- [ ] `findOverdueMembers(string $billingMonth): array` — returns `OverdueMemberRM[]`
- [ ] `findByMemberId(MemberId $memberId): array` — returns `MemberPaymentListItemRM[]`
- [ ] All ID parameters use Value Objects, not raw strings
- [ ] Located in Domain layer (interface only, no implementation)

---

### TASK-008: Create MemberNotFoundException (Billing context alias)

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
The `RecordPaymentHandler` must throw a domain-meaningful exception when the referenced member does not exist. Re-use `Core\Member\Domain\Exceptions\MemberNotFoundException` directly — do NOT duplicate it. This task is to verify the import path and document that no new exception class is needed.

**File:** Reference existing `src/Core/Member/Domain/Exceptions/MemberNotFoundException.php`

**Acceptance Criteria:**
- [ ] Confirm `MemberNotFoundException` exists in `src/Core/Member/Domain/Exceptions/`
- [ ] Confirm `MembershipPlanNotFoundException` exists in `src/Core/Member/Domain/Exceptions/`
- [ ] Document in design comments that Billing/Payment handlers import from Core/Member exceptions — this is the documented cross-BC exception sharing pattern
- [ ] No new file created

---

## Phase 2: Infrastructure Layer

### TASK-009: Create PaymentTable Constants Class

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** None

**Description:**
Create a constants class for the payments table column names, following the same pattern as `MemberTable.php`.

**File:** `src/Billing/Payment/Infrastructure/Tables/PaymentTable.php`

**Acceptance Criteria:**
- [ ] `TABLE_NAME = 'payments'`
- [ ] Constants for all columns: `ID`, `MEMBER_ID`, `MEMBERSHIP_PLAN_ID`, `RECORDED_BY`, `AMOUNT_CENTS`, `PAYMENT_DATE`, `BILLING_MONTH`, `NOTES`, `CREATED_AT`, `UPDATED_AT`
- [ ] `final class`, no instantiation

---

### TASK-010: Create Payments Migration

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-009

**Description:**
Create the database migration for the `payments` table.

**File:** `database/migrations/2026_06_11_000001_create_payments_table.php`

**Acceptance Criteria:**
- [ ] Creates `payments` table with columns: id (CHAR(26) PK), member_id (CHAR(26) NOT NULL), membership_plan_id (CHAR(26) NOT NULL), recorded_by (CHAR(26) NOT NULL), amount_cents (INT NOT NULL), payment_date (DATE NOT NULL), billing_month (CHAR(7) NOT NULL), notes (TEXT NULL), created_at, updated_at
- [ ] Foreign keys: member_id → members.id, membership_plan_id → membership_plans.id, recorded_by → users.id
- [ ] Unique constraint: `(member_id, billing_month)`
- [ ] Indexes: billing_month, member_id, payment_date
- [ ] Reversible `down()` method drops the table

---

### TASK-011: Create PaymentModel

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-010, TASK-009

**Description:**
Create the Eloquent model for the payments table. Payments are never soft-deleted.

**File:** `src/Billing/Payment/Infrastructure/Persistence/PaymentModel.php`

**Acceptance Criteria:**
- [ ] `protected $table = PaymentTable::TABLE_NAME`
- [ ] `public $incrementing = false` and `protected $keyType = 'string'` (ULID)
- [ ] `protected $fillable` includes all columns from PaymentTable constants
- [ ] `casts()` method casts `payment_date` as `'date'`
- [ ] No SoftDeletes trait — payments are immutable
- [ ] `final class`

---

### TASK-012: Create PaymentHydrator

**Phase:** Infrastructure
**Complexity:** M
**Dependencies:** TASK-002, TASK-011

**Description:**
Create the hydrator that transforms between `PaymentModel` (Eloquent) and `Payment` (domain entity). Follows the same pattern as `MemberHydrator.php`.

**File:** `src/Billing/Payment/Infrastructure/Hydrators/PaymentHydrator.php`

**Acceptance Criteria:**
- [ ] `hydrate(PaymentModel $model): Payment` — maps all columns to domain value objects
- [ ] `dehydrate(Payment $payment): array` — maps all domain properties back to column values
- [ ] All IDs converted via `::fromString()` in hydrate and `->value()` in dehydrate
- [ ] `paymentDate` and `createdAt` converted to `DateTimeImmutable`
- [ ] `final class`

---

### TASK-013: Create PaymentRepository

**Phase:** Infrastructure
**Complexity:** M
**Dependencies:** TASK-007, TASK-011, TASK-012

**Description:**
Create the repository implementation. Implements all methods from `PaymentRepositoryInterface`. Key requirement: no N+1 queries. The overdue members query uses a single LEFT JOIN with a `NOT EXISTS` subquery.

**File:** `src/Billing/Payment/Infrastructure/Repositories/PaymentRepository.php`

**Acceptance Criteria:**
- [ ] Implements `PaymentRepositoryInterface`
- [ ] `save()` uses `PaymentModel::create()` — no updateOrCreate (payments are immutable/insert-only)
- [ ] `getById()` throws `PaymentNotFoundException` if not found
- [ ] `getDetailById()` uses a single JOIN query to load member number, names, plan name — no N+1
- [ ] `findAll()` uses a single JOIN query with optional WHERE clauses for memberId, year, month — no N+1
- [ ] `findOverdueMembers()` uses the single SQL query from the design doc (LEFT JOIN + NOT EXISTS subquery) — no N+1
- [ ] `findByMemberId()` uses a single JOIN with membership_plans to include plan name — no N+1
- [ ] `findByMemberAndBillingMonth()` uses a simple WHERE clause on the unique columns

---

## Phase 3: Application Layer

### TASK-014: Create RecordPaymentCommand

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-001, TASK-002

**Description:**
Create the command data object for recording a payment. ID is passed in (generated in the Action before dispatching).

**File:** `src/Billing/Payment/Application/Commands/RecordPayment/RecordPaymentCommand.php`

**Acceptance Criteria:**
- [ ] Constructor accepts: `PaymentId` paymentId, `MemberId` memberId, `MembershipPlanId` membershipPlanId, `UserId` recordedBy, `int` amountCents, `DateTimeImmutable` paymentDate, `?string` notes
- [ ] All properties `public readonly`
- [ ] No logic — pure data container

---

### TASK-015: Create RecordPaymentHandler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-007, TASK-014

**Description:**
Create the handler for `RecordPaymentCommand`. Validates business rules, creates the entity, and saves it. Returns void (CQRS rule).

**File:** `src/Billing/Payment/Application/Commands/RecordPayment/RecordPaymentHandler.php`

**Acceptance Criteria:**
- [ ] Returns `void` (CQRS — commands never return values)
- [ ] Injects `PaymentRepositoryInterface`, `MemberRepositoryInterface`, `MembershipPlanRepositoryInterface`
- [ ] Checks `findByMemberAndBillingMonth()` — throws `PaymentAlreadyExistsForMonthException` if duplicate
- [ ] Verifies member exists via `MemberRepositoryInterface::getById()` — throws `MemberNotFoundException` if not
- [ ] Verifies plan exists and is active via `MembershipPlanRepositoryInterface::getById()` — throws `MembershipPlanNotFoundException` if not
- [ ] Creates entity via `Payment::create()` — `InvalidPaymentAmountException` propagates if amount invalid
- [ ] Saves via `PaymentRepositoryInterface::save()`
- [ ] No direct database access — only via injected repository interfaces

---

### TASK-016: Create GetPaymentByIdQuery

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-001

**Description:**
Create query to retrieve a single payment's full detail by ID.

**File:** `src/Billing/Payment/Application/Queries/GetPaymentById/GetPaymentByIdQuery.php`

**Acceptance Criteria:**
- [ ] Constructor accepts `PaymentId $id`
- [ ] Property is `public readonly`

---

### TASK-017: Create GetPaymentByIdHandler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-007, TASK-016

**Description:**
Create handler for `GetPaymentByIdQuery`. Returns `PaymentDetailRM`.

**File:** `src/Billing/Payment/Application/Queries/GetPaymentById/GetPaymentByIdHandler.php`

**Acceptance Criteria:**
- [ ] Returns `PaymentDetailRM` (not the entity)
- [ ] Delegates to `PaymentRepositoryInterface::getDetailById()`
- [ ] `PaymentNotFoundException` propagates to the caller

---

### TASK-018: Create ListPaymentsQuery

**Phase:** Application
**Complexity:** S
**Dependencies:** None

**Description:**
Create query for paginated list of payments with optional filters.

**File:** `src/Billing/Payment/Application/Queries/ListPayments/ListPaymentsQuery.php`

**Acceptance Criteria:**
- [ ] Constructor accepts: `?string` memberId, `?int` year, `?int` month, `int` page (default 1), `int` perPage (default 20)
- [ ] All properties `public readonly`

---

### TASK-019: Create ListPaymentsHandler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-007, TASK-018

**Description:**
Create handler for `ListPaymentsQuery`. Returns paginated result with items and total count.

**File:** `src/Billing/Payment/Application/Queries/ListPayments/ListPaymentsHandler.php`

**Acceptance Criteria:**
- [ ] Returns `array{items: PaymentListItemRM[], total: int}`
- [ ] Calls `PaymentRepositoryInterface::findAll()` and `countAll()` — two queries max (not N+1)
- [ ] Passes all filter parameters through unchanged

---

### TASK-020: Create GetOverdueMembersQuery

**Phase:** Application
**Complexity:** S
**Dependencies:** None

**Description:**
Create query for the overdue members list. No parameters — uses server date internally in the handler.

**File:** `src/Billing/Payment/Application/Queries/GetOverdueMembers/GetOverdueMembersQuery.php`

**Acceptance Criteria:**
- [ ] Empty constructor (no parameters needed)
- [ ] Current billing month is determined in the handler, not here

---

### TASK-021: Create GetOverdueMembersHandler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-007, TASK-020

**Description:**
Create handler for `GetOverdueMembersQuery`. Derives current billing month and delegates to the repository.

**File:** `src/Billing/Payment/Application/Queries/GetOverdueMembers/GetOverdueMembersHandler.php`

**Acceptance Criteria:**
- [ ] Returns `OverdueMemberRM[]`
- [ ] Derives billing month: `(new \DateTimeImmutable())->format('Y-m')`
- [ ] Calls `PaymentRepositoryInterface::findOverdueMembers(billingMonth)`
- [ ] No business logic beyond deriving the current month

---

### TASK-022: Create GetMyPaymentsQuery

**Phase:** Application
**Complexity:** S
**Dependencies:** None

**Description:**
Create query for a member to retrieve their own payment history.

**File:** `src/Billing/Payment/Application/Queries/GetMyPayments/GetMyPaymentsQuery.php`

**Acceptance Criteria:**
- [ ] Constructor accepts `MemberId $memberId`
- [ ] Property `public readonly`

---

### TASK-023: Create GetMyPaymentsHandler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-007, TASK-022

**Description:**
Create handler for `GetMyPaymentsQuery`. Returns the member's payment list.

**File:** `src/Billing/Payment/Application/Queries/GetMyPayments/GetMyPaymentsHandler.php`

**Acceptance Criteria:**
- [ ] Returns `MemberPaymentListItemRM[]`
- [ ] Delegates to `PaymentRepositoryInterface::findByMemberId()`
- [ ] No direct DB access

---

## Phase 4: HTTP Layer

### TASK-024: Create RecordPaymentDto and RecordPaymentRequest

**Phase:** HTTP
**Complexity:** S
**Dependencies:** None

**Description:**
Create the DTO and Request for the `POST /api/admin/payments` endpoint. Request must use only `getDto()` — no Laravel validation rules.

**Files:**
- `app/Http/Actions/Payments/Record/RecordPaymentDto.php`
- `app/Http/Actions/Payments/Record/RecordPaymentRequest.php`

**Acceptance Criteria:**
- [ ] `RecordPaymentDto` has public readonly: `string` memberId, `string` membershipPlanId, `int` amountCents, `DateTimeImmutable` paymentDate, `?string` notes
- [ ] `RecordPaymentRequest::getDto()` maps inputs: `member_id`, `membership_plan_id`, `amount_cents` (cast to int), `payment_date` (cast to DateTimeImmutable), `notes` (nullable)
- [ ] NO `rules()` method — only `getDto()`
- [ ] No framework validation logic

---

### TASK-025: Create ListPaymentsDto and ListPaymentsRequest

**Phase:** HTTP
**Complexity:** S
**Dependencies:** None

**Description:**
Create DTO and Request for the `GET /api/admin/payments` endpoint.

**Files:**
- `app/Http/Actions/Payments/List/ListPaymentsDto.php`
- `app/Http/Actions/Payments/List/ListPaymentsRequest.php`

**Acceptance Criteria:**
- [ ] `ListPaymentsDto` has: `?string` memberId, `?int` year, `?int` month, `int` page, `int` perPage
- [ ] `ListPaymentsRequest::getDto()` maps query params with proper type casts and defaults (page=1, perPage=20)
- [ ] NO `rules()` method

---

### TASK-026: Create Payment Resources

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-006

**Description:**
Create the four resource classes for HTTP responses.

**Files:**
- `app/Http/Actions/Payments/Shared/PaymentResource.php`
- `app/Http/Actions/Payments/Shared/PaymentListResource.php`
- `app/Http/Actions/Payments/Shared/OverdueMemberResource.php`
- `app/Http/Actions/Payments/Shared/MemberPaymentListResource.php`

**Acceptance Criteria:**
- [ ] `PaymentResource`: wraps `PaymentDetailRM`, `toResponse(int $status = 200): JsonResponse`, JSON shape matches API contract in design doc
- [ ] `PaymentListResource`: wraps `PaymentListItemRM[]` + total + page + perPage, returns `data` + `meta` structure
- [ ] `OverdueMemberResource`: wraps `OverdueMemberRM[]`, returns `data` + `meta.total` structure
- [ ] `MemberPaymentListResource`: wraps `MemberPaymentListItemRM[]`, returns `data` array
- [ ] All amount values exposed as `amount_cents` (int) — no currency formatting in resources
- [ ] `final class` for each

---

### TASK-027: Create RecordPaymentAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-015, TASK-017, TASK-024, TASK-026

**Description:**
Create the thin action for `POST /api/admin/payments`. Generates PaymentId, dispatches command, catches domain exceptions, returns resource.

**File:** `app/Http/Actions/Payments/Record/RecordPaymentAction.php`

**Acceptance Criteria:**
- [ ] Max 30 lines (thin action)
- [ ] Generates `PaymentId::random()` before dispatching command
- [ ] Reads `recorded_by` from `$request->user()->id`
- [ ] Catches and maps all domain exceptions to correct HTTP status codes:
  - `MemberNotFoundException` → 404
  - `MembershipPlanNotFoundException` → 422
  - `PaymentAlreadyExistsForMonthException` → 409
  - `InvalidPaymentAmountException` → 422
- [ ] Returns `PaymentResource` with HTTP 201 on success
- [ ] No business logic in the action

---

### TASK-028: Create ListPaymentsAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-019, TASK-025, TASK-026

**Description:**
Create the action for `GET /api/admin/payments`.

**File:** `app/Http/Actions/Payments/List/ListPaymentsAction.php`

**Acceptance Criteria:**
- [ ] Thin action — delegates immediately to handler
- [ ] Returns `PaymentListResource` (paginated)
- [ ] No exception handling needed (list queries return empty, not throw)

---

### TASK-029: Create GetPaymentDetailAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-017, TASK-026

**Description:**
Create the action for `GET /api/admin/payments/{id}`.

**File:** `app/Http/Actions/Payments/Detail/GetPaymentDetailAction.php`

**Acceptance Criteria:**
- [ ] Thin action
- [ ] Catches `PaymentNotFoundException` → 404
- [ ] Returns `PaymentResource`

---

### TASK-030: Create GetOverdueMembersAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-021, TASK-026

**Description:**
Create the action for `GET /api/admin/payments/overdue`.

**File:** `app/Http/Actions/Payments/Overdue/GetOverdueMembersAction.php`

**Acceptance Criteria:**
- [ ] Thin action — no request body needed, no parameters
- [ ] Returns `OverdueMemberResource`
- [ ] No exception handling needed

---

### TASK-031: Create GetMyPaymentsAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-023, TASK-026

**Description:**
Create the action for `GET /api/member/payments`. Resolves the authenticated user's MemberId via `MemberRepositoryInterface::findByUserId()`.

**File:** `app/Http/Actions/MemberPayments/GetMyPaymentsAction.php`

**Acceptance Criteria:**
- [ ] Reads `UserId` from `$request->user()->id`
- [ ] Calls `MemberRepositoryInterface::findByUserId()` to resolve MemberId
- [ ] Returns 404 if no member profile found for the user
- [ ] Returns `MemberPaymentListResource` on success
- [ ] No business logic

---

### TASK-032: Register Routes in api.php

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-027, TASK-028, TASK-029, TASK-030, TASK-031

**Description:**
Register all 5 payment endpoints in `routes/api.php`. Critical: the `/overdue` route must be registered BEFORE the `/{id}` route to prevent Laravel from routing "overdue" as an ID.

**File:** `routes/api.php`

**Acceptance Criteria:**
- [ ] `POST /api/admin/payments` → RecordPaymentAction (middleware: auth:sanctum, role.admin)
- [ ] `GET /api/admin/payments` → ListPaymentsAction (middleware: auth:sanctum, role.admin)
- [ ] `GET /api/admin/payments/overdue` → GetOverdueMembersAction (middleware: auth:sanctum, role.admin) — **registered BEFORE /{id}**
- [ ] `GET /api/admin/payments/{id}` → GetPaymentDetailAction (middleware: auth:sanctum, role.admin)
- [ ] `GET /api/member/payments` → GetMyPaymentsAction (middleware: auth:sanctum, role.member)
- [ ] Consistent naming with existing routes

---

### TASK-033: Bind PaymentRepositoryInterface in AppServiceProvider

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-007, TASK-013

**Description:**
Register the repository binding so Laravel's IoC container knows to inject `PaymentRepository` when `PaymentRepositoryInterface` is type-hinted.

**File:** `app/Providers/AppServiceProvider.php`

**Acceptance Criteria:**
- [ ] `$this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);` added
- [ ] Import statements added at top of file
- [ ] No other changes to this file

---

## Phase 5: Tests

### TASK-034: Unit Tests — Payment Entity

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-002

**Description:**
Unit tests for the `Payment` entity's `create()` factory method.

**File:** `tests/Unit/Billing/Payment/PaymentTest.php`

**Acceptance Criteria:**
- [ ] Test: `create()` derives `billingMonth` correctly from `paymentDate` (e.g., 2026-06-05 → "2026-06")
- [ ] Test: `create()` throws `InvalidPaymentAmountException` when `amountCents = 0`
- [ ] Test: `create()` throws `InvalidPaymentAmountException` when `amountCents < 0`
- [ ] Test: `create()` sets all properties correctly for valid input
- [ ] Test: `billingMonth` is always derived from `paymentDate` — never from external string

---

### TASK-035: Unit Tests — RecordPaymentHandler

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-015

**Description:**
Unit tests for the handler using mocked repositories.

**File:** `tests/Unit/Billing/Payment/RecordPaymentHandlerTest.php`

**Acceptance Criteria:**
- [ ] Test: throws `PaymentAlreadyExistsForMonthException` when duplicate member+month exists
- [ ] Test: throws `MemberNotFoundException` when member does not exist
- [ ] Test: throws `MembershipPlanNotFoundException` when plan does not exist
- [ ] Test: calls `PaymentRepositoryInterface::save()` exactly once on success
- [ ] Test: `recorded_by` is set to the provided admin UserId

---

### TASK-036: Feature Tests — Record Payment Endpoint

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-027, TASK-032

**Description:**
Integration (feature) tests for `POST /api/admin/payments`.

**File:** `tests/Feature/Payments/RecordPaymentTest.php`

**Acceptance Criteria:**
- [ ] Test: 201 created with correct JSON body on success
- [ ] Test: 409 when payment already exists for same member+month
- [ ] Test: 404 when member_id does not exist
- [ ] Test: 422 when amount_cents is 0
- [ ] Test: 422 when membership_plan_id does not exist
- [ ] Test: 401 when unauthenticated
- [ ] Test: 403 when authenticated as member role (not admin)

---

### TASK-037: Feature Tests — Overdue Members Endpoint

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-030, TASK-032

**Description:**
Integration tests for `GET /api/admin/payments/overdue`. This is the most critical endpoint for the business.

**File:** `tests/Feature/Payments/OverdueMembersTest.php`

**Acceptance Criteria:**
- [ ] Test: returns active members who have NO payment for current month
- [ ] Test: does NOT return members who HAVE paid this month
- [ ] Test: does NOT return inactive members
- [ ] Test: `last_payment_date` is null for members who have never paid
- [ ] Test: `last_payment_date` is the correct date for members who paid in a previous month
- [ ] Test: returns empty data array when all active members have paid
- [ ] Test: 403 when authenticated as member role

---

### TASK-038: Feature Tests — Member Self-Service and List Endpoints

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-028, TASK-029, TASK-031, TASK-032

**Description:**
Integration tests for the remaining 3 endpoints.

**Files:**
- `tests/Feature/Payments/ListPaymentsTest.php`
- `tests/Feature/Payments/GetPaymentDetailTest.php`
- `tests/Feature/Payments/MyPaymentsTest.php`

**Acceptance Criteria:**
- [ ] `ListPaymentsTest`: returns paginated list; filters by member_id, year, month work correctly; `meta` contains total, page, per_page
- [ ] `GetPaymentDetailTest`: returns full detail on success; 404 when ID not found
- [ ] `MyPaymentsTest`: member can see own payments; member cannot see other members' payments; 403 if admin role tries to access `/api/member/payments`; results ordered by payment_date DESC

---

## Final Checklist

- [ ] All 38 tasks completed
- [ ] Migration runs cleanly: `php artisan migrate`
- [ ] All unit tests passing: `php artisan test --testsuite=Unit`
- [ ] All feature tests passing: `php artisan test --testsuite=Feature`
- [ ] PHPStan passes at configured level
- [ ] No N+1 queries in overdue list (verify with query log or Telescope)
- [ ] Route order verified: `/overdue` registered before `/{id}`
- [ ] `billing_month` is never accepted from user input (verify RecordPaymentRequest has no billing_month field)
- [ ] All error responses include both `error` (human-readable) and `code` (machine-readable) fields
- [ ] AppServiceProvider binding added and verified
