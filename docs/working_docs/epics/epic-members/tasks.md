# Implementation Tasks: Member Management (epic-members)

**Requirement:** [requirements.md](requirements.md)
**Solution Design:** [design.md](design.md)
**Created:** 2026-06-10
**Total Tasks:** 40
**Estimated Complexity:** L (Large — full bounded context from scratch)

---

## Summary

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Phase 1: Domain | 9 | S–M |
| Phase 2: Infrastructure | 10 | S–M |
| Phase 3: Application (Commands) | 10 | S–M |
| Phase 4: Application (Queries) | 6 | S–M |
| Phase 5: HTTP Layer | 9 | S–M |
| Phase 6: Tests | 9 | M |
| **Total** | **53** | **L** |

---

## Phase 1: Domain Layer

### TASK-001: Create MemberId Value Object

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the ULID-based ID value object for the Member entity, following the exact same pattern as `UserId`.

**File:** `backend/src/Core/Member/Domain/ValueObjects/MemberId.php`

**Acceptance Criteria:**
- [ ] Extends `Symfony\Component\Uid\Ulid`
- [ ] Namespace: `App\Src\Core\Member\Domain\ValueObjects`
- [ ] Has `random(): static`, `fromString(string): static`, `value(): string` methods
- [ ] `value()` returns `$this->toBase32()`

---

### TASK-002: Create MembershipPlanId Value Object

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create the ULID-based ID value object for MembershipPlan references.

**File:** `backend/src/Core/Member/Domain/ValueObjects/MembershipPlanId.php`

**Acceptance Criteria:**
- [ ] Extends `Symfony\Component\Uid\Ulid`
- [ ] Namespace: `App\Src\Core\Member\Domain\ValueObjects`
- [ ] Same interface as MemberId

---

### TASK-003: Create MemberStatus Enum

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create a display-only enum representing member status (mirrors User status for member context). Used in ReadModels and Resources.

**File:** `backend/src/Core/Member/Domain/Enums/MemberStatus.php`

**Acceptance Criteria:**
- [ ] `enum MemberStatus: string`
- [ ] Cases: `PendingApproval = 'pending_approval'`, `Active = 'active'`, `Inactive = 'inactive'`
- [ ] Namespace: `App\Src\Core\Member\Domain\Enums`

---

### TASK-004: Create Member Entity

**Phase:** Domain
**Complexity:** M
**Dependencies:** TASK-001

**Description:**
Create the Member entity with all profile fields, `create()` factory method, and `update()` method. Status is NOT stored in Member — it lives in User. Member is immutable on update (returns new instance).

**File:** `backend/src/Core/Member/Domain/Entities/Member.php`

**Acceptance Criteria:**
- [ ] All properties: `id`, `userId`, `memberNumber`, `firstName`, `lastName`, `phone`, `dateOfBirth`, `profilePhoto`, `joinDate`, `emergencyContactName`, `emergencyContactPhone`, `notes`, `createdAt`
- [ ] All properties `public readonly`
- [ ] Static `create()` factory method with required: id, userId, memberNumber, firstName, lastName, joinDate
- [ ] `update()` method returns a new Member instance with updated profile fields (immutable pattern)
- [ ] No status property — status lives in User
- [ ] No getters/setters
- [ ] Namespace: `App\Src\Core\Member\Domain\Entities`

---

### TASK-005: Create MembershipPlan Entity (read-only)

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-002

**Description:**
Create minimal read-only MembershipPlan entity for plan validation in handlers.

**File:** `backend/src/Core/Member/Domain/Entities/MembershipPlan.php`

**Acceptance Criteria:**
- [ ] Properties: `id` (MembershipPlanId), `name`, `slug`, `priceCents` (int), `classesPerMonth` (?int), `isActive` (bool)
- [ ] All properties `public readonly`
- [ ] Constructor injection only (no factory method needed — read-only)
- [ ] No update/state methods

---

### TASK-006: Create Domain ReadModels

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create two ReadModels for query results: one lightweight (list) and one full (detail).

**Files:**
- `backend/src/Core/Member/Domain/ReadModels/MemberListItemRM.php`
- `backend/src/Core/Member/Domain/ReadModels/MemberDetailRM.php`

