# Solution Design: Member Management (epic-members)

**Requirement:** [requirements.md](requirements.md)
**Validation:** [validation.md](validation.md)
**Date:** 2026-06-10
**Status:** Draft
**Bounded Context:** `Core/Member`

---

## Summary

Build the `Core/Member` bounded context from scratch within the existing Laravel DDD structure. The `members` and `membership_plans` tables already exist (created in epic-foundation). A new `member_plan_assignments` table is needed. The design reuses the existing `Shared/Auth` User entity for authentication state; Member is a profile entity linked 1:1 to a User.

All patterns follow the established `Shared/Auth` precedent: UserId-style Value Objects, Hydrator pattern, Repository interface + implementation, thin Actions with explicit exception handling in the HTTP layer.

---

## Architecture Decision

**Member profile is separate from User.** User holds credentials and auth state. Member holds gym-specific profile data. This is consistent with the existing design (epic-foundation design doc) and allows Auth to evolve independently.

**Plan assignment is append-only via a pivot table** (`member_plan_assignments`). This preserves history without requiring soft delete on the assignment. The "current plan" is always the most recent assignment by `assigned_at`. No end_date column needed in MVP.

**CreateMember is transactional:** User creation + Member creation + plan assignment happen in one DB transaction inside the handler. If any step fails, all are rolled back.

**Deactivation revokes tokens:** The DeactivateMemberHandler calls `UserModel::tokens()->delete()` after updating the User status. This is an infrastructure concern acceptable in the handler since it directly accesses the Eloquent model for a security operation.

**No Command/Query Bus in this epic.** Consistent with the existing Auth implementation, Actions directly inject and call Handlers. The project does not yet have a Bus infrastructure.

---

## Existing Code Analysis

| Component | Location | Reusable | Modifications Needed |
|-----------|----------|----------|---------------------|
| UserId | `src/Shared/Auth/Domain/ValueObjects/UserId.php` | Yes | None |
| UserRepositoryInterface | `src/Shared/Auth/Domain/Repositories/UserRepositoryInterface.php` | Yes | None — inject directly |
| UserRepository | `src/Shared/Auth/Infrastructure/Repositories/UserRepository.php` | Yes | None |
| UserModel | `src/Shared/Auth/Infrastructure/Persistence/UserModel.php` | Yes | None |
| UserHydrator | `src/Shared/Auth/Infrastructure/Hydrators/UserHydrator.php` | Yes | None |
| UserTable | `src/Shared/Auth/Infrastructure/Tables/UserTable.php` | Yes | None |
| MemberTable | `src/Core/Member/Infrastructure/Tables/MemberTable.php` | Yes | None — already has all columns |
| MembershipPlanTable | `src/Core/Member/Infrastructure/Tables/MembershipPlanTable.php` | Yes | None |
| UserStatus enum | `src/Shared/Auth/Domain/Enums/UserStatus.php` | Yes | None — reuse pending_approval/active/inactive |
| UserRole enum | `src/Shared/Auth/Domain/Enums/UserRole.php` | Yes | None |
| LoginAction pattern | `app/Http/Actions/Auth/Login/LoginAction.php` | Reference | Follow same pattern |
| api.php | `routes/api.php` | Extend | Add new route groups |

---

## Implementation Plan

### 1. Domain Layer — `backend/src/Core/Member/`

#### Value Objects

| VO | File Path | Description |
|----|-----------|-------------|
| MemberId | `src/Core/Member/Domain/ValueObjects/MemberId.php` | ULID-based ID for Member entity |
| MembershipPlanId | `src/Core/Member/Domain/ValueObjects/MembershipPlanId.php` | ULID-based ID for MembershipPlan (read-only reference) |

```php
// MemberId
final class MemberId extends Ulid {
    public static function random(): static { return new static(); }
    public static function fromString(string $value): static { return new static($value); }
    public function value(): string { return $this->toBase32(); }
}

// MembershipPlanId — identical pattern
final class MembershipPlanId extends Ulid { ... }
```

#### Enums

| Enum | File Path | Description |
|------|-----------|-------------|
| MemberStatus | `src/Core/Member/Domain/Enums/MemberStatus.php` | Mirrors User status for member context |

```php
// MemberStatus — a view of User status from the Member perspective
// Values: pending_approval, active, inactive
// NOT an autonomous enum — derives from User.status()
// Used only in ReadModels and Resources for display
enum MemberStatus: string {
    case PendingApproval = 'pending_approval';
    case Active          = 'active';
    case Inactive        = 'inactive';
}
```

#### Entities

| Entity | File Path | Description |
|--------|-----------|-------------|
| Member | `src/Core/Member/Domain/Entities/Member.php` | Gym member profile linked to User |

