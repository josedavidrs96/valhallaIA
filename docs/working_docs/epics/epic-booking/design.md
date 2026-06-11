# Solution Design: Class Booking System (epic-booking)

**Requirement:** `docs/working_docs/epics/epic-booking/requirements.md`
**Validation:** `docs/working_docs/epics/epic-booking/validation.md`
**Date:** 2026-06-11
**Bounded Context:** `Core/Booking`

---

## Summary

Build the `Core/Booking` bounded context from scratch following the DDD + Hexagonal + CQRS patterns already established by `Shared/Auth`, `Core/Member`, and `Core/ClassSession`. One new entity (`Booking`), one repository interface and implementation, three commands (book, cancel, future-cancel-by-admin), four queries, seven HTTP actions, and one migration.

Capacity is enforced via a **live count** of confirmed bookings per session — no denormalized counter column. This avoids race condition complexity and is sufficient at Valhalla's scale (~100 members, 42 sessions).

No Command/Query bus — Actions inject handlers directly, consistent with the rest of the codebase.

---

## Architecture Decision

**`Core/Booking` is its own bounded context.** It references `MemberId` and `ClassSessionId` as foreign keys but never imports Member or ClassSession entities directly. Cross-BC reads use the existing repository interfaces (`MemberRepositoryInterface`, `ClassSessionRepositoryInterface`) injected into handlers.

**Capacity via live count, not a counter column.** `SELECT COUNT(*) FROM bookings WHERE class_session_id = ? AND status = 'confirmed'` is one query. At this gym's size (max ~25 bookings per session) it is fast and avoids the write-contention of incrementing a counter.

**Booking is soft-state, never hard-deleted.** Status transitions from `confirmed` to `cancelled`. This preserves history without needing a deleted_at column.

**Cancel is a PATCH action, not DELETE.** `DELETE /api/bookings/{id}` implies hard delete. Using `PATCH /api/bookings/{id}/cancel` is semantically accurate.

**No booking window or plan-limit enforcement.** Explicitly deferred to post-MVP per validation report.

---

## Existing Code Analysis

| Component | Location | Reusable | Notes |
|-----------|----------|----------|-------|
| `MemberId` VO | `src/Core/Member/Domain/ValueObjects/MemberId.php` | Yes | Used as FK in Booking |
| `MemberRepositoryInterface` | `src/Core/Member/Domain/Repositories/MemberRepositoryInterface.php` | Yes | Injected in BookingHandler to verify member exists |
| `ClassSessionId` VO | `src/Core/ClassSession/Domain/ValueObjects/ClassSessionId.php` | Yes | Used as FK in Booking |
| `ClassSessionRepositoryInterface` | `src/Core/ClassSession/Domain/Repositories/ClassSessionRepositoryInterface.php` | Yes | Injected in BookingHandler to verify session exists and is active |
| `UserId` VO | `src/Shared/Auth/Domain/ValueObjects/UserId.php` | Yes | Resolve authenticated user to member |
| `UserModel` pattern | `src/Shared/Auth/Infrastructure/Persistence/UserModel.php` | Pattern | Same Eloquent pattern for BookingModel |
| `LoginAction` pattern | `app/Http/Actions/Auth/Login/LoginAction.php` | Pattern | Thin action → handler → resource |
| `AppServiceProvider` | `app/Providers/AppServiceProvider.php` | Modify | Add Booking repository binding |
| `routes/api.php` | `routes/api.php` | Modify | Add booking routes |

---

## Implementation Plan

### 1. Domain Layer

**Namespace root:** `App\Src\Core\Booking\Domain`

#### Value Objects

| VO | File Path | Description |
|----|-----------|-------------|
| `BookingId` | `src/Core/Booking/Domain/ValueObjects/BookingId.php` | Extends `Ulid` — same pattern as `MemberId` |

```php
final class BookingId extends Ulid {
    public static function random(): static { return new static(); }
    public static function fromString(string $value): static { return new static($value); }
    public function value(): string { return $this->toBase32(); }
}
```

#### Enums