**Acceptance Criteria:**
- [ ] `MemberListItemRM`: `id`, `memberNumber`, `firstName`, `lastName`, `email`, `status`, `planName`, `planId`, `joinDate` — all public readonly, all primitives (string/int/null)
- [ ] `MemberDetailRM`: all fields from design.md including full plan info (`planId`, `planName`, `planPriceCents`, `planClassesPerMonth`), emergency contacts, notes
- [ ] Both classes are `final readonly`
- [ ] Namespace: `App\Src\Core\Member\Domain\ReadModels`

---

### TASK-007: Create Domain Exceptions

**Phase:** Domain
**Complexity:** S
**Dependencies:** None

**Description:**
Create three domain exceptions for member operations.

**Files:**
- `backend/src/Core/Member/Domain/Exceptions/MemberNotFoundException.php`
- `backend/src/Core/Member/Domain/Exceptions/MemberEmailAlreadyExistsException.php`
- `backend/src/Core/Member/Domain/Exceptions/MembershipPlanNotFoundException.php`

**Acceptance Criteria:**
- [ ] Each extends `\DomainException` (or `\RuntimeException` for not-found)
- [ ] Namespace: `App\Src\Core\Member\Domain\Exceptions`
- [ ] Meaningful default messages

---

### TASK-008: Create MemberRepositoryInterface

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-001, TASK-004, TASK-006

**Description:**
Create the repository interface (port) for the Member entity.

**File:** `backend/src/Core/Member/Domain/Repositories/MemberRepositoryInterface.php`

**Acceptance Criteria:**
- [ ] Methods: `getById(MemberId): Member` (throws MemberNotFoundException), `findByUserId(UserId): ?Member`, `save(Member): void`, `nextMemberNumber(): int`, `findAll(?string $status, ?string $planId, int $page, int $perPage): array` (returns MemberListItemRM[]), `countAll(?string $status, ?string $planId): int`, `getDetailById(MemberId): MemberDetailRM`, `getDetailByUserId(UserId): MemberDetailRM`
- [ ] All ID parameters use Value Objects
- [ ] PHPDoc on methods that can throw

---

### TASK-009: Create MembershipPlanRepositoryInterface

**Phase:** Domain
**Complexity:** S
**Dependencies:** TASK-002, TASK-005

**Description:**
Create the read-only repository interface for MembershipPlan.

**File:** `backend/src/Core/Member/Domain/Repositories/MembershipPlanRepositoryInterface.php`

**Acceptance Criteria:**
- [ ] Methods: `getById(MembershipPlanId): MembershipPlan` (throws MembershipPlanNotFoundException), `findAllActive(): array` (returns MembershipPlan[])
- [ ] ID parameters use MembershipPlanId Value Object

---

## Phase 2: Infrastructure Layer

### TASK-010: Create MemberPlanAssignmentTable Constants

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** None

**Description:**
Create the Table constants class for the `member_plan_assignments` table.

**File:** `backend/src/Core/Member/Infrastructure/Tables/MemberPlanAssignmentTable.php`

**Acceptance Criteria:**
- [ ] `TABLE_NAME = 'member_plan_assignments'`
- [ ] Constants: `ID`, `MEMBER_ID`, `MEMBERSHIP_PLAN_ID`, `ASSIGNED_AT`, `CREATED_AT`, `UPDATED_AT`
- [ ] PHPDoc comments listing all properties with types
- [ ] Namespace: `App\Src\Core\Member\Infrastructure\Tables`

---

### TASK-011: Migration — Add deleted_at to members

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** None

**Description:**
Add soft delete support to the existing `members` table.

**File:** `backend/database/migrations/2026_06_10_000010_add_deleted_at_to_members_table.php`

**Acceptance Criteria:**
- [ ] `up()` adds `deleted_at TIMESTAMP NULL DEFAULT NULL` after `updated_at`
- [ ] Adds index on `deleted_at`
- [ ] `down()` drops the column
- [ ] Uses `Schema::table()` (not `Schema::create()`)

---

### TASK-012: Migration — Create member_plan_assignments table

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-010

**Description:**
Create the plan assignment pivot table.

**File:** `backend/database/migrations/2026_06_10_000011_create_member_plan_assignments_table.php`