```php
// Member entity — profile data only, status lives in User
final class Member {
    public function __construct(
        public readonly MemberId     $id,
        public readonly UserId       $userId,
        public readonly int          $memberNumber,
        public readonly string       $firstName,
        public readonly string       $lastName,
        public readonly ?string      $phone,
        public readonly ?\DateTimeImmutable $dateOfBirth,
        public readonly ?string      $profilePhoto,
        public readonly \DateTimeImmutable  $joinDate,
        public readonly ?string      $emergencyContactName,
        public readonly ?string      $emergencyContactPhone,
        public readonly ?string      $notes,
        public readonly \DateTimeImmutable  $createdAt,
    ) {}

    public static function create(
        MemberId    $id,
        UserId      $userId,
        int         $memberNumber,
        string      $firstName,
        string      $lastName,
        \DateTimeImmutable $joinDate,
        ?string     $phone = null,
        ?\DateTimeImmutable $dateOfBirth = null,
    ): self {
        return new self(
            id: $id, userId: $userId, memberNumber: $memberNumber,
            firstName: $firstName, lastName: $lastName, phone: $phone,
            dateOfBirth: $dateOfBirth, profilePhoto: null,
            joinDate: $joinDate, emergencyContactName: null,
            emergencyContactPhone: null, notes: null,
            createdAt: new \DateTimeImmutable(),
        );
    }

    public function update(
        string $firstName, string $lastName, ?string $phone,
        ?\DateTimeImmutable $dateOfBirth, ?string $emergencyContactName,
        ?string $emergencyContactPhone, ?string $notes, ?string $profilePhoto,
    ): self {
        // Returns new immutable instance with updated fields
        return new self(
            id: $this->id, userId: $this->userId, memberNumber: $this->memberNumber,
            firstName: $firstName, lastName: $lastName, phone: $phone,
            dateOfBirth: $dateOfBirth, profilePhoto: $profilePhoto,
            joinDate: $this->joinDate, emergencyContactName: $emergencyContactName,
            emergencyContactPhone: $emergencyContactPhone, notes: $notes,
            createdAt: $this->createdAt,
        );
    }
}
```

#### Exceptions

| Exception | File Path | HTTP Status |
|-----------|-----------|-------------|
| MemberNotFoundException | `src/Core/Member/Domain/Exceptions/MemberNotFoundException.php` | 404 |
| MemberEmailAlreadyExistsException | `src/Core/Member/Domain/Exceptions/MemberEmailAlreadyExistsException.php` | 409 |
| MembershipPlanNotFoundException | `src/Core/Member/Domain/Exceptions/MembershipPlanNotFoundException.php` | 422 |

#### Read Models

| ReadModel | File Path | Used By |
|-----------|-----------|---------|
| MemberListItemRM | `src/Core/Member/Domain/ReadModels/MemberListItemRM.php` | ListMembersHandler — lightweight list row |
| MemberDetailRM | `src/Core/Member/Domain/ReadModels/MemberDetailRM.php` | GetMemberByIdHandler, GetMemberProfileHandler |

```php
// MemberListItemRM — lightweight, no relations loaded
final readonly class MemberListItemRM {
    public function __construct(
        public string $id,
        public int    $memberNumber,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $status,         // UserStatus value
        public ?string $planName,      // From JOIN with membership_plans
        public ?string $planId,
        public string $joinDate,
    ) {}
}

// MemberDetailRM — full profile including current plan
final readonly class MemberDetailRM {
    public function __construct(
        public string  $id,
        public string  $userId,
        public int     $memberNumber,
        public string  $firstName,
        public string  $lastName,
        public string  $email,
        public ?string $phone,
        public ?string $dateOfBirth,
        public ?string $profilePhoto,
        public string  $joinDate,
        public string  $status,
        public ?string $planId,
        public ?string $planName,
        public ?int    $planPriceCents,
        public ?int    $planClassesPerMonth,
        public string  $createdAt,
        public ?string $emergencyContactName,
        public ?string $emergencyContactPhone,
        public ?string $notes,
    ) {}
}
```

#### Repository Interface

```php
// src/Core/Member/Domain/Repositories/MemberRepositoryInterface.php

interface MemberRepositoryInterface {
    /** @throws MemberNotFoundException */
    public function getById(MemberId $id): Member;

    public function findByUserId(UserId $userId): ?Member;

    public function save(Member $member): void;

    public function nextMemberNumber(): int;

    /** @return MemberListItemRM[] */
    public function findAll(?string $status, ?string $planId, int $page, int $perPage): array;

    public function countAll(?string $status, ?string $planId): int;

    /** @throws MemberNotFoundException */
    public function getDetailById(MemberId $id): MemberDetailRM;

    /** @throws MemberNotFoundException */
    public function getDetailByUserId(UserId $userId): MemberDetailRM;
}
```

#### MembershipPlan Repository Interface (read-only)