| Enum | File Path | Values |
|------|-----------|--------|
| `BookingStatus` | `src/Core/Booking/Domain/Enums/BookingStatus.php` | `confirmed`, `cancelled` |

```php
enum BookingStatus: string {
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}
```

#### Entity

| Entity | File Path | Description |
|--------|-----------|-------------|
| `Booking` | `src/Core/Booking/Domain/Entities/Booking.php` | Member reservation for a ClassSession |

```php
final class Booking {
    private BookingStatus $status;

    public function __construct(
        public readonly BookingId       $id,
        public readonly MemberId        $memberId,
        public readonly ClassSessionId  $classSessionId,
        BookingStatus                   $status,
        public readonly \DateTimeImmutable $createdAt,
    ) {
        $this->status = $status;
    }

    public static function create(
        BookingId      $id,
        MemberId       $memberId,
        ClassSessionId $classSessionId,
    ): self {
        return new self(
            id:             $id,
            memberId:       $memberId,
            classSessionId: $classSessionId,
            status:         BookingStatus::Confirmed,
            createdAt:      new \DateTimeImmutable(),
        );
    }

    /** @throws BookingAlreadyCancelledException */
    public function cancel(): void {
        if ($this->status === BookingStatus::Cancelled) {
            throw new BookingAlreadyCancelledException($this->id);
        }
        $this->status = BookingStatus::Cancelled;
    }

    public function status(): BookingStatus {
        return $this->status;
    }
}
```

#### Domain Exceptions

| Exception | File Path | HTTP Code | Trigger |
|-----------|-----------|-----------|---------|
| `BookingNotFoundException` | `Domain/Exceptions/BookingNotFoundException.php` | 404 | Booking ID does not exist |
| `BookingAlreadyExistsException` | `Domain/Exceptions/BookingAlreadyExistsException.php` | 409 | Member already has confirmed booking for session |
| `BookingAlreadyCancelledException` | `Domain/Exceptions/BookingAlreadyCancelledException.php` | 422 | Trying to cancel already-cancelled booking |
| `BookingNotOwnedException` | `Domain/Exceptions/BookingNotOwnedException.php` | 403 | Member tries to cancel another member's booking |
| `SessionFullException` | `Domain/Exceptions/SessionFullException.php` | 422 | Session capacity reached |
| `SessionNotAvailableException` | `Domain/Exceptions/SessionNotAvailableException.php` | 422 | Session is cancelled or deleted |

#### Read Models

| ReadModel | File Path | Used By |
|-----------|-----------|---------|
| `BookingRM` | `src/Core/Booking/Domain/ReadModels/BookingRM.php` | All query handlers |
| `RosterItemRM` | `src/Core/Booking/Domain/ReadModels/RosterItemRM.php` | GetClassRosterHandler |

```php
// BookingRM — member's own booking view (includes session info)
final readonly class BookingRM {
    public function __construct(
        public string $id,
        public string $memberId,
        public string $classSessionId,
        public string $status,
        // Denormalized session info
        public string $dayOfWeek,
        public string $timeSlot,
        public string $classTypeName,
        public string $classTypeSlug,
        public string $createdAt,
    ) {}
}

// RosterItemRM — admin/coach roster view (includes member info)
final readonly class RosterItemRM {
    public function __construct(
        public string  $bookingId,
        public string  $memberId,
        public int     $memberNumber,
        public string  $firstName,
        public string  $lastName,
        public string  $status,
        public string  $bookedAt,
    ) {}
}
```

#### Repository Interface

```php
// src/Core/Booking/Domain/Repositories/BookingRepositoryInterface.php

interface BookingRepositoryInterface {
    /** @throws BookingNotFoundException */
    public function getById(BookingId $id): Booking;

    public function findByMemberAndSession(MemberId $memberId, ClassSessionId $sessionId): ?Booking;

    public function countConfirmedBySession(ClassSessionId $sessionId): int;

    public function save(Booking $booking): void;

    /** @return BookingRM[] */
    public function findByMember(MemberId $memberId): array;

    /** @return RosterItemRM[] */
    public function getRoster(ClassSessionId $sessionId): array;
}
```

---