**Acceptance Criteria:**
- [ ] `id` CHAR(26) PRIMARY KEY
- [ ] `member_id` CHAR(26) NOT NULL, FK → members.id ON DELETE CASCADE
- [ ] `membership_plan_id` CHAR(26) NOT NULL, FK → membership_plans.id
- [ ] `assigned_at` DATE NOT NULL
- [ ] `timestamps()`
- [ ] Composite index on (member_id, assigned_at)
- [ ] `down()` drops the table

---

### TASK-013: Create Eloquent Models

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-011, TASK-012

**Description:**
Create three Eloquent models for Member, MembershipPlan, and MemberPlanAssignment.

**Files:**
- `backend/src/Core/Member/Infrastructure/Persistence/MemberModel.php`
- `backend/src/Core/Member/Infrastructure/Persistence/MembershipPlanModel.php`
- `backend/src/Core/Member/Infrastructure/Persistence/MemberPlanAssignmentModel.php`

**Acceptance Criteria:**
- [ ] `MemberModel`: `use SoftDeletes`, non-incrementing ULID PK, fillable fields from MemberTable constants, casts for date_of_birth and deleted_at
- [ ] `MembershipPlanModel`: read-only (no fillable needed beyond basic setup), non-incrementing ULID PK
- [ ] `MemberPlanAssignmentModel`: non-incrementing ULID PK, fillable from MemberPlanAssignmentTable constants
- [ ] All use their respective Table constants (no string literals)

---

### TASK-014: Create MemberHydrator

**Phase:** Infrastructure
**Complexity:** M
**Dependencies:** TASK-004, TASK-013

**Description:**
Create hydrator to transform between MemberModel and Member entity.

**File:** `backend/src/Core/Member/Infrastructure/Hydrators/MemberHydrator.php`

**Acceptance Criteria:**
- [ ] `hydrate(MemberModel): Member` — maps all fields, creates Value Objects, handles nullable DateTimeImmutable
- [ ] `dehydrate(Member): array` — returns associative array with MemberTable constants as keys
- [ ] Uses MemberTable constants (not string literals)
- [ ] `date_of_birth` formatted as `Y-m-d` in dehydrate, parsed with `new \DateTimeImmutable()` in hydrate
- [ ] `join_date` formatted as `Y-m-d`

---

### TASK-015: Create MembershipPlanHydrator

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-005, TASK-013

**Description:**
Create hydrator for MembershipPlan entity.

**File:** `backend/src/Core/Member/Infrastructure/Hydrators/MembershipPlanHydrator.php`

**Acceptance Criteria:**
- [ ] `hydrate(MembershipPlanModel): MembershipPlan`
- [ ] Maps `is_active` tinyint to `bool`
- [ ] Uses MembershipPlanTable constants

---

### TASK-016: Create MemberRepository

**Phase:** Infrastructure
**Complexity:** L
**Dependencies:** TASK-008, TASK-013, TASK-014

**Description:**
Create the Eloquent-based repository implementation. This is the most complex task — see design.md for the SQL query pattern for `findAll()`.

**File:** `backend/src/Core/Member/Infrastructure/Repositories/MemberRepository.php`

**Acceptance Criteria:**
- [ ] Implements `MemberRepositoryInterface`
- [ ] `getById()`: queries MemberModel with `whereNull('deleted_at')`, throws MemberNotFoundException if null
- [ ] `findByUserId()`: queries by user_id, returns null if not found
- [ ] `save()`: uses `MemberModel::updateOrCreate([MemberTable::ID => ...], ...)`
- [ ] `nextMemberNumber()`: `DB::table(MemberTable::TABLE_NAME)->lockForUpdate()->max(MemberTable::MEMBER_NUMBER) + 1` — uses DB transaction lock
- [ ] `findAll()`: single JOIN query with correlated subquery for latest plan (see design.md SQL). Returns `MemberListItemRM[]`. NO N+1 queries.
- [ ] `countAll()`: same filters as findAll but returns `int` count
- [ ] `getDetailById()`: single JOIN query returning `MemberDetailRM`
- [ ] `getDetailByUserId()`: same as getDetailById but joins via user_id
- [ ] Uses MemberTable and related constants throughout (no string literals)

---

### TASK-017: Create MembershipPlanRepository

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-009, TASK-013, TASK-015

**Description:**
Create the read-only plan repository implementation.

**File:** `backend/src/Core/Member/Infrastructure/Repositories/MembershipPlanRepository.php`