```php
// src/Core/Member/Domain/Repositories/MembershipPlanRepositoryInterface.php

interface MembershipPlanRepositoryInterface {
    /** @throws MembershipPlanNotFoundException */
    public function getById(MembershipPlanId $id): MembershipPlan;

    /** @return MembershipPlan[] */
    public function findAllActive(): array;
}
```

#### MembershipPlan Entity (read-only, minimal)

```php
// src/Core/Member/Domain/Entities/MembershipPlan.php
final class MembershipPlan {
    public function __construct(
        public readonly MembershipPlanId $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly int $priceCents,
        public readonly ?int $classesPerMonth,
        public readonly bool $isActive,
    ) {}
}
```

---

### 2. Infrastructure Layer — `backend/src/Core/Member/Infrastructure/`

#### New Table Constants

| Class | File Path | Table |
|-------|-----------|-------|
| MemberPlanAssignmentTable | `src/Core/Member/Infrastructure/Tables/MemberPlanAssignmentTable.php` | `member_plan_assignments` |

```php
// MemberPlanAssignmentTable
// @property string id            ULID PK
// @property string member_id     FK -> members.id
// @property string membership_plan_id   FK -> membership_plans.id
// @property string assigned_at   DATE — when this plan was assigned
// @property string created_at
// @property string updated_at
final class MemberPlanAssignmentTable {
    public const TABLE_NAME         = 'member_plan_assignments';
    public const ID                 = 'id';
    public const MEMBER_ID          = 'member_id';
    public const MEMBERSHIP_PLAN_ID = 'membership_plan_id';
    public const ASSIGNED_AT        = 'assigned_at';
    public const CREATED_AT         = 'created_at';
    public const UPDATED_AT         = 'updated_at';
}
```

#### Migrations

| # | Migration File | Description |
|---|---------------|-------------|
| 1 | `2026_06_10_000010_add_deleted_at_to_members_table.php` | Add `deleted_at` soft delete column to members |
| 2 | `2026_06_10_000011_create_member_plan_assignments_table.php` | Pivot: member ↔ plan history |

```sql
-- members table: add deleted_at
ALTER TABLE members ADD COLUMN deleted_at TIMESTAMP NULL AFTER updated_at;

-- member_plan_assignments
CREATE TABLE member_plan_assignments (
    id                  CHAR(26) PRIMARY KEY,
    member_id           CHAR(26) NOT NULL,
    membership_plan_id  CHAR(26) NOT NULL,
    assigned_at         DATE NOT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (membership_plan_id) REFERENCES membership_plans(id),
    INDEX idx_member_id (member_id),
    INDEX idx_assigned_at (member_id, assigned_at)
);
```

#### Eloquent Models

| Model | File Path |
|-------|-----------|
| MemberModel | `src/Core/Member/Infrastructure/Persistence/MemberModel.php` |
| MembershipPlanModel | `src/Core/Member/Infrastructure/Persistence/MembershipPlanModel.php` |
| MemberPlanAssignmentModel | `src/Core/Member/Infrastructure/Persistence/MemberPlanAssignmentModel.php` |

```php
// MemberModel
final class MemberModel extends Model {
    use SoftDeletes;
    protected $table      = MemberTable::TABLE_NAME;
    protected $primaryKey = MemberTable::ID;
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $fillable   = [
        MemberTable::ID, MemberTable::USER_ID, MemberTable::MEMBER_NUMBER,
        MemberTable::FIRST_NAME, MemberTable::LAST_NAME, MemberTable::PHONE,
        MemberTable::DATE_OF_BIRTH, MemberTable::PROFILE_PHOTO, MemberTable::JOIN_DATE,
        MemberTable::EMERGENCY_CONTACT_NAME, MemberTable::EMERGENCY_CONTACT_PHONE,
        MemberTable::NOTES,
    ];
    protected function casts(): array {
        return [MemberTable::DATE_OF_BIRTH => 'date', MemberTable::DELETED_AT => 'datetime'];
    }
}

// MembershipPlanModel — read-only
final class MembershipPlanModel extends Model {
    protected $table      = MembershipPlanTable::TABLE_NAME;
    protected $primaryKey = MembershipPlanTable::ID;
    public $incrementing  = false;
    protected $keyType    = 'string';
    public $timestamps    = true;
}

// MemberPlanAssignmentModel
final class MemberPlanAssignmentModel extends Model {
    protected $table      = MemberPlanAssignmentTable::TABLE_NAME;
    protected $primaryKey = MemberPlanAssignmentTable::ID;
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $fillable   = [
        MemberPlanAssignmentTable::ID, MemberPlanAssignmentTable::MEMBER_ID,
        MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID, MemberPlanAssignmentTable::ASSIGNED_AT,
    ];
}
```

#### Hydrators