### 2. Infrastructure Layer

**Namespace root:** `App\Src\Core\Booking\Infrastructure`

#### Table Constants

| Class | File Path |
|-------|-----------|
| `BookingTable` | `src/Core/Booking/Infrastructure/Tables/BookingTable.php` |

```php
final class BookingTable {
    public const TABLE_NAME        = 'bookings';
    public const ID                = 'id';
    public const MEMBER_ID         = 'member_id';
    public const CLASS_SESSION_ID  = 'class_session_id';
    public const STATUS            = 'status';
    public const CREATED_AT        = 'created_at';
    public const UPDATED_AT        = 'updated_at';
}
```

#### Eloquent Model

| Class | File Path |
|-------|-----------|
| `BookingModel` | `src/Core/Booking/Infrastructure/Persistence/BookingModel.php` |

```php
final class BookingModel extends Model {
    protected $table      = BookingTable::TABLE_NAME;
    protected $primaryKey = BookingTable::ID;
    public $incrementing  = false;
    protected $keyType    = 'string';
    protected $fillable   = [
        BookingTable::ID,
        BookingTable::MEMBER_ID,
        BookingTable::CLASS_SESSION_ID,
        BookingTable::STATUS,
    ];
    protected function casts(): array {
        return [
            BookingTable::STATUS => 'string',
        ];
    }
}
```

#### Hydrator

| Class | File Path |
|-------|-----------|
| `BookingHydrator` | `src/Core/Booking/Infrastructure/Hydrators/BookingHydrator.php` |

```php
final class BookingHydrator {
    public function hydrate(BookingModel $model): Booking {
        return new Booking(
            id:             BookingId::fromString($model->{BookingTable::ID}),
            memberId:       MemberId::fromString($model->{BookingTable::MEMBER_ID}),
            classSessionId: ClassSessionId::fromString($model->{BookingTable::CLASS_SESSION_ID}),
            status:         BookingStatus::from($model->{BookingTable::STATUS}),
            createdAt:      new \DateTimeImmutable((string) $model->{BookingTable::CREATED_AT}),
        );
    }

    public function dehydrate(Booking $booking): array {
        return [
            BookingTable::ID               => $booking->id->value(),
            BookingTable::MEMBER_ID        => $booking->memberId->value(),
            BookingTable::CLASS_SESSION_ID => $booking->classSessionId->value(),
            BookingTable::STATUS           => $booking->status()->value,
        ];
    }
}
```

#### Repository Implementation

| Interface | Implementation | File Path |
|-----------|----------------|-----------|
| `BookingRepositoryInterface` | `BookingRepository` | `src/Core/Booking/Infrastructure/Repositories/BookingRepository.php` |

**Key implementation notes:**

- `findByMemberAndSession()`: single query `WHERE member_id = ? AND class_session_id = ? AND status = 'confirmed'` — returns first or null.
- `countConfirmedBySession()`: `SELECT COUNT(*) FROM bookings WHERE class_session_id = ? AND status = 'confirmed'` — one query.
- `getRoster()`: single JOIN query — `bookings` JOIN `members` on `member_id`. Returns `RosterItemRM[]` ordered by `created_at ASC`.
- `findByMember()`: single JOIN query — `bookings` JOIN `class_sessions` cs JOIN `class_types` ct on cs. Returns `BookingRM[]` ordered by `created_at DESC`.
- `save()`: `BookingModel::query()->updateOrCreate([ID => $id], $hydrator->dehydrate($booking))`.

```sql
-- getRoster() query
SELECT b.id, b.status, b.created_at,
       m.id as member_id, m.member_number, m.first_name, m.last_name
FROM bookings b
JOIN members m ON m.id = b.member_id
WHERE b.class_session_id = ?
ORDER BY b.created_at ASC

-- findByMember() query
SELECT b.id, b.member_id, b.class_session_id, b.status, b.created_at,
       cs.day_of_week, cs.time_slot,
       ct.name as class_type_name, ct.slug as class_type_slug
FROM bookings b
JOIN class_sessions cs ON cs.id = b.class_session_id
JOIN class_types ct ON ct.id = cs.class_type_id
WHERE b.member_id = ?
ORDER BY b.created_at DESC
```