**Acceptance Criteria:**
- [ ] Implements `MembershipPlanRepositoryInterface`
- [ ] `getById()`: throws MembershipPlanNotFoundException if not found
- [ ] `findAllActive()`: filters by `is_active = 1`, returns MembershipPlan[]
- [ ] Uses MembershipPlanTable constants

---

### TASK-018: Register Service Provider Bindings

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-016, TASK-017

**Description:**
Register the new repository interface→implementation bindings in the service container.

**File:** `backend/app/Providers/AppServiceProvider.php`

**Acceptance Criteria:**
- [ ] `$this->app->bind(MemberRepositoryInterface::class, MemberRepository::class)`
- [ ] `$this->app->bind(MembershipPlanRepositoryInterface::class, MembershipPlanRepository::class)`
- [ ] Imports added correctly

---

### TASK-019: Register MemberModel in AppServiceProvider (SoftDeletes global scope)

**Phase:** Infrastructure
**Complexity:** S
**Dependencies:** TASK-013

**Description:**
Verify that SoftDeletes trait properly excludes deleted members from all queries. No extra configuration needed beyond the trait, but document and verify.

**File:** `backend/src/Core/Member/Infrastructure/Persistence/MemberModel.php`

**Acceptance Criteria:**
- [ ] `use SoftDeletes` is present on MemberModel
- [ ] `deleted_at` is in the `$casts` array
- [ ] A quick manual check confirms that `MemberModel::query()->get()` excludes soft-deleted rows

---

## Phase 3: Application Layer — Commands

### TASK-020: Create CreateMemberCommand + Handler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-004, TASK-008, TASK-009, TASK-016

**Description:**
Create command and handler for member creation. Handler is transactional: creates User, creates Member, creates plan assignment atomically.

**Files:**
- `backend/src/Core/Member/Application/Commands/CreateMember/CreateMemberCommand.php`
- `backend/src/Core/Member/Application/Commands/CreateMember/CreateMemberHandler.php`

**Acceptance Criteria:**
- [ ] Command: `final class` with readonly constructor props: `memberId`, `userId`, `email`, `firstName`, `lastName`, `joinDate`, `planId`, `phone`, `dateOfBirth`
- [ ] Handler `handle()` returns `void`
- [ ] Checks email uniqueness via `UserRepositoryInterface::findByEmail()` — throws `MemberEmailAlreadyExistsException` if found
- [ ] Validates plan is active via `MembershipPlanRepositoryInterface::getById()` — throws `MembershipPlanNotFoundException` if not found or not active
- [ ] Gets next member number via `MemberRepository::nextMemberNumber()`
- [ ] Wraps all writes in `DB::transaction()`:
  - Creates User: `UserModel::create([id, email, password=bcrypt(Str::random(16)), role='member', status='pending_approval', must_change_password=1])`
  - Creates Member: `Member::create(...)` + `MemberRepository::save(...)`
  - Creates MemberPlanAssignment: `MemberPlanAssignmentModel::create([id=ulid, member_id, membership_plan_id, assigned_at=today])`
- [ ] If transaction fails, exception bubbles up (no partial state)

---

### TASK-021: Create UpdateMemberCommand + Handler

**Phase:** Application
**Complexity:** M
**Dependencies:** TASK-004, TASK-008, TASK-016

**Description:**
Create command and handler for updating a member's profile fields.

**Files:**
- `backend/src/Core/Member/Application/Commands/UpdateMember/UpdateMemberCommand.php`
- `backend/src/Core/Member/Application/Commands/UpdateMember/UpdateMemberHandler.php`

**Acceptance Criteria:**
- [ ] Command: readonly props: `memberId`, `firstName`, `lastName`, `phone`, `dateOfBirth`, `emergencyContactName`, `emergencyContactPhone`, `notes`, `profilePhoto`
- [ ] Handler: `getById()` → `member->update(...)` → `save()`
- [ ] Handler returns `void`
- [ ] Throws `MemberNotFoundException` if member not found
- [ ] `joinDate` and `email` are NOT in the command (not editable)

---

### TASK-022: Create AssignMembershipPlanCommand + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-008, TASK-009

**Description:**
Create command and handler for assigning a new membership plan to a member.