| Hydrator | File Path |
|----------|-----------|
| MemberHydrator | `src/Core/Member/Infrastructure/Hydrators/MemberHydrator.php` |
| MembershipPlanHydrator | `src/Core/Member/Infrastructure/Hydrators/MembershipPlanHydrator.php` |

```php
// MemberHydrator
final class MemberHydrator {
    public function hydrate(MemberModel $model): Member {
        return new Member(
            id:                   MemberId::fromString($model->{MemberTable::ID}),
            userId:               UserId::fromString($model->{MemberTable::USER_ID}),
            memberNumber:         (int) $model->{MemberTable::MEMBER_NUMBER},
            firstName:            $model->{MemberTable::FIRST_NAME},
            lastName:             $model->{MemberTable::LAST_NAME},
            phone:                $model->{MemberTable::PHONE},
            dateOfBirth:          $model->{MemberTable::DATE_OF_BIRTH}
                                      ? new \DateTimeImmutable($model->{MemberTable::DATE_OF_BIRTH})
                                      : null,
            profilePhoto:         $model->{MemberTable::PROFILE_PHOTO},
            joinDate:             new \DateTimeImmutable($model->{MemberTable::JOIN_DATE}),
            emergencyContactName: $model->{MemberTable::EMERGENCY_CONTACT_NAME},
            emergencyContactPhone:$model->{MemberTable::EMERGENCY_CONTACT_PHONE},
            notes:                $model->{MemberTable::NOTES},
            createdAt:            new \DateTimeImmutable((string) $model->{MemberTable::CREATED_AT}),
        );
    }

    public function dehydrate(Member $member): array {
        return [
            MemberTable::ID           => $member->id->value(),
            MemberTable::USER_ID      => $member->userId->value(),
            MemberTable::MEMBER_NUMBER=> $member->memberNumber,
            MemberTable::FIRST_NAME   => $member->firstName,
            MemberTable::LAST_NAME    => $member->lastName,
            MemberTable::PHONE        => $member->phone,
            MemberTable::DATE_OF_BIRTH=> $member->dateOfBirth?->format('Y-m-d'),
            MemberTable::PROFILE_PHOTO=> $member->profilePhoto,
            MemberTable::JOIN_DATE    => $member->joinDate->format('Y-m-d'),
            MemberTable::EMERGENCY_CONTACT_NAME  => $member->emergencyContactName,
            MemberTable::EMERGENCY_CONTACT_PHONE => $member->emergencyContactPhone,
            MemberTable::NOTES        => $member->notes,
        ];
    }
}
```

#### Repository Implementation

| Interface | Implementation | Tables Used |
|-----------|----------------|-------------|
| MemberRepositoryInterface | `src/Core/Member/Infrastructure/Repositories/MemberRepository.php` | members, member_plan_assignments, membership_plans, users |
| MembershipPlanRepositoryInterface | `src/Core/Member/Infrastructure/Repositories/MembershipPlanRepository.php` | membership_plans |

**Key implementation notes for MemberRepository:**

- `findAll()` uses a single LEFT JOIN query to get plan name from member_plan_assignments + membership_plans (subquery for latest assignment per member — no N+1)
- `getDetailById()` uses JOIN to load current plan in one query
- `nextMemberNumber()` uses `MAX(member_number) + 1` with a DB lock to prevent race conditions
- `save()` uses `updateOrCreate` pattern (same as UserRepository)

```php
// findAll — single query, no N+1
// Uses a correlated subquery to get the most recent plan assignment per member
SELECT m.*, u.email, u.status,
       mp.id as plan_id, mp.name as plan_name
FROM members m
JOIN users u ON u.id = m.user_id
LEFT JOIN member_plan_assignments mpa
    ON mpa.member_id = m.id
    AND mpa.assigned_at = (
        SELECT MAX(assigned_at) FROM member_plan_assignments
        WHERE member_id = m.id
    )
LEFT JOIN membership_plans mp ON mp.id = mpa.membership_plan_id
WHERE m.deleted_at IS NULL
  AND (? IS NULL OR u.status = ?)
  AND (? IS NULL OR mpa.membership_plan_id = ?)
ORDER BY m.member_number ASC
LIMIT ? OFFSET ?
```

---

### 3. Application Layer — `backend/src/Core/Member/Application/`

#### Commands

| Command | Handler | File Path | Returns |
|---------|---------|-----------|---------|
| CreateMemberCommand | CreateMemberHandler | `Application/Commands/CreateMember/` | void |
| UpdateMemberCommand | UpdateMemberHandler | `Application/Commands/UpdateMember/` | void |
| AssignMembershipPlanCommand | AssignMembershipPlanHandler | `Application/Commands/AssignMembershipPlan/` | void |
| ActivateMemberCommand | ActivateMemberHandler | `Application/Commands/ActivateMember/` | void |
| DeactivateMemberCommand | DeactivateMemberHandler | `Application/Commands/DeactivateMember/` | void |