#### Migration

| File | Description |
|------|-------------|
| `2026_06_11_000020_create_bookings_table.php` | Creates `bookings` table with FKs to `members` and `class_sessions` |

```sql
CREATE TABLE bookings (
    id               CHAR(26)                        NOT NULL,
    member_id        CHAR(26)                        NOT NULL,
    class_session_id CHAR(26)                        NOT NULL,
    status           ENUM('confirmed', 'cancelled')  NOT NULL DEFAULT 'confirmed',
    created_at       TIMESTAMP                       NULL DEFAULT NULL,
    updated_at       TIMESTAMP                       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_member_session (member_id, class_session_id),
    KEY idx_class_session_status (class_session_id, status),
    KEY idx_member_id (member_id),

    CONSTRAINT fk_bookings_member
        FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_session
        FOREIGN KEY (class_session_id) REFERENCES class_sessions (id) ON DELETE CASCADE
);
```

> `UNIQUE KEY uq_member_session (member_id, class_session_id)` enforces at DB level that a member can have at most one booking record per session. The application enforces via `BookingAlreadyExistsException` before attempting to save; the DB constraint is a safety net.

---

### 3. Application Layer

**Namespace root:** `App\Src\Core\Booking\Application`

#### Commands

| Command | Handler | File Path | Returns |
|---------|---------|-----------|---------|
| `CreateBookingCommand` | `CreateBookingHandler` | `Application/Commands/CreateBooking/` | void |
| `CancelBookingCommand` | `CancelBookingHandler` | `Application/Commands/CancelBooking/` | void |

**CreateBookingCommand fields:** `id: BookingId`, `memberId: MemberId`, `classSessionId: ClassSessionId`

**CreateBookingHandler responsibilities:**
1. `ClassSessionRepositoryInterface::getById(classSessionId)` — throws `ClassSessionNotFoundException` (404) if not found
2. Verify session status is `active` — throw `SessionNotAvailableException` (422) if cancelled
3. `BookingRepositoryInterface::countConfirmedBySession(classSessionId)` — compare with `session->maxCapacity`; throw `SessionFullException` (422) if full
4. `BookingRepositoryInterface::findByMemberAndSession(memberId, classSessionId)` — throw `BookingAlreadyExistsException` (409) if not null
5. `Booking::create(id, memberId, classSessionId)` + `BookingRepositoryInterface::save($booking)`