**Files:**
- `backend/src/Core/Member/Application/Commands/AssignMembershipPlan/AssignMembershipPlanCommand.php`
- `backend/src/Core/Member/Application/Commands/AssignMembershipPlan/AssignMembershipPlanHandler.php`

**Acceptance Criteria:**
- [ ] Command: `memberId` (MemberId), `planId` (MembershipPlanId)
- [ ] Handler verifies member exists via `MemberRepository::getById()`
- [ ] Handler verifies plan is active via `MembershipPlanRepositoryInterface::getById()`
- [ ] Creates new `MemberPlanAssignmentModel` with today as `assigned_at`
- [ ] Does NOT delete old assignments (append-only history)
- [ ] Returns `void`

---

### TASK-023: Create ActivateMemberCommand + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-008, TASK-016

**Description:**
Create command and handler to activate a member (pending_approval or inactive → active).

**Files:**
- `backend/src/Core/Member/Application/Commands/ActivateMember/ActivateMemberCommand.php`
- `backend/src/Core/Member/Application/Commands/ActivateMember/ActivateMemberHandler.php`

**Acceptance Criteria:**
- [ ] Command: `memberId` (MemberId)
- [ ] Handler: `MemberRepository::getById()` → `UserRepository::getById(member->userId)` → calls `user->approve()` if status is `pending_approval`, `user->activate()` if status is `inactive` → `UserRepository::save(user)`
- [ ] `InvalidStatusTransitionException` (from existing Auth domain) bubbles up if user is already active
- [ ] Returns `void`

---

### TASK-024: Create DeactivateMemberCommand + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-008, TASK-016

**Description:**
Create command and handler to deactivate an active member. Must also revoke all Sanctum tokens.

**Files:**
- `backend/src/Core/Member/Application/Commands/DeactivateMember/DeactivateMemberCommand.php`
- `backend/src/Core/Member/Application/Commands/DeactivateMember/DeactivateMemberHandler.php`

**Acceptance Criteria:**
- [ ] Command: `memberId` (MemberId)
- [ ] Handler: `MemberRepository::getById()` → `UserRepository::getById(member->userId)` → `user->deactivate()` → `UserRepository::save(user)` → `UserModel::query()->where('id', $userId)->firstOrFail()->tokens()->delete()`
- [ ] `InvalidStatusTransitionException` bubbles up if user is not active
- [ ] Returns `void`
- [ ] Token revocation happens AFTER status update (if token revocation fails, status is still updated — acceptable for MVP)

---

## Phase 4: Application Layer — Queries

### TASK-025: Create GetMemberByIdQuery + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-006, TASK-008, TASK-016

**Description:**
Create query and handler to retrieve full member detail by MemberId.

**Files:**
- `backend/src/Core/Member/Application/Queries/GetMemberById/GetMemberByIdQuery.php`
- `backend/src/Core/Member/Application/Queries/GetMemberById/GetMemberByIdHandler.php`

**Acceptance Criteria:**
- [ ] Query: `memberId` (MemberId)
- [ ] Handler calls `MemberRepository::getDetailById()` — returns `MemberDetailRM`
- [ ] Throws `MemberNotFoundException` if not found (propagated from repository)
- [ ] Returns `MemberDetailRM` directly (ReadModel, not entity)

---

### TASK-026: Create ListMembersQuery + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-006, TASK-008, TASK-016

**Description:**
Create query and handler for the paginated member list with optional filters.

**Files:**
- `backend/src/Core/Member/Application/Queries/ListMembers/ListMembersQuery.php`
- `backend/src/Core/Member/Application/Queries/ListMembers/ListMembersHandler.php`

**Acceptance Criteria:**
- [ ] Query: optional `status` (string), optional `planId` (string), `page` (int, default 1), `perPage` (int, default 20)
- [ ] Handler calls `MemberRepository::findAll()` and `MemberRepository::countAll()`
- [ ] Returns `array{items: MemberListItemRM[], total: int, page: int, perPage: int}`
- [ ] NO N+1 queries — `findAll()` uses a single JOIN query

---

### TASK-027: Create GetMemberProfileQuery + Handler

**Phase:** Application
**Complexity:** S
**Dependencies:** TASK-006, TASK-008, TASK-016

**Description:**
Create query and handler for a member to retrieve their own profile by UserId.