**CreateMemberCommand detail:**

```php
// Command
final class CreateMemberCommand {
    public function __construct(
        public readonly MemberId    $memberId,
        public readonly UserId      $userId,
        public readonly string      $email,            // For User creation
        public readonly string      $firstName,
        public readonly string      $lastName,
        public readonly \DateTimeImmutable $joinDate,
        public readonly MembershipPlanId $planId,
        public readonly ?string     $phone = null,
        public readonly ?\DateTimeImmutable $dateOfBirth = null,
    ) {}
}

// Handler (returns void)
// 1. Check email uniqueness via UserRepositoryInterface::findByEmail
// 2. Verify plan exists and is active via MembershipPlanRepositoryInterface::getById
// 3. Determine nextMemberNumber via MemberRepository::nextMemberNumber()
// 4. DB::transaction():
//    a. Create User: UserModel::create([id, email, password=bcrypt(random), role=member, status=pending_approval, must_change_password=true])
//    b. Create Member: MemberRepository::save(Member::create(...))
//    c. Create MemberPlanAssignment: MemberPlanAssignmentModel::create([...])
```

**ActivateMemberCommand detail:**

```php
// Handler
// 1. getDetailById → resolve User
// 2. UserRepository::getById(member.userId)
// 3. user->approve() if pending_approval, or user->activate() if inactive
//    — both methods exist on User entity. approve() for pending_approval; activate() for inactive
// 4. UserRepository::save(user)
```

**DeactivateMemberCommand detail:**

```php
// Handler
// 1. getDetailById → resolve User
// 2. UserRepository::getById(member.userId)
// 3. user->deactivate()
// 4. UserRepository::save(user)
// 5. UserModel::query()->where('id', userId)->first()->tokens()->delete()
//    (token revocation — infrastructure concern, acceptable in handler)
```

**AssignMembershipPlanCommand detail:**

```php
// Handler
// 1. MemberRepository::getById(memberId) — verify member exists
// 2. MembershipPlanRepositoryInterface::getById(planId) — verify plan is active
// 3. MemberPlanAssignmentModel::create([id=ULID, member_id, membership_plan_id, assigned_at=today])
```

#### Queries

| Query | Handler | File Path | Returns |
|-------|---------|-----------|---------|
| GetMemberByIdQuery | GetMemberByIdHandler | `Application/Queries/GetMemberById/` | MemberDetailRM |
| ListMembersQuery | ListMembersHandler | `Application/Queries/ListMembers/` | array{items: MemberListItemRM[], total: int} |
| GetMemberProfileQuery | GetMemberProfileHandler | `Application/Queries/GetMemberProfile/` | MemberDetailRM |

```php
// ListMembersQuery
final class ListMembersQuery {
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $planId = null,
        public readonly int     $page = 1,
        public readonly int     $perPage = 20,
    ) {}
}

// GetMemberProfileQuery — for authenticated member
final class GetMemberProfileQuery {
    public function __construct(public readonly UserId $userId) {}
}
```

---

### 4. HTTP Layer — `backend/app/Http/Actions/Members/`

#### Actions, Requests, DTOs, Resources

**Folder structure:**

```
app/Http/Actions/Members/
├── Create/
│   ├── CreateMemberAction.php
│   ├── CreateMemberRequest.php
│   └── CreateMemberDto.php
├── Update/
│   ├── UpdateMemberAction.php
│   ├── UpdateMemberRequest.php
│   └── UpdateMemberDto.php
├── AssignPlan/
│   ├── AssignMemberPlanAction.php
│   ├── AssignMemberPlanRequest.php
│   └── AssignMemberPlanDto.php
├── Activate/
│   └── ActivateMemberAction.php
├── Deactivate/
│   └── DeactivateMemberAction.php
├── List/
│   ├── ListMembersAction.php
│   ├── ListMembersRequest.php
│   └── ListMembersDto.php
├── Detail/
│   └── GetMemberDetailAction.php
└── Shared/
    ├── MemberResource.php          # Full member detail response
    └── MemberListItemResource.php  # Lightweight list item response
```

**Action Pattern (follows LoginAction precedent):**