> Cross-BC read: `ClassSessionRepositoryInterface` is injected from `Core/ClassSession`. `MemberRepositoryInterface` is NOT injected here — the booking handler trusts the HTTP layer to only dispatch the command with a valid `memberId` (the authenticated member's ID is resolved in the Action). This avoids an extra DB query for the common case.

**CancelBookingCommand fields:** `id: BookingId`, `requestingMemberId: MemberId`

**CancelBookingHandler responsibilities:**
1. `BookingRepositoryInterface::getById(id)` — throws `BookingNotFoundException` (404) if not found
2. Verify `booking->memberId == requestingMemberId` — throw `BookingNotOwnedException` (403) if mismatch
3. `$booking->cancel()` — throws `BookingAlreadyCancelledException` (422) if already cancelled
4. `BookingRepositoryInterface::save($booking)`

#### Queries

| Query | Handler | File Path | Returns |
|-------|---------|-----------|---------|
| `GetBookingByIdQuery` | `GetBookingByIdHandler` | `Application/Queries/GetBookingById/` | `BookingRM` |
| `GetMemberBookingsQuery` | `GetMemberBookingsHandler` | `Application/Queries/GetMemberBookings/` | `BookingRM[]` |
| `GetClassRosterQuery` | `GetClassRosterHandler` | `Application/Queries/GetClassRoster/` | `array{items: RosterItemRM[], confirmed_count: int, max_capacity: int}` |
| `GetMemberBookingsByIdQuery` | `GetMemberBookingsByIdHandler` | `Application/Queries/GetMemberBookingsById/` | `BookingRM[]` |

```php
// GetMemberBookingsQuery — for authenticated member's own bookings
final class GetMemberBookingsQuery {
    public function __construct(public readonly MemberId $memberId) {}
}

// GetClassRosterQuery — for admin/coach
final class GetClassRosterQuery {
    public function __construct(
        public readonly ClassSessionId $classSessionId,
    ) {}
}

// GetMemberBookingsByIdQuery — admin gets any member's bookings
final class GetMemberBookingsByIdQuery {
    public function __construct(public readonly MemberId $memberId) {}
}
```

**GetClassRosterHandler responsibilities:**
1. `ClassSessionRepositoryInterface::getById(classSessionId)` — verify session exists (for max_capacity)
2. `BookingRepositoryInterface::getRoster(classSessionId)` — get roster items
3. `BookingRepositoryInterface::countConfirmedBySession(classSessionId)` — for capacity summary
4. Return `['items' => $roster, 'confirmed_count' => $count, 'max_capacity' => $session->maxCapacity]`

---

### 4. HTTP Layer

**Namespace root:** `App\Http\Actions\Booking`

#### Folder Structure

```
app/Http/Actions/Booking/
├── Create/
│   ├── CreateBookingAction.php
│   ├── CreateBookingRequest.php
│   └── CreateBookingDto.php
├── Cancel/
│   └── CancelBookingAction.php
├── MemberBookings/
│   └── GetMemberBookingsAction.php
├── AdminMemberBookings/
│   ├── GetAdminMemberBookingsAction.php
│   └── GetAdminMemberBookingsRequest.php (no body — path param only)
├── Roster/
│   └── GetClassRosterAction.php
└── Shared/
    ├── BookingResource.php
    ├── BookingListResource.php
    └── RosterResource.php
```

#### Actions, Requests, Resources, DTOs

| Method | Route | Action | Auth | Description |
|--------|-------|--------|------|-------------|
| POST | `/api/bookings` | `CreateBookingAction` | `auth:sanctum`, `role.member` | Member books a session |
| PATCH | `/api/bookings/{id}/cancel` | `CancelBookingAction` | `auth:sanctum`, `role.member` | Member cancels own booking |
| GET | `/api/member/bookings` | `GetMemberBookingsAction` | `auth:sanctum`, `role.member` | Member's own booking list |
| GET | `/api/admin/class-sessions/{id}/roster` | `GetClassRosterAction` | `auth:sanctum`, `role.admin` OR `role.coach` | Class roster |
| GET | `/api/admin/members/{id}/bookings` | `GetAdminMemberBookingsAction` | `auth:sanctum`, `role.admin` | Admin views member's bookings |

**CreateBookingAction:**

```php
final class CreateBookingAction {
    public function __construct(
        private readonly CreateBookingHandler    $handler,
        private readonly GetBookingByIdHandler   $query,
    ) {}

    public function __invoke(CreateBookingRequest $request): JsonResponse {
        $dto       = $request->getDto();
        $bookingId = BookingId::random();

        // Resolve authenticated member's MemberId from User
        $userId   = UserId::fromString($request->user()->id);
        // MemberId is resolved via MemberRepositoryInterface — injected in action
        // but for simplicity, the DTO carries the member ID derived from the authenticated user
        // See note below.

        try {
            $this->handler->handle(new CreateBookingCommand(
                id:             $bookingId,
                memberId:       MemberId::fromString($dto->memberId),
                classSessionId: ClassSessionId::fromString($dto->classSessionId),
            ));
        } catch (ClassSessionNotFoundException) {
            return response()->json(['error' => 'Sesion no encontrada', 'code' => 'SESSION_NOT_FOUND'], 404);
        } catch (SessionNotAvailableException) {
            return response()->json(['error' => 'La sesion no esta disponible', 'code' => 'SESSION_NOT_AVAILABLE'], 422);
        } catch (SessionFullException) {
            return response()->json(['error' => 'La sesion esta completa', 'code' => 'SESSION_FULL'], 422);
        } catch (BookingAlreadyExistsException) {
            return response()->json(['error' => 'Ya tienes una reserva para esta sesion', 'code' => 'BOOKING_ALREADY_EXISTS'], 409);
        }

        $rm = $this->query->handle(new GetBookingByIdQuery($bookingId));
        return (new BookingResource($rm))->toResponse(201);
    }
}
```

> **Note on MemberId resolution in CreateBookingAction:** The authenticated user has a `user_id`. The action must resolve the corresponding `MemberId`. Two options: (a) inject `MemberRepositoryInterface` and call `findByUserId($userId)`, or (b) store `member_id` in the Sanctum token payload. Option (a) is simpler and follows existing patterns. The action injects `MemberRepositoryInterface` alongside the handler.

**CancelBookingAction:**

```php
final class CancelBookingAction {
    public function __construct(
        private readonly CancelBookingHandler    $handler,
        private readonly GetBookingByIdHandler   $query,
        private readonly MemberRepositoryInterface $memberRepo,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse {
        $userId = UserId::fromString($request->user()->id);
        $member = $this->memberRepo->findByUserId($userId);
        if ($member === null) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        try {
            $this->handler->handle(new CancelBookingCommand(
                id:                 BookingId::fromString($id),
                requestingMemberId: $member->id,
            ));
        } catch (BookingNotFoundException) {
            return response()->json(['error' => 'Reserva no encontrada', 'code' => 'BOOKING_NOT_FOUND'], 404);
        } catch (BookingNotOwnedException) {
            return response()->json(['error' => 'No tienes permiso para cancelar esta reserva', 'code' => 'BOOKING_NOT_OWNED'], 403);
        } catch (BookingAlreadyCancelledException) {
            return response()->json(['error' => 'La reserva ya esta cancelada', 'code' => 'BOOKING_ALREADY_CANCELLED'], 422);
        }

        $rm = $this->query->handle(new GetBookingByIdQuery(BookingId::fromString($id)));
        return (new BookingResource($rm))->toResponse();
    }
}
```

**CreateBookingRequest:**

```php
final class CreateBookingRequest extends FormRequest {
    public function authorize(): bool { return true; }

    public function getDto(): CreateBookingDto {
        return new CreateBookingDto(
            classSessionId: (string) $this->input('class_session_id', ''),
        );
    }
}

// CreateBookingDto — memberId NOT in request body; resolved from auth token in action
final class CreateBookingDto {
    public function __construct(
        public readonly string $classSessionId,
    ) {}
}
```

**Resources:**

```php
// BookingResource — single booking response
final class BookingResource {
    public function __construct(private readonly BookingRM $rm) {}

    public function toResponse(int $status = 200): JsonResponse {
        return response()->json([
            'id'               => $this->rm->id,
            'member_id'        => $this->rm->memberId,
            'class_session_id' => $this->rm->classSessionId,
            'status'           => $this->rm->status,
            'session'          => [
                'day_of_week'      => $this->rm->dayOfWeek,
                'time_slot'        => $this->rm->timeSlot,
                'class_type_name'  => $this->rm->classTypeName,
                'class_type_slug'  => $this->rm->classTypeSlug,
            ],
            'created_at'       => $this->rm->createdAt,
        ], $status);
    }
}

// RosterResource — roster response with capacity summary
final class RosterResource {
    public function __construct(
        private readonly array $items,         // RosterItemRM[]
        private readonly int   $confirmedCount,
        private readonly int   $maxCapacity,
    ) {}

    public function toResponse(): JsonResponse {
        return response()->json([
            'capacity' => [
                'confirmed' => $this->confirmedCount,
                'available' => max(0, $this->maxCapacity - $this->confirmedCount),
                'max'       => $this->maxCapacity,
            ],
            'roster' => array_map(fn(RosterItemRM $item) => [
                'booking_id'    => $item->bookingId,
                'member_id'     => $item->memberId,
                'member_number' => $item->memberNumber,
                'first_name'    => $item->firstName,
                'last_name'     => $item->lastName,
                'status'        => $item->status,
                'booked_at'     => $item->bookedAt,
            ], $this->items),
        ]);
    }
}
```

#### Routes

```php
// routes/api.php additions

// Member routes — authenticated member
Route::middleware(['auth:sanctum', 'role.member', 'force.password.change'])->group(function () {
    Route::post('/bookings',                 CreateBookingAction::class);
    Route::patch('/bookings/{id}/cancel',    CancelBookingAction::class);
    Route::get('/member/bookings',           GetMemberBookingsAction::class);
});

// Admin routes — authenticated admin
Route::middleware(['auth:sanctum', 'role.admin', 'force.password.change'])->group(function () {
    Route::get('/admin/class-sessions/{id}/roster',    GetClassRosterAction::class);
    Route::get('/admin/members/{id}/bookings',         GetAdminMemberBookingsAction::class);
});

// Coach routes — authenticated coach
Route::middleware(['auth:sanctum', 'role.coach'])->group(function () {
    Route::get('/coach/class-sessions/{id}/roster',    GetClassRosterAction::class);
});
```

> `GetClassRosterAction` is reused for both admin and coach routes — the action itself does not check role (the middleware handles it). The two routes point to the same action class.

---

### 5. Service Provider Bindings

```php
// AppServiceProvider::register()
$this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);

$this->app->bind(BookingRepository::class, fn() =>
    new BookingRepository(new BookingHydrator())
);

// Handler bindings (one per handler)
$this->app->bind(CreateBookingHandler::class, fn($app) =>
    new CreateBookingHandler(
        $app->make(BookingRepositoryInterface::class),
        $app->make(ClassSessionRepositoryInterface::class),
    )
);

$this->app->bind(CancelBookingHandler::class, fn($app) =>
    new CancelBookingHandler(
        $app->make(BookingRepositoryInterface::class),
    )
);

// ... query handlers follow same pattern
```

---

### 6. Collateral Changes

| File | Change Type | Description |
|------|-------------|-------------|
| `app/Providers/AppServiceProvider.php` | Extension | Bind `BookingRepositoryInterface`, all handlers |
| `routes/api.php` | Extension | Add 5 booking routes |
| `database/migrations/` | New file | `2026_06_11_000020_create_bookings_table.php` |

**No breaking changes.** All additions are additive. No existing bounded context code is modified.

---

## Database Schema

```sql
CREATE TABLE bookings (
    id               CHAR(26)                        NOT NULL,
    member_id        CHAR(26)                        NOT NULL,
    class_session_id CHAR(26)                        NOT NULL,
    status           ENUM('confirmed', 'cancelled')  NOT NULL DEFAULT 'confirmed',
    created_at       TIMESTAMP                       NULL DEFAULT NULL,
    updated_at       TIMESTAMP                       NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_member_session (member_id, class_session_id),
    KEY idx_class_session_status (class_session_id, status),
    KEY idx_member_id (member_id),

    CONSTRAINT fk_bookings_member
        FOREIGN KEY (member_id) REFERENCES members (id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_session
        FOREIGN KEY (class_session_id) REFERENCES class_sessions (id) ON DELETE CASCADE
);
```

---

## State Machine

```
[Member books session]
         │
         ▼
     confirmed ──── cancel() ──► cancelled
         │                           │
         └──── (no restore) ─────────┘
                   (create new booking)
```

Cancelled bookings cannot be re-confirmed. Member must create a new booking.

---

## Dependencies

| Dependency | Type | Status |
|------------|------|--------|
| `Core/Member/MemberId` VO | Hard | Already in codebase |
| `Core/Member/MemberRepositoryInterface` | Hard | Already in codebase |
| `Core/ClassSession/ClassSessionId` VO | Hard | Already in codebase |
| `Core/ClassSession/ClassSessionRepositoryInterface` | Hard | Already in codebase |
| `members` table | Hard | Already exists |
| `class_sessions` table | Hard | Already exists (or designed) |
| `symfony/uid` (Ulid) | PHP package | Already installed |

---

## Testing Strategy

| Test Type | Scope | File Path | Priority |
|-----------|-------|-----------|----------|
| Unit | `Booking::create()`, `Booking::cancel()` state transitions | `tests/Unit/Core/Booking/Domain/BookingTest.php` | High |
| Unit | `CreateBookingHandler` — session full, duplicate, cancelled session | `tests/Unit/Core/Booking/CreateBookingHandlerTest.php` | High |
| Unit | `CancelBookingHandler` — not owned, already cancelled | `tests/Unit/Core/Booking/CancelBookingHandlerTest.php` | High |
| Feature | `POST /api/bookings` — success, full, duplicate, cancelled session | `tests/Feature/Booking/CreateBookingTest.php` | High |
| Feature | `PATCH /api/bookings/{id}/cancel` — success, not owned, already cancelled | `tests/Feature/Booking/CancelBookingTest.php` | High |
| Feature | `GET /api/member/bookings` | `tests/Feature/Booking/GetMemberBookingsTest.php` | High |
| Feature | `GET /api/admin/class-sessions/{id}/roster` | `tests/Feature/Booking/GetClassRosterTest.php` | High |
| Feature | `GET /api/admin/members/{id}/bookings` | `tests/Feature/Booking/GetAdminMemberBookingsTest.php` | Medium |

---

## Implementation Order

1. [ ] Domain: `BookingId` Value Object
2. [ ] Domain: `BookingStatus` Enum
3. [ ] Domain: `Booking` Entity with `create()` and `cancel()`
4. [ ] Domain: `BookingRM`, `RosterItemRM` Read Models
5. [ ] Domain: All domain exceptions (6)
6. [ ] Domain: `BookingRepositoryInterface`
7. [ ] Infrastructure: `BookingTable` constants
8. [ ] Infrastructure: `BookingModel`
9. [ ] Infrastructure: `BookingHydrator`
10. [ ] Infrastructure: `BookingRepository` implementation
11. [ ] Database: migration `create_bookings_table`
12. [ ] Application: `CreateBookingCommand` + `CreateBookingHandler`
13. [ ] Application: `CancelBookingCommand` + `CancelBookingHandler`
14. [ ] Application: `GetBookingByIdQuery` + `GetBookingByIdHandler`
15. [ ] Application: `GetMemberBookingsQuery` + `GetMemberBookingsHandler`
16. [ ] Application: `GetClassRosterQuery` + `GetClassRosterHandler`
17. [ ] Application: `GetMemberBookingsByIdQuery` + `GetMemberBookingsByIdHandler`
18. [ ] HTTP: `CreateBookingDto`, `CreateBookingRequest`
19. [ ] HTTP: Resources (`BookingResource`, `BookingListResource`, `RosterResource`)
20. [ ] HTTP: All 5 Actions
21. [ ] Routes: add to `routes/api.php`
22. [ ] DI: add bindings to `AppServiceProvider`
23. [ ] Tests: Unit suite
24. [ ] Tests: Feature suite

---

## Open Technical Questions

1. **MemberId resolution from authenticated user:** The action needs `MemberId` from the logged-in `User`. The cleanest approach is to inject `MemberRepositoryInterface` in the action and call `findByUserId()`. This adds one query per booking request — acceptable at this scale. Alternative: store `member_id` as a custom claim in the Sanctum token — more complex, defer to post-MVP.

2. **Coach roster access:** The design uses a single `GetClassRosterAction` for both admin and coach routes. Confirm that `role.coach` middleware alias exists in `bootstrap/app.php`.

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Race condition: two members book last spot simultaneously | Low | Low | DB unique key on `(member_id, class_session_id)` catches duplicates at DB level; application throws `BookingAlreadyExistsException` before that | 
| `countConfirmedBySession` and `save` not atomic | Low | Low | At ~25 bookings/session, two concurrent requests could both pass the capacity check. Accept for MVP — add DB-level lock or pessimistic locking post-MVP if needed |
| `MemberId` resolution adds an extra query per booking | Low | Low | One extra query; at this scale (~100 concurrent users max) this is acceptable |
| Orphan bookings when session is soft-deleted | Low | Low | ON DELETE CASCADE on `class_session_id` FK handles hard delete; soft-deleted sessions are filtered at query level — existing bookings remain but session is marked deleted |