**Files:**
- `backend/src/Core/Member/Application/Queries/GetMemberProfile/GetMemberProfileQuery.php`
- `backend/src/Core/Member/Application/Queries/GetMemberProfile/GetMemberProfileHandler.php`

**Acceptance Criteria:**
- [ ] Query: `userId` (UserId)
- [ ] Handler calls `MemberRepository::getDetailByUserId(userId)`
- [ ] Throws `MemberNotFoundException` if no Member linked to this UserId
- [ ] Returns `MemberDetailRM`

---

## Phase 5: HTTP Layer

### TASK-028: Create DTOs and Request classes

**Phase:** HTTP
**Complexity:** S
**Dependencies:** None (DTOs are pure data classes)

**Description:**
Create all DTO classes and their corresponding Request classes.

**Files:**
- `backend/app/Http/Actions/Members/Create/CreateMemberDto.php` + `CreateMemberRequest.php`
- `backend/app/Http/Actions/Members/Update/UpdateMemberDto.php` + `UpdateMemberRequest.php`
- `backend/app/Http/Actions/Members/AssignPlan/AssignMemberPlanDto.php` + `AssignMemberPlanRequest.php`
- `backend/app/Http/Actions/Members/List/ListMembersDto.php` + `ListMembersRequest.php`

**Acceptance Criteria:**
- [ ] All DTOs are `final readonly` classes
- [ ] All Requests extend `Illuminate\Foundation\Http\FormRequest`
- [ ] All Requests have `authorize(): bool { return true; }` (middleware handles auth)
- [ ] All Requests have ONLY `getDto()` method — NO `rules()`, NO `after()`
- [ ] `getDto()` uses `$this->input()` for all field access
- [ ] Nullable fields mapped as `$this->input('field') ? (string) $this->input('field') : null`
- [ ] Date fields parsed as `new \DateTimeImmutable((string) $this->input('field'))` with fallback

---

### TASK-029: Create MemberResource and MemberListResource

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-006

**Description:**
Create the two HTTP response resource classes.

**Files:**
- `backend/app/Http/Actions/Members/Shared/MemberResource.php`
- `backend/app/Http/Actions/Members/Shared/MemberListResource.php`

**Acceptance Criteria:**
- [ ] `MemberResource`: wraps `MemberDetailRM`, `toResponse(int $status = 200): JsonResponse`, includes all fields from design.md response shape, `plan` is null if no plan assigned
- [ ] `MemberListResource`: wraps `MemberListItemRM[]` + pagination meta, `toResponse(): JsonResponse`, returns `{data: [...], meta: {total, page, per_page}}`
- [ ] No `ñ` in any Spanish UI strings — use `n` instead
- [ ] All IDs serialized as strings via `.value()` (but since ReadModels use primitives already, no conversion needed)

---

### TASK-030: Create CreateMemberAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-020, TASK-025, TASK-028, TASK-029

**Description:**
Create the HTTP Action for member creation.

**File:** `backend/app/Http/Actions/Members/Create/CreateMemberAction.php`

**Acceptance Criteria:**
- [ ] `__invoke(CreateMemberRequest $request): JsonResponse`
- [ ] Generates `MemberId::random()` and `UserId::random()` before dispatching
- [ ] Catches `MemberEmailAlreadyExistsException` → 409
- [ ] Catches `MembershipPlanNotFoundException` → 422
- [ ] On success: calls `GetMemberByIdHandler` with new memberId, returns `MemberResource::toResponse(201)`
- [ ] Max 25 lines of code

---

### TASK-031: Create UpdateMemberAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-021, TASK-025, TASK-028, TASK-029

**Description:**
Create the HTTP Action for updating a member profile.

**File:** `backend/app/Http/Actions/Members/Update/UpdateMemberAction.php`

**Acceptance Criteria:**
- [ ] `__invoke(UpdateMemberRequest $request, string $id): JsonResponse`
- [ ] Catches `MemberNotFoundException` → 404
- [ ] On success: returns updated `MemberResource::toResponse()`
- [ ] Max 20 lines

---

### TASK-032: Create AssignMemberPlanAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-022, TASK-025, TASK-028, TASK-029

**Description:**
Create the HTTP Action for plan assignment.

**File:** `backend/app/Http/Actions/Members/AssignPlan/AssignMemberPlanAction.php`