```php
// CreateMemberAction
final class CreateMemberAction {
    public function __construct(
        private readonly CreateMemberHandler $handler,
        private readonly GetMemberByIdHandler $query,
    ) {}

    public function __invoke(CreateMemberRequest $request): JsonResponse {
        $dto = $request->getDto();
        $memberId = MemberId::random();
        $userId   = UserId::random();

        try {
            $this->handler->handle(new CreateMemberCommand(
                memberId: $memberId, userId: $userId,
                email: $dto->email, firstName: $dto->firstName,
                lastName: $dto->lastName, joinDate: $dto->joinDate,
                planId: MembershipPlanId::fromString($dto->planId),
                phone: $dto->phone, dateOfBirth: $dto->dateOfBirth,
            ));
        } catch (MemberEmailAlreadyExistsException) {
            return response()->json(['error' => 'El email ya esta registrado', 'code' => 'MEMBER_EMAIL_ALREADY_EXISTS'], 409);
        } catch (MembershipPlanNotFoundException) {
            return response()->json(['error' => 'Plan de membresia no encontrado', 'code' => 'MEMBERSHIP_PLAN_NOT_FOUND'], 422);
        }

        $rm = $this->query->handle(new GetMemberByIdQuery($memberId));
        return (new MemberResource($rm))->toResponse(201);
    }
}
```

```php
// ActivateMemberAction — no request body needed
final class ActivateMemberAction {
    public function __construct(
        private readonly ActivateMemberHandler $handler,
        private readonly GetMemberByIdHandler $query,
    ) {}

    public function __invoke(string $id): JsonResponse {
        try {
            $this->handler->handle(new ActivateMemberCommand(MemberId::fromString($id)));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        } catch (InvalidStatusTransitionException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'INVALID_STATUS_TRANSITION'], 422);
        }

        $rm = $this->query->handle(new GetMemberByIdQuery(MemberId::fromString($id)));
        return (new MemberResource($rm))->toResponse();
    }
}
```

**Request DTOs (no framework validation — only getDto()):**

```php
// CreateMemberRequest
public function getDto(): CreateMemberDto {
    return new CreateMemberDto(
        email:       (string) $this->input('email', ''),
        firstName:   (string) $this->input('first_name', ''),
        lastName:    (string) $this->input('last_name', ''),
        planId:      (string) $this->input('membership_plan_id', ''),
        joinDate:    new \DateTimeImmutable((string) $this->input('join_date', 'today')),
        phone:       $this->input('phone') ? (string) $this->input('phone') : null,
        dateOfBirth: $this->input('date_of_birth')
                         ? new \DateTimeImmutable((string) $this->input('date_of_birth'))
                         : null,
    );
}

// ListMembersRequest
public function getDto(): ListMembersDto {
    return new ListMembersDto(
        status:  $this->input('status'),
        planId:  $this->input('plan_id'),
        page:    (int) $this->input('page', 1),
        perPage: (int) $this->input('per_page', 20),
    );
}
```

**Resources:**

```php
// MemberResource
final class MemberResource {
    public function __construct(private readonly MemberDetailRM $rm) {}
    public function toResponse(int $status = 200): JsonResponse {
        return response()->json([
            'id'                      => $this->rm->id,
            'user_id'                 => $this->rm->userId,
            'member_number'           => $this->rm->memberNumber,
            'first_name'              => $this->rm->firstName,
            'last_name'               => $this->rm->lastName,
            'email'                   => $this->rm->email,
            'phone'                   => $this->rm->phone,
            'date_of_birth'           => $this->rm->dateOfBirth,
            'profile_photo'           => $this->rm->profilePhoto,
            'join_date'               => $this->rm->joinDate,
            'status'                  => $this->rm->status,
            'emergency_contact_name'  => $this->rm->emergencyContactName,
            'emergency_contact_phone' => $this->rm->emergencyContactPhone,
            'notes'                   => $this->rm->notes,
            'created_at'              => $this->rm->createdAt,
            'plan'                    => $this->rm->planId ? [
                'id'                => $this->rm->planId,
                'name'              => $this->rm->planName,
                'price_cents'       => $this->rm->planPriceCents,
                'classes_per_month' => $this->rm->planClassesPerMonth,
            ] : null,
        ], $status);
    }
}

// MemberListResource (wraps collection)
final class MemberListResource {
    /** @param MemberListItemRM[] $items */
    public function __construct(
        private readonly array $items,
        private readonly int   $total,
        private readonly int   $page,
        private readonly int   $perPage,
    ) {}
    public function toResponse(): JsonResponse {
        return response()->json([
            'data' => array_map(fn($item) => [
                'id'            => $item->id,
                'member_number' => $item->memberNumber,
                'first_name'    => $item->firstName,
                'last_name'     => $item->lastName,
                'email'         => $item->email,
                'status'        => $item->status,
                'join_date'     => $item->joinDate,
                'plan'          => $item->planId ? [
                    'id'   => $item->planId,
                    'name' => $item->planName,
                ] : null,
            ], $this->items),
            'meta' => [
                'total'    => $this->total,
                'page'     => $this->page,
                'per_page' => $this->perPage,
            ],
        ]);
    }
}
```

#### Member Self-Profile Action

```
app/Http/Actions/MemberProfile/
└── GetMemberProfileAction.php
```