**Acceptance Criteria:**
- [ ] `__invoke(AssignMemberPlanRequest $request, string $id): JsonResponse`
- [ ] Catches `MemberNotFoundException` → 404
- [ ] Catches `MembershipPlanNotFoundException` → 422
- [ ] Returns updated `MemberResource::toResponse()`

---

### TASK-033: Create ActivateMemberAction and DeactivateMemberAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-023, TASK-024, TASK-025, TASK-029

**Description:**
Create the HTTP Actions for activate and deactivate operations.

**Files:**
- `backend/app/Http/Actions/Members/Activate/ActivateMemberAction.php`
- `backend/app/Http/Actions/Members/Deactivate/DeactivateMemberAction.php`

**Acceptance Criteria:**
- [ ] Both `__invoke(string $id): JsonResponse` — no request body
- [ ] Catch `MemberNotFoundException` → 404
- [ ] Catch `InvalidStatusTransitionException` (from Auth domain) → 422
- [ ] Return updated `MemberResource::toResponse()`

---

### TASK-034: Create ListMembersAction and GetMemberDetailAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-026, TASK-025, TASK-028, TASK-029

**Description:**
Create Actions for listing members and getting a single member detail.

**Files:**
- `backend/app/Http/Actions/Members/List/ListMembersAction.php`
- `backend/app/Http/Actions/Members/Detail/GetMemberDetailAction.php`

**Acceptance Criteria:**
- [ ] `ListMembersAction::__invoke(ListMembersRequest $request): JsonResponse` — returns `MemberListResource::toResponse()`
- [ ] `GetMemberDetailAction::__invoke(string $id): JsonResponse` — catches `MemberNotFoundException` → 404, returns `MemberResource::toResponse()`

---

### TASK-035: Create GetMemberProfileAction

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-027, TASK-029

**Description:**
Create the HTTP Action for a member to view their own profile.

**File:** `backend/app/Http/Actions/MemberProfile/GetMemberProfileAction.php`

**Acceptance Criteria:**
- [ ] `__invoke(Request $request): JsonResponse`
- [ ] Reads `$request->user()->id` to get the authenticated UserId
- [ ] Calls `GetMemberProfileHandler::handle(new GetMemberProfileQuery(UserId::fromString($userId)))`
- [ ] Catches `MemberNotFoundException` → 404
- [ ] Returns `MemberResource::toResponse()`

---

### TASK-036: Register Routes in api.php

**Phase:** HTTP
**Complexity:** S
**Dependencies:** TASK-030 through TASK-035

**Description:**
Add all member endpoints to the API routes file.

**File:** `backend/routes/api.php`

**Acceptance Criteria:**
- [ ] Admin routes grouped under `Route::prefix('admin')->middleware(['auth:sanctum', 'role.admin', 'password.change'])`
- [ ] Member routes grouped under `Route::prefix('member')->middleware(['auth:sanctum', 'role.member', 'password.change'])`
- [ ] All 8 endpoints registered (POST members, GET members, GET members/{id}, PUT members/{id}, PUT members/{id}/plan, PUT members/{id}/activate, PUT members/{id}/deactivate, GET member/profile)
- [ ] Route parameter `{id}` used consistently
- [ ] No typos in route names

---

## Phase 6: Tests

### TASK-037: Unit Tests — Member Entity

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-004

**Description:**
Write unit tests for the Member entity.

**File:** `backend/tests/Unit/Core/Member/MemberTest.php`

**Acceptance Criteria:**
- [ ] `create()` with all required fields succeeds
- [ ] `create()` assigns correct initial values
- [ ] `update()` returns new instance with updated fields
- [ ] `update()` does not mutate original instance (immutable)
- [ ] `joinDate` is preserved on update (not changeable)

---

### TASK-038: Unit Tests — CreateMemberHandler

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-020

**Description:**
Write unit tests for the CreateMemberHandler with mocked repositories.

**File:** `backend/tests/Unit/Core/Member/CreateMemberHandlerTest.php`

**Acceptance Criteria:**
- [ ] Success case: User created, Member created, plan assignment created (all mocked)
- [ ] Email already exists → `MemberEmailAlreadyExistsException`
- [ ] Plan not found → `MembershipPlanNotFoundException`
- [ ] Plan inactive → `MembershipPlanNotFoundException`
- [ ] Transaction failure → exception propagated, no partial state