```php
// GetMemberProfileAction — for authenticated member
final class GetMemberProfileAction {
    public function __construct(private readonly GetMemberProfileHandler $handler) {}

    public function __invoke(Request $request): JsonResponse {
        $userId = UserId::fromString($request->user()->id);
        try {
            $rm = $this->handler->handle(new GetMemberProfileQuery($userId));
        } catch (MemberNotFoundException) {
            return response()->json(['error' => 'Perfil no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }
        return (new MemberResource($rm))->toResponse();
    }
}
```

#### Endpoints

| Method | Route | Action | Middleware | Description |
|--------|-------|--------|------------|-------------|
| POST | `/api/admin/members` | CreateMemberAction | `auth:sanctum`, `role.admin`, `password.change` | Create member |
| GET | `/api/admin/members` | ListMembersAction | `auth:sanctum`, `role.admin`, `password.change` | List members (paginated + filters) |
| GET | `/api/admin/members/{id}` | GetMemberDetailAction | `auth:sanctum`, `role.admin`, `password.change` | Member detail |
| PUT | `/api/admin/members/{id}` | UpdateMemberAction | `auth:sanctum`, `role.admin`, `password.change` | Update profile |
| PUT | `/api/admin/members/{id}/plan` | AssignMemberPlanAction | `auth:sanctum`, `role.admin`, `password.change` | Assign plan |
| PUT | `/api/admin/members/{id}/activate` | ActivateMemberAction | `auth:sanctum`, `role.admin`, `password.change` | Activate |
| PUT | `/api/admin/members/{id}/deactivate` | DeactivateMemberAction | `auth:sanctum`, `role.admin`, `password.change` | Deactivate |
| GET | `/api/member/profile` | GetMemberProfileAction | `auth:sanctum`, `role.member`, `password.change` | Member views own profile |

---

### 5. Service Provider / Bindings

Add to `AppServiceProvider` (or a dedicated `MemberServiceProvider`):

```php
$this->app->bind(MemberRepositoryInterface::class, MemberRepository::class);
$this->app->bind(MembershipPlanRepositoryInterface::class, MembershipPlanRepository::class);
```

---

### 6. Collateral Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `routes/api.php` | Extension | Add admin/members and member/profile route groups |
| `app/Providers/AppServiceProvider.php` | Extension | Bind new repository interfaces |
| `backend/database/migrations/` | New files | 2 new migrations (deleted_at + plan_assignments) |

**No breaking changes.** All additions are additive.

---

## Database Schema

### `member_plan_assignments` (new)

```sql
CREATE TABLE member_plan_assignments (
    id                  CHAR(26) PRIMARY KEY,
    member_id           CHAR(26) NOT NULL,
    membership_plan_id  CHAR(26) NOT NULL,
    assigned_at         DATE NOT NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (membership_plan_id) REFERENCES membership_plans(id),
    INDEX idx_member_assigned (member_id, assigned_at)
);
```

### `members` table: add `deleted_at`

```sql
ALTER TABLE members ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;
-- Index for soft delete filtering
ALTER TABLE members ADD INDEX idx_deleted_at (deleted_at);
```

---

## State Machine

```
User.status for members:

[Admin creates member]
         │
         ▼
  pending_approval  ──── Admin activate ──►  active
                                               │  ▲
                                 deactivate   │  │ re-activate
                                               ▼  │
                                            inactive
```

Implemented via existing `User.approve()`, `User.activate()`, `User.deactivate()` methods — no new methods needed.

---

## API Contract (Response Shapes)

### `POST /api/admin/members` → 201

```json
{
  "id": "01J3...",
  "user_id": "01J3...",
  "member_number": 1,
  "first_name": "Carlos",
  "last_name": "Ruiz",
  "email": "carlos@example.com",
  "phone": "+34 600 000 000",
  "date_of_birth": "1990-05-15",
  "profile_photo": null,
  "join_date": "2026-06-10",
  "status": "pending_approval",
  "emergency_contact_name": null,
  "emergency_contact_phone": null,
  "notes": null,
  "created_at": "2026-06-10T10:00:00+00:00",
  "plan": {
    "id": "01J2...",
    "name": "4-5 Dias",
    "price_cents": 4000,
    "classes_per_month": 25
  }
}
```

### `GET /api/admin/members` → 200

```json
{
  "data": [
    {
      "id": "01J3...",
      "member_number": 1,
      "first_name": "Carlos",
      "last_name": "Ruiz",
      "email": "carlos@example.com",
      "status": "active",
      "join_date": "2026-06-10",
      "plan": { "id": "01J2...", "name": "4-5 Dias" }
    }
  ],
  "meta": { "total": 1, "page": 1, "per_page": 20 }
}
```

---

## Dependencies

| Dependency | Type | Status |
|------------|------|--------|
| `Shared/Auth/User` entity | Internal BC | Already exists |
| `Shared/Auth/UserRepository` | Internal BC | Already exists |
| `symfony/uid` (Ulid) | PHP package | Already installed |
| `members` table | DB | Already exists |
| `membership_plans` table | DB | Already exists + seeded |

---

## Testing Strategy

| Test Type | Scope | Priority | File Path |
|-----------|-------|----------|-----------|
| Unit | Member entity — create(), update() | High | `tests/Unit/Core/Member/MemberTest.php` |
| Unit | CreateMemberHandler — email uniqueness, plan validation | High | `tests/Unit/Core/Member/CreateMemberHandlerTest.php` |
| Unit | ActivateMemberHandler — state transitions | High | `tests/Unit/Core/Member/ActivateMemberHandlerTest.php` |
| Unit | DeactivateMemberHandler — token revocation | High | `tests/Unit/Core/Member/DeactivateMemberHandlerTest.php` |
| Integration | `POST /api/admin/members` — success, duplicate email, bad plan | High | `tests/Feature/Members/CreateMemberTest.php` |
| Integration | `GET /api/admin/members` — filters by status and plan | High | `tests/Feature/Members/ListMembersTest.php` |
| Integration | `PUT /api/admin/members/{id}/activate` — transitions | High | `tests/Feature/Members/ActivateMemberTest.php` |
| Integration | `PUT /api/admin/members/{id}/deactivate` — token revocation | High | `tests/Feature/Members/DeactivateMemberTest.php` |
| Integration | `GET /api/member/profile` — own only, 403 if wrong role | High | `tests/Feature/Members/MemberProfileTest.php` |

---

## Implementation Order

1. [ ] Domain: `MemberId`, `MembershipPlanId` Value Objects
2. [ ] Domain: `MemberStatus` Enum
3. [ ] Domain: `Member` Entity with `create()` and `update()`
4. [ ] Domain: `MembershipPlan` Entity (read-only)
5. [ ] Domain: `MemberListItemRM`, `MemberDetailRM` ReadModels
6. [ ] Domain: `MemberRepositoryInterface`, `MembershipPlanRepositoryInterface`
7. [ ] Domain: `MemberNotFoundException`, `MemberEmailAlreadyExistsException`, `MembershipPlanNotFoundException`
8. [ ] Infrastructure: Migration — `add_deleted_at_to_members_table`
9. [ ] Infrastructure: Migration — `create_member_plan_assignments_table`
10. [ ] Infrastructure: `MemberPlanAssignmentTable` constants class
11. [ ] Infrastructure: `MemberModel`, `MembershipPlanModel`, `MemberPlanAssignmentModel`
12. [ ] Infrastructure: `MemberHydrator`, `MembershipPlanHydrator`
13. [ ] Infrastructure: `MemberRepository` implementation
14. [ ] Infrastructure: `MembershipPlanRepository` implementation
15. [ ] Application: `CreateMemberCommand` + `CreateMemberHandler`
16. [ ] Application: `UpdateMemberCommand` + `UpdateMemberHandler`
17. [ ] Application: `AssignMembershipPlanCommand` + `AssignMembershipPlanHandler`
18. [ ] Application: `ActivateMemberCommand` + `ActivateMemberHandler`
19. [ ] Application: `DeactivateMemberCommand` + `DeactivateMemberHandler`
20. [ ] Application: `GetMemberByIdQuery` + `GetMemberByIdHandler`
21. [ ] Application: `ListMembersQuery` + `ListMembersHandler`
22. [ ] Application: `GetMemberProfileQuery` + `GetMemberProfileHandler`
23. [ ] HTTP: DTOs (`CreateMemberDto`, `UpdateMemberDto`, `ListMembersDto`, `AssignMemberPlanDto`)
24. [ ] HTTP: Requests (`CreateMemberRequest`, `UpdateMemberRequest`, `ListMembersRequest`, `AssignMemberPlanRequest`)
25. [ ] HTTP: Resources (`MemberResource`, `MemberListResource`)
26. [ ] HTTP: Actions (all 8)
27. [ ] HTTP: Routes in `api.php`
28. [ ] Bindings in `AppServiceProvider`
29. [ ] Tests: Unit suite
30. [ ] Tests: Feature suite

---

## Open Technical Questions

None — all decisions resolved.

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Race condition on member_number assignment | Low | Low | Use DB lock (`lockForUpdate()`) in nextMemberNumber() query |
| Token revocation slows deactivation (many tokens) | Low | Low | Bulk delete is one query — acceptable |
| Correlated subquery for current plan is slow at scale | Low | Low | ~100 members max; add index on (member_id, assigned_at) |
| CreateMember transaction fails midway | Low | Medium | All steps in DB::transaction() — atomic rollback |