---

### TASK-039: Unit Tests — Activate/Deactivate Handlers

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-023, TASK-024

**Description:**
Write unit tests for status transition handlers.

**File:** `backend/tests/Unit/Core/Member/MemberStatusHandlersTest.php`

**Acceptance Criteria:**
- [ ] Activate pending_approval member → status becomes active
- [ ] Activate inactive member → status becomes active
- [ ] Activate already active member → `InvalidStatusTransitionException`
- [ ] Deactivate active member → status becomes inactive
- [ ] Deactivate inactive member → `InvalidStatusTransitionException`
- [ ] Deactivation triggers token delete (mock UserModel tokens)

---

### TASK-040: Feature Tests — Create Member (POST /api/admin/members)

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-036

**Description:**
Write feature tests for the create member endpoint.

**File:** `backend/tests/Feature/Members/CreateMemberTest.php`

**Acceptance Criteria:**
- [ ] Authenticated admin can create member → 201 with member data
- [ ] Duplicate email → 409
- [ ] Non-existent plan → 422
- [ ] Missing required fields → 422 (validation at handler level)
- [ ] Non-admin (coach or member) → 403
- [ ] Unauthenticated → 401

---

### TASK-041: Feature Tests — List Members (GET /api/admin/members)

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-036

**Description:**
Write feature tests for the member list endpoint with filters.

**File:** `backend/tests/Feature/Members/ListMembersTest.php`

**Acceptance Criteria:**
- [ ] Returns paginated list with meta
- [ ] Filter by status=active returns only active members
- [ ] Filter by plan_id returns only members with that plan
- [ ] Combined filters work correctly
- [ ] Non-admin → 403

---

### TASK-042: Feature Tests — Activate/Deactivate

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-036

**Description:**
Write feature tests for status transition endpoints.

**File:** `backend/tests/Feature/Members/MemberStatusTest.php`

**Acceptance Criteria:**
- [ ] Activate pending member → 200, status=active
- [ ] Activate already active → 422
- [ ] Deactivate active → 200, status=inactive, tokens revoked
- [ ] Deactivate inactive → 422
- [ ] Non-existent member → 404

---

### TASK-043: Feature Tests — Member Self-Profile (GET /api/member/profile)

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-036

**Description:**
Write feature tests for the member self-profile endpoint.

**File:** `backend/tests/Feature/Members/MemberProfileTest.php`

**Acceptance Criteria:**
- [ ] Authenticated member sees own profile → 200
- [ ] Response includes current plan details
- [ ] Admin trying to access /member/profile → 403 (wrong role middleware)
- [ ] Unauthenticated → 401
- [ ] Member cannot access /api/admin/members → 403

---

### TASK-044: Feature Tests — Update Member and Assign Plan

**Phase:** Tests
**Complexity:** M
**Dependencies:** TASK-036

**Description:**
Write feature tests for profile update and plan assignment.

**File:** `backend/tests/Feature/Members/UpdateMemberTest.php`

**Acceptance Criteria:**
- [ ] Admin updates member profile → 200 with updated data
- [ ] Email not changed even if sent in body
- [ ] Assign new plan → 200, plan updated in response
- [ ] Assign non-existent plan → 422
- [ ] Non-existent member → 404

---

### TASK-045: Feature Tests — Get Member Detail

**Phase:** Tests
**Complexity:** S
**Dependencies:** TASK-036

**Description:**
Write feature tests for GET /api/admin/members/{id}.

**File:** `backend/tests/Feature/Members/GetMemberDetailTest.php`

**Acceptance Criteria:**
- [ ] Admin retrieves existing member → 200 with all fields
- [ ] Non-existent member → 404
- [ ] Non-admin → 403

---

## Final Checklist

- [ ] All 45 tasks completed
- [ ] `php artisan migrate` runs without errors
- [ ] `php artisan test` passes (all unit + feature tests green)
- [ ] PHPStan passes at configured level
- [ ] No N+1 queries in list endpoint (verified via Laravel Debugbar or code review)
- [ ] All Spanish UI strings use `n` instead of `n with tilde`
- [ ] All endpoints require correct middleware (verified in feature tests)
- [ ] Token revocation on deactivation verified in feature test
- [ ] API contracts documented (design.md covers response shapes)
