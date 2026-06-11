# Solution Design: Class Schedule Management (epic-classes)

**Requirement:** `docs/working_docs/epics/epic-classes/requirements.md`
**Validation:** `docs/working_docs/epics/epic-classes/validation.md`
**Date:** 2026-06-11
**Bounded Context:** `Core/ClassSession`

---

## Summary

Implement the `Core/ClassSession` bounded context following the existing DDD + Hexagonal + CQRS patterns established by `Shared/Auth`. The design introduces one new entity (`ClassSession`) with three enums, two value objects, one repository interface/implementation pair, four commands, four queries, nine HTTP actions, and a seeder for the default 42-session weekly schedule.

Soft delete (`deleted_at`) is used instead of hard delete, per the validation recommendation — cost is minimal and prevents a breaking schema change when `epic-booking` lands.

---

## Architecture Decision

**Why `Core/ClassSession` (not `Core/Class`):** `class` is a reserved keyword in PHP. The bounded context folder is `ClassSession` to avoid namespace conflicts.

**Why soft delete from the start:** The `epic-booking` feature will reference `class_session_id` as a FK. Hard-deleting a session with bookings would orphan records. Adding soft delete now costs one `deleted_at` column and a trait; it avoids a migration and code change when bookings exist.

**Why no Command/Query bus:** The existing codebase injects handlers directly (no bus infrastructure). Following the established pattern: Actions inject handlers directly, AppServiceProvider binds interfaces to implementations.

**Why `coach_id` is nullable:** Sessions are seeded without coach assignments. Admin assigns coaches post-deploy. The coach conflict check skips `null` coach IDs (a session with `coach_id = null` never conflicts).

**Unique constraint on `(day_of_week, time_slot, class_type_id)`:** Friday allows two sessions per slot (GAP + Entrenamiento Libre). The constraint is on the triple, not on `(day_of_week, time_slot)` alone.

---

## Existing Code Analysis

| Component | Location | Reusable | Notes |
|-----------|----------|----------|-------|
| `UserId` VO | `Shared/Auth/Domain/ValueObjects/UserId.php` | Yes | Pattern for `ClassSessionId` |
| `UserRole` enum | `Shared/Auth/Domain/Enums/UserRole.php` | Yes — reference | `coach` role already exists |
| `UserRepositoryInterface` | `Shared/Auth/Domain/Repositories/` | Pattern only | Same interface shape |
| `UserRepository` | `Shared/Auth/Infrastructure/Repositories/` | Pattern only | Same repo structure |
| `UserHydrator` | `Shared/Auth/Infrastructure/Hydrators/` | Pattern only | Same hydrator shape |
| `UserModel` | `Shared/Auth/Infrastructure/Persistence/` | Pattern only | ULID PK, soft deletes |
| `UserTable` | `Shared/Auth/Infrastructure/Tables/` | Pattern only | Constants class shape |
| `LoginAction` | `app/Http/Actions/Auth/Login/` | Pattern only | Action → handler → resource |
| `LoginRequest` | `app/Http/Actions/Auth/Login/` | Pattern only | `getDto()` only, no `rules()` |
| `AppServiceProvider` | `app/Providers/AppServiceProvider.php` | **Modify** | Add ClassSession bindings |
| `routes/api.php` | `routes/api.php` | **Modify** | Add ClassSession routes |
| `ClassTypeTable` | `Core/ClassType/Infrastructure/Tables/` | Reference | Table constants pattern |
| `RequireAdminRole` middleware | `app/Http/Middleware/` | Yes — use | Existing admin guard |
| `RequireCoachRole` middleware | `app/Http/Middleware/` | Yes — use | Existing coach guard |

---

## Implementation Plan

### 1. Domain Layer

**Namespace root:** `App\Src\Core\ClassSession\Domain`

#### Enums

| Enum | File Path | Values |
|------|-----------|--------|
| `DayOfWeek` | `src/Core/ClassSession/Domain/Enums/DayOfWeek.php` | `monday`, `tuesday`, `wednesday`, `thursday`, `friday` |
| `ClassSessionStatus` | `src/Core/ClassSession/Domain/Enums/ClassSessionStatus.php` | `active`, `cancelled` |

**DayOfWeek detail:**
```php
enum DayOfWeek: string {
    case Monday    = 'monday';
    case Tuesday   = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday  = 'thursday';
    case Friday    = 'friday';

    // Sort order for display (Mon=1 … Fri=5)
    public function sortOrder(): int { ... }
}
```

**ClassSessionStatus detail:**
```php
enum ClassSessionStatus: string {
    case Active    = 'active';
    case Cancelled = 'cancelled';
}
```

#### Value Objects

| VO | File Path | Description |
|----|-----------|-------------|
| `ClassSessionId` | `src/Core/ClassSession/Domain/ValueObjects/ClassSessionId.php` | Extends `Ulid` — same pattern as `UserId` |
| `TimeSlot` | `src/Core/ClassSession/Domain/ValueObjects/TimeSlot.php` | Validated string — one of 7 fixed slots |
| `ClassTypeId` | `src/Core/ClassSession/Domain/ValueObjects/ClassTypeId.php` | Extends `Ulid` — reference to ClassType |

**ClassSessionId detail:**
```php
final class ClassSessionId extends Ulid {
    public static function random(): static { return new static(); }
    public static function fromString(string $value): static { return new static($value); }
    public function value(): string { return $this->toBase32(); }
}
```

**TimeSlot detail:**
```php
final readonly class TimeSlot {
    private const VALID_SLOTS = ['07:45', '12:15', '16:15', '17:30', '18:45', '20:00', '21:15'];

    public function __construct(public readonly string $value) {
        if (!in_array($value, self::VALID_SLOTS, true)) {
            throw new InvalidTimeSlotException($value);
        }
    }

    public static function validValues(): array { return self::VALID_SLOTS; }
}
```

**ClassTypeId detail:**
```php
final class ClassTypeId extends Ulid {
    public static function random(): static { return new static(); }
    public static function fromString(string $value): static { return new static($value); }
    public function value(): string { return $this->toBase32(); }
}
```

#### Entity

| Entity | File Path | Description |
|--------|-----------|-------------|
| `ClassSession` | `src/Core/ClassSession/Domain/Entities/ClassSession.php` | Main aggregate — weekly recurring slot |

**ClassSession detail:**
```php
final class ClassSession {
    private ClassSessionStatus $status;
    private ?\DateTimeImmutable $deletedAt;

    public function __construct(
        public readonly ClassSessionId $id,
        public readonly ClassTypeId $classTypeId,
        public readonly ?UserId $coachId,       // nullable: assigned post-deploy
        public readonly DayOfWeek $dayOfWeek,
        public readonly TimeSlot $timeSlot,
        public readonly int $maxCapacity,       // >= 1, validated in create()
        ClassSessionStatus $status,
        public readonly \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $deletedAt = null,
    ) { ... }

    public static function create(
        ClassSessionId $id,
        ClassTypeId $classTypeId,
        ?UserId $coachId,
        DayOfWeek $dayOfWeek,
        TimeSlot $timeSlot,
        int $maxCapacity,
    ): self { ... }   // throws InvalidCapacityException if maxCapacity < 1

    public function update(
        ClassTypeId $classTypeId,
        ?UserId $coachId,
        int $maxCapacity,
    ): void { ... }   // throws InvalidCapacityException

    public function cancel(): void { ... }   // throws SessionAlreadyCancelledException
    public function restore(): void { ... }  // throws SessionNotCancelledException
    public function softDelete(): void { ... }

    public function status(): ClassSessionStatus { ... }
    public function deletedAt(): ?\DateTimeImmutable { ... }
    public function isDeleted(): bool { ... }
}
```

> **Note:** `dayOfWeek` and `timeSlot` are `readonly` — immutable after creation. `update()` only accepts `classTypeId`, `coachId`, and `maxCapacity`.

#### Domain Exceptions

| Exception | File Path | HTTP Code |
|-----------|-----------|-----------|
| `ClassSessionNotFoundException` | `Domain/Exceptions/ClassSessionNotFoundException.php` | 404 |
| `InvalidCapacityException` | `Domain/Exceptions/InvalidCapacityException.php` | 422 |
| `InvalidTimeSlotException` | `Domain/Exceptions/InvalidTimeSlotException.php` | 422 |
| `SessionAlreadyCancelledException` | `Domain/Exceptions/SessionAlreadyCancelledException.php` | 422 |
| `SessionNotCancelledException` | `Domain/Exceptions/SessionNotCancelledException.php` | 422 |
| `CoachConflictException` | `Domain/Exceptions/CoachConflictException.php` | 409 |
| `ClassTypeNotFoundException` | `Domain/Exceptions/ClassTypeNotFoundException.php` | 422 |
| `CoachNotFoundException` | `Domain/Exceptions/CoachNotFoundException.php` | 422 |
| `SessionHasBookingsException` | `Domain/Exceptions/SessionHasBookingsException.php` | 409 (no-op guard) |
| `WeekendSessionNotAllowedException` | `Domain/Exceptions/WeekendSessionNotAllowedException.php` | 422 |

> `WeekendSessionNotAllowedException` is not needed if `DayOfWeek` enum only contains weekdays — creation with an invalid day_of_week will fail at `DayOfWeek::from()` before reaching the entity. Keep as a named exception thrown from the handler for a clear API error code.

#### Repository Interface

| Interface | File Path |
|-----------|-----------|
| `ClassSessionRepositoryInterface` | `src/Core/ClassSession/Domain/Repositories/ClassSessionRepositoryInterface.php` |

```php
interface ClassSessionRepositoryInterface {
    /** @throws ClassSessionNotFoundException */
    public function getById(ClassSessionId $id): ClassSession;

    public function findById(ClassSessionId $id): ?ClassSession;

    /** @return ClassSession[] */
    public function findAll(?DayOfWeek $day, ?UserId $coachId, ?ClassSessionStatus $status): array;

    /** @return ClassSession[] */
    public function findByCoach(UserId $coachId): array;

    /** @return ClassSession[] — all active+cancelled, grouped internally by day */
    public function findWeeklySchedule(): array;

    /** Returns true if another session (excluding $excludeId) has the same coach at same day+time */
    public function hasCoachConflict(UserId $coachId, DayOfWeek $day, TimeSlot $slot, ?ClassSessionId $excludeId): bool;

    public function save(ClassSession $session): void;

    public function softDelete(ClassSessionId $id): void;
}
```

#### Read Models

| ReadModel | File Path | Used By |
|-----------|-----------|---------|
| `ClassSessionRM` | `src/Core/ClassSession/Domain/ReadModels/ClassSessionRM.php` | All query handlers |

```php
final readonly class ClassSessionRM {
    public function __construct(
        public ClassSessionId $id,
        public string $classTypeId,
        public string $classTypeName,
        public string $classTypeSlug,
        public ?string $classTypeColor,
        public ?string $coachId,
        public ?string $coachEmail,
        public DayOfWeek $dayOfWeek,
        public string $timeSlot,
        public int $maxCapacity,
        public ClassSessionStatus $status,
        public \DateTimeImmutable $createdAt,
    ) {}
}
```

> `ClassSessionRM` is a denormalized read model that joins class_type name/slug/color and coach email at query time (single JOIN query, no N+1).

---

### 2. Application Layer

**Namespace root:** `App\Src\Core\ClassSession\Application`

#### Commands

| Command | Handler | File Path | Description |
|---------|---------|-----------|-------------|
| `CreateClassSessionCommand` | `CreateClassSessionHandler` | `Application/Commands/CreateClassSession/` | Creates session with status=active |
| `UpdateClassSessionCommand` | `UpdateClassSessionHandler` | `Application/Commands/UpdateClassSession/` | Updates classTypeId, coachId, maxCapacity |
| `CancelClassSessionCommand` | `CancelClassSessionHandler` | `Application/Commands/CancelClassSession/` | Transitions active → cancelled |
| `RestoreClassSessionCommand` | `RestoreClassSessionHandler` | `Application/Commands/RestoreClassSession/` | Transitions cancelled → active |
| `DeleteClassSessionCommand` | `DeleteClassSessionHandler` | `Application/Commands/DeleteClassSession/` | Soft-deletes session (guard: no bookings) |

**CreateClassSessionCommand fields:** `id: ClassSessionId`, `classTypeId: ClassTypeId`, `coachId: ?UserId`, `dayOfWeek: DayOfWeek`, `timeSlot: TimeSlot`, `maxCapacity: int`

**CreateClassSessionHandler responsibilities:**
1. Validate `DayOfWeek` is a weekday (already guaranteed by enum — no Saturday/Sunday values)
2. Verify `classTypeId` exists and is active (via `ClassTypeRepositoryInterface` or direct model check — see note below)
3. If `coachId` is not null: verify user exists with `role = coach` (via `UserRepositoryInterface`)
4. If `coachId` is not null: check `repository->hasCoachConflict(coachId, day, slot, null)` → throw `CoachConflictException`
5. Call `ClassSession::create(...)` and `repository->save($session)`

> **Cross-BC reads:** `classTypeId` existence check uses a lightweight check against `ClassTypeRepositoryInterface` (read-only, defined in `Core/ClassType/Domain/Repositories/`). If that interface doesn't exist yet, the handler can query `ClassTypeModel` directly for this validation (infrastructure layer access is acceptable in a handler only as a pragmatic exception for cross-BC existence checks — document explicitly). Coach validation uses `UserRepositoryInterface` already in `Shared/Auth`.

**UpdateClassSessionCommand fields:** `id: ClassSessionId`, `classTypeId: ClassTypeId`, `coachId: ?UserId`, `maxCapacity: int`

**CancelClassSessionCommand / RestoreClassSessionCommand / DeleteClassSessionCommand fields:** `id: ClassSessionId` only

#### Queries

| Query | Handler | File Path | Returns | Description |
|-------|---------|-----------|---------|-------------|
| `GetClassSessionByIdQuery` | `GetClassSessionByIdHandler` | `Application/Queries/GetClassSessionById/` | `ClassSessionRM` | Single session detail (admin) |
| `ListClassSessionsQuery` | `ListClassSessionsHandler` | `Application/Queries/ListClassSessions/` | `ClassSessionRM[]` | Filterable list (admin) |
| `GetWeeklyScheduleQuery` | `GetWeeklyScheduleHandler` | `Application/Queries/GetWeeklySchedule/` | `ClassSessionRM[]` | Public schedule — all sessions |
| `GetCoachSessionsQuery` | `GetCoachSessionsHandler` | `Application/Queries/GetCoachSessions/` | `ClassSessionRM[]` | Coach's own sessions |

**ListClassSessionsQuery fields:** `dayOfWeek: ?DayOfWeek`, `coachId: ?UserId`, `status: ?ClassSessionStatus`

**GetCoachSessionsQuery fields:** `coachId: UserId`

**Handler pattern:** Each query handler calls the appropriate repository method and returns `ClassSessionRM[]` or `ClassSessionRM`. The repository implementation does the JOIN to denormalize class_type and user fields — handlers never query in loops.

---

### 3. Infrastructure Layer

**Namespace root:** `App\Src\Core\ClassSession\Infrastructure`

#### Table Constants

| Class | File Path |
|-------|-----------|
| `ClassSessionTable` | `src/Core/ClassSession/Infrastructure/Tables/ClassSessionTable.php` |

```php
final class ClassSessionTable {
    public const TABLE_NAME    = 'class_sessions';
    public const ID            = 'id';
    public const CLASS_TYPE_ID = 'class_type_id';
    public const COACH_ID      = 'coach_id';
    public const DAY_OF_WEEK   = 'day_of_week';
    public const TIME_SLOT     = 'time_slot';
    public const MAX_CAPACITY  = 'max_capacity';
    public const STATUS        = 'status';
    public const CREATED_AT    = 'created_at';
    public const UPDATED_AT    = 'updated_at';
    public const DELETED_AT    = 'deleted_at';
}
```

#### Eloquent Model

| Class | File Path |
|-------|-----------|
| `ClassSessionModel` | `src/Core/ClassSession/Infrastructure/Persistence/ClassSessionModel.php` |

```php
final class ClassSessionModel extends Model {
    use SoftDeletes;
    protected $table        = ClassSessionTable::TABLE_NAME;
    protected $primaryKey   = ClassSessionTable::ID;
    public    $incrementing = false;
    protected $keyType      = 'string';
    // $fillable lists all columns except timestamps
    // casts: deleted_at => datetime
}
```

#### Hydrator

| Class | File Path |
|-------|-----------|
| `ClassSessionHydrator` | `src/Core/ClassSession/Infrastructure/Hydrators/ClassSessionHydrator.php` |

```php
final class ClassSessionHydrator {
    public function hydrate(ClassSessionModel $model): ClassSession { ... }
    public function dehydrate(ClassSession $session): array { ... }
    // hydrate: maps all columns back to VOs and enums
    // dehydrate: returns array for updateOrCreate
}
```

`hydrate` maps:
- `id` → `ClassSessionId::fromString()`
- `class_type_id` → `ClassTypeId::fromString()`
- `coach_id` → `UserId::fromString()` or `null`
- `day_of_week` → `DayOfWeek::from()`
- `time_slot` → `new TimeSlot()`
- `max_capacity` → `int`
- `status` → `ClassSessionStatus::from()`
- `created_at` → `\DateTimeImmutable`
- `deleted_at` → `\DateTimeImmutable` or `null`

#### Repository

| Interface | Implementation | File Path |
|-----------|----------------|-----------|
| `ClassSessionRepositoryInterface` | `ClassSessionRepository` | `src/Core/ClassSession/Infrastructure/Repositories/ClassSessionRepository.php` |

**`findAll()` implementation:** Single query with optional `WHERE` clauses on `day_of_week`, `coach_id`, `status`. Returns hydrated `ClassSession[]`.

**`findWeeklySchedule()` implementation:** Queries all non-deleted sessions, JOINs `class_types` (id, name, slug, color) and `users` (id, email), returns `ClassSessionRM[]` ordered by `day_of_week` sort order then `time_slot`. No N+1 — single JOIN query.

**`hasCoachConflict()` implementation:**
```php
public function hasCoachConflict(UserId $coachId, DayOfWeek $day, TimeSlot $slot, ?ClassSessionId $excludeId): bool {
    return ClassSessionModel::query()
        ->where(ClassSessionTable::COACH_ID, $coachId->value())
        ->where(ClassSessionTable::DAY_OF_WEEK, $day->value)
        ->where(ClassSessionTable::TIME_SLOT, $slot->value)
        ->when($excludeId, fn($q) => $q->where(ClassSessionTable::ID, '!=', $excludeId->value()))
        ->whereNull(ClassSessionTable::DELETED_AT)
        ->exists();
}
```

**`save()` implementation:** Uses `ClassSessionModel::query()->updateOrCreate([ID => ...], $hydrator->dehydrate($session))`.

**`softDelete()` implementation:** Sets `deleted_at` via `ClassSessionModel::query()->where(ID, ...)->update([DELETED_AT => now()])`.

**`findAll()` / `findByCoach()` / `GetById` — denormalized RM:** For query handlers that need `ClassSessionRM` (list, schedule, coach view), the repository builds a JOIN query:
```sql
SELECT cs.*, ct.name, ct.slug, ct.color, u.email
FROM class_sessions cs
JOIN class_types ct ON ct.id = cs.class_type_id
LEFT JOIN users u ON u.id = cs.coach_id
WHERE cs.deleted_at IS NULL
  [AND cs.day_of_week = ?]
  [AND cs.coach_id = ?]
  [AND cs.status = ?]
ORDER BY FIELD(cs.day_of_week, 'monday','tuesday','wednesday','thursday','friday'), cs.time_slot
```
Returns `ClassSessionRM[]`. No queries in loops.

#### Migration

| File | Description |
|------|-------------|
| `2026_06_11_000005_create_class_sessions_table.php` | Creates `class_sessions` table with FK to `class_types` and nullable FK to `users` |

```sql
CREATE TABLE class_sessions (
    id           CHAR(26)     NOT NULL,
    class_type_id CHAR(26)    NOT NULL,
    coach_id     CHAR(26)     NULL,
    day_of_week  ENUM('monday','tuesday','wednesday','thursday','friday') NOT NULL,
    time_slot    VARCHAR(5)   NOT NULL,
    max_capacity UNSIGNED INT NOT NULL DEFAULT 20,
    status       ENUM('active','cancelled') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP    NULL,
    updated_at   TIMESTAMP    NULL,
    deleted_at   TIMESTAMP    NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_class_sessions_slot (day_of_week, time_slot, class_type_id),
    FOREIGN KEY (class_type_id) REFERENCES class_types(id),
    FOREIGN KEY (coach_id) REFERENCES users(id) ON DELETE SET NULL
);
```

> `UNIQUE KEY` on `(day_of_week, time_slot, class_type_id)` — allows Friday dual sessions (GAP + Entrenamiento Libre) while preventing duplicate same-type slots.

---

### 4. HTTP Layer

**Namespace root:** `App\Http\Actions\ClassSession`

#### Actions, Requests, Resources, DTOs

| Method | Route | Action | Request | DTO | Resource | Auth |
|--------|-------|--------|---------|-----|----------|------|
| GET | `/api/schedule` | `GetWeeklyScheduleAction` | — | — | `WeeklyScheduleResource` | None |
| GET | `/api/class-sessions` | `ListClassSessionsAction` | `ListClassSessionsRequest` | `ListClassSessionsDto` | `ClassSessionListResource` | Admin |
| POST | `/api/class-sessions` | `CreateClassSessionAction` | `CreateClassSessionRequest` | `CreateClassSessionDto` | `ClassSessionResource` | Admin |
| GET | `/api/class-sessions/{id}` | `GetClassSessionAction` | — | — | `ClassSessionResource` | Admin |
| PUT | `/api/class-sessions/{id}` | `UpdateClassSessionAction` | `UpdateClassSessionRequest` | `UpdateClassSessionDto` | `ClassSessionResource` | Admin |
| DELETE | `/api/class-sessions/{id}` | `DeleteClassSessionAction` | — | — | 204 no body | Admin |
| PATCH | `/api/class-sessions/{id}/cancel` | `CancelClassSessionAction` | — | — | `ClassSessionResource` | Admin |
| PATCH | `/api/class-sessions/{id}/restore` | `RestoreClassSessionAction` | — | — | `ClassSessionResource` | Admin |
| GET | `/api/coach/sessions` | `GetCoachSessionsAction` | — | — | `ClassSessionListResource` | Coach |

**Action structure** (following thin-action pattern, ≤ 20 lines each):

```php
// CreateClassSessionAction example
final class CreateClassSessionAction {
    public function __construct(
        private readonly CreateClassSessionHandler $handler,
        private readonly GetClassSessionByIdHandler $query,
    ) {}

    public function __invoke(CreateClassSessionRequest $request): JsonResponse {
        $dto = $request->getDto();
        $id  = ClassSessionId::random();  // ID generated before command

        try {
            $this->handler->handle(new CreateClassSessionCommand(
                id:          $id,
                classTypeId: ClassTypeId::fromString($dto->classTypeId),
                coachId:     $dto->coachId ? UserId::fromString($dto->coachId) : null,
                dayOfWeek:   DayOfWeek::from($dto->dayOfWeek),
                timeSlot:    new TimeSlot($dto->timeSlot),
                maxCapacity: $dto->maxCapacity,
            ));
        } catch (ClassTypeNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'CLASS_TYPE_NOT_FOUND'], 422);
        } catch (CoachNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'COACH_NOT_FOUND'], 422);
        } catch (CoachConflictException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'COACH_CONFLICT'], 409);
        } catch (InvalidCapacityException|InvalidTimeSlotException|\ValueError $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'INVALID_INPUT'], 422);
        }

        $rm = $this->query->handle(new GetClassSessionByIdQuery($id));
        return (new ClassSessionResource($rm))->toResponse(201);
    }
}
```

> `\ValueError` catches `DayOfWeek::from()` failure for invalid enum values.

**Request pattern** (no `rules()`, only `getDto()`):
```php
final class CreateClassSessionRequest extends FormRequest {
    public function authorize(): bool { return true; }

    public function getDto(): CreateClassSessionDto {
        return new CreateClassSessionDto(
            classTypeId: (string) $this->input('class_type_id', ''),
            coachId:     $this->input('coach_id') ? (string) $this->input('coach_id') : null,
            dayOfWeek:   (string) $this->input('day_of_week', ''),
            timeSlot:    (string) $this->input('time_slot', ''),
            maxCapacity: (int)    $this->input('max_capacity', 0),
        );
    }
}
```

**Resource shape:**
```php
// ClassSessionResource::toResponse()
[
    'id'          => $rm->id->value(),
    'class_type'  => ['id' => $rm->classTypeId, 'name' => $rm->classTypeName, 'slug' => $rm->classTypeSlug, 'color' => $rm->classTypeColor],
    'coach'       => $rm->coachId ? ['id' => $rm->coachId, 'email' => $rm->coachEmail] : null,
    'day_of_week' => $rm->dayOfWeek->value,
    'time_slot'   => $rm->timeSlot,
    'max_capacity'=> $rm->maxCapacity,
    'status'      => $rm->status->value,
    'created_at'  => $rm->createdAt->format(\DateTimeInterface::ATOM),
]
```

**WeeklyScheduleResource** groups sessions by day:
```php
// Grouped by day_of_week, ordered by time_slot within each day
[
    'monday'    => [ [...session...], [...session...] ],
    'tuesday'   => [ ... ],
    'wednesday' => [ ... ],
    'thursday'  => [ ... ],
    'friday'    => [ [...gap session...], [...entrenamiento-libre session...] ],
]
```

#### Routes

```php
// routes/api.php additions

// Public — no auth
Route::get('/schedule', GetWeeklyScheduleAction::class);

// Admin — auth:sanctum + admin role + force_password_change guard
Route::middleware(['auth:sanctum', 'role.admin', 'force.password.change'])->group(function () {
    Route::prefix('class-sessions')->group(function () {
        Route::get('/',         ListClassSessionsAction::class);
        Route::post('/',        CreateClassSessionAction::class);
        Route::get('/{id}',     GetClassSessionAction::class);
        Route::put('/{id}',     UpdateClassSessionAction::class);
        Route::delete('/{id}',  DeleteClassSessionAction::class);
        Route::patch('/{id}/cancel',  CancelClassSessionAction::class);
        Route::patch('/{id}/restore', RestoreClassSessionAction::class);
    });
});

// Coach — auth:sanctum + coach role
Route::middleware(['auth:sanctum', 'role.coach'])->group(function () {
    Route::get('/coach/sessions', GetCoachSessionsAction::class);
});
```

> Check actual middleware alias names from `bootstrap/app.php` or `Kernel.php`. Existing middleware files are `RequireAdminRole` and `RequireCoachRole` — check their registered aliases.

---

### 5. Seeder

| Class | File Path |
|-------|-----------|
| `ClassSessionSeeder` | `database/seeders/ClassSessionSeeder.php` |

**Logic:**
- Fetch class type IDs by slug from `class_types` table (single query, map to array keyed by slug)
- Build 42 session records:
  - Mon/Wed: tren-superior × 7 slots = 14
  - Tue: tren-inferior × 7 slots = 7
  - Thu: full-body × 7 slots = 7
  - Fri: gap × 7 slots + entrenamiento-libre × 7 slots = 14
- All sessions: `coach_id = null`, `max_capacity = 20`, `status = active`
- IDs generated via `ClassSessionId::random()`
- Insert with `ClassSessionModel::query()->insertOrIgnore()` (idempotent — skip if already exists)

---

### 6. AppServiceProvider Additions

```php
// Register in AppServiceProvider::register()
$this->app->bind(ClassSessionRepositoryInterface::class, ClassSessionRepository::class);

$this->app->bind(ClassSessionRepository::class, fn() =>
    new ClassSessionRepository(new ClassSessionHydrator())
);

// One bind per handler (CreateClassSessionHandler, UpdateClassSessionHandler, etc.)
```

---

## Collateral Changes

### Files to Modify

| File | Change | Description |
|------|--------|-------------|
| `app/Providers/AppServiceProvider.php` | Add bindings | ClassSessionRepository + all handlers |
| `routes/api.php` | Add routes | 9 new endpoints |
| `database/seeders/DatabaseSeeder.php` | Add call | `$this->call(ClassSessionSeeder::class)` after ClassTypeSeeder |

### Breaking Changes

None. This is a greenfield bounded context. No existing code is modified except AppServiceProvider and routes.

---

## Database Schema (final)

```sql
CREATE TABLE class_sessions (
    id            CHAR(26)     NOT NULL,
    class_type_id CHAR(26)     NOT NULL,
    coach_id      CHAR(26)     NULL DEFAULT NULL,
    day_of_week   ENUM('monday','tuesday','wednesday','thursday','friday') NOT NULL,
    time_slot     VARCHAR(5)   NOT NULL,
    max_capacity  INT UNSIGNED NOT NULL DEFAULT 20,
    status        ENUM('active','cancelled') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP    NULL DEFAULT NULL,
    updated_at    TIMESTAMP    NULL DEFAULT NULL,
    deleted_at    TIMESTAMP    NULL DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_day_slot_type (day_of_week, time_slot, class_type_id),
    KEY idx_coach_id (coach_id),
    KEY idx_status (status),
    KEY idx_deleted_at (deleted_at),

    CONSTRAINT fk_cs_class_type FOREIGN KEY (class_type_id)
        REFERENCES class_types (id),
    CONSTRAINT fk_cs_coach FOREIGN KEY (coach_id)
        REFERENCES users (id) ON DELETE SET NULL
);
```

---

## State Machine

```
(new) ──create()──► active ──cancel()──► cancelled ──restore()──► active
                        │                    │
                   softDelete()          softDelete()
                        │                    │
                     deleted               deleted
```

> Both `active` and `cancelled` sessions can be soft-deleted. A deleted session is filtered from all queries by `whereNull(deleted_at)`.

---

## Dependencies

| Dependency | Type | Description |
|------------|------|-------------|
| `class_types` table | Hard — must exist | `class_type_id` FK; ClassTypeSeeder must run first |
| `users` table | Hard — must exist | `coach_id` FK; users migration runs first |
| `Shared/Auth/Domain/ValueObjects/UserId` | Import | Used for `coachId` parameter |
| `Shared/Auth/Domain/Repositories/UserRepositoryInterface` | Import | Coach existence + role check in handler |
| `Symfony\Component\Uid\Ulid` | Import | Base class for `ClassSessionId`, `ClassTypeId` |
| `epic-booking` (future) | Future FK | Will add `class_session_id` FK to bookings; soft delete protects integrity |

---

## Testing Strategy

| Test Type | Scope | File Path | Priority |
|-----------|-------|-----------|----------|
| Unit | `ClassSession` entity (state transitions, `create()`, `cancel()`, `restore()`, `update()`) | `tests/Unit/Core/ClassSession/Domain/ClassSessionTest.php` | High |
| Unit | `TimeSlot` VO (valid slots, rejection of invalid) | `tests/Unit/Core/ClassSession/Domain/TimeSlotTest.php` | High |
| Unit | `DayOfWeek` enum (sortOrder) | `tests/Unit/Core/ClassSession/Domain/DayOfWeekTest.php` | Medium |
| Feature | `POST /api/class-sessions` | `tests/Feature/ClassSession/CreateClassSessionTest.php` | High |
| Feature | `PUT /api/class-sessions/{id}` | `tests/Feature/ClassSession/UpdateClassSessionTest.php` | High |
| Feature | `PATCH cancel / restore` | `tests/Feature/ClassSession/CancelRestoreClassSessionTest.php` | High |
| Feature | `DELETE /api/class-sessions/{id}` | `tests/Feature/ClassSession/DeleteClassSessionTest.php` | High |
| Feature | `GET /api/class-sessions` (with filters) | `tests/Feature/ClassSession/ListClassSessionsTest.php` | High |
| Feature | `GET /api/class-sessions/{id}` | `tests/Feature/ClassSession/GetClassSessionTest.php` | Medium |
| Feature | `GET /api/schedule` (public, no auth) | `tests/Feature/ClassSession/GetWeeklyScheduleTest.php` | High |
| Feature | `GET /api/coach/sessions` | `tests/Feature/ClassSession/GetCoachSessionsTest.php` | High |

---

## Implementation Order

1. [ ] Domain: `DayOfWeek` enum
2. [ ] Domain: `ClassSessionStatus` enum
3. [ ] Domain: `ClassSessionId` VO
4. [ ] Domain: `ClassTypeId` VO
5. [ ] Domain: `TimeSlot` VO (with `InvalidTimeSlotException`)
6. [ ] Domain: All remaining domain exceptions
7. [ ] Domain: `ClassSession` entity
8. [ ] Domain: `ClassSessionRM` read model
9. [ ] Domain: `ClassSessionRepositoryInterface`
10. [ ] Infrastructure: `ClassSessionTable` constants
11. [ ] Infrastructure: `ClassSessionModel`
12. [ ] Infrastructure: `ClassSessionHydrator`
13. [ ] Infrastructure: `ClassSessionRepository`
14. [ ] Database: migration `create_class_sessions_table`
15. [ ] Application: `CreateClassSessionCommand` + `CreateClassSessionHandler`
16. [ ] Application: `UpdateClassSessionCommand` + `UpdateClassSessionHandler`
17. [ ] Application: `CancelClassSessionCommand` + `CancelClassSessionHandler`
18. [ ] Application: `RestoreClassSessionCommand` + `RestoreClassSessionHandler`
19. [ ] Application: `DeleteClassSessionCommand` + `DeleteClassSessionHandler`
20. [ ] Application: `GetClassSessionByIdQuery` + `GetClassSessionByIdHandler`
21. [ ] Application: `ListClassSessionsQuery` + `ListClassSessionsHandler`
22. [ ] Application: `GetWeeklyScheduleQuery` + `GetWeeklyScheduleHandler`
23. [ ] Application: `GetCoachSessionsQuery` + `GetCoachSessionsHandler`
24. [ ] HTTP: DTOs for Create and Update
25. [ ] HTTP: Requests (`CreateClassSessionRequest`, `UpdateClassSessionRequest`, `ListClassSessionsRequest`)
26. [ ] HTTP: Resources (`ClassSessionResource`, `ClassSessionListResource`, `WeeklyScheduleResource`)
27. [ ] HTTP: All 9 Actions
28. [ ] Routes: add to `routes/api.php`
29. [ ] DI: add bindings to `AppServiceProvider`
30. [ ] Seeder: `ClassSessionSeeder` + wire into `DatabaseSeeder`
31. [ ] Tests: Unit — entity + VOs
32. [ ] Tests: Feature — all 9 endpoints
33. [ ] PHPStan validation

---

## Open Technical Questions

1. **ClassType existence check:** Does `Core/ClassType` have a `ClassTypeRepositoryInterface`? If not, the `CreateClassSessionHandler` and `UpdateClassSessionHandler` can use `ClassTypeModel::query()->where('id', ...)->where('is_active', 1)->exists()` directly as a pragmatic cross-BC read. This should be documented as a controlled exception.

2. **Middleware alias names:** The existing `RequireAdminRole` and `RequireCoachRole` middleware classes exist. What are their registered aliases in `bootstrap/app.php`? Confirm before writing routes.

3. **`ForcePasswordChange` middleware:** Should admin class-session routes be behind `ForcePasswordChange`? The existing auth routes apply it selectively. Recommendation: yes — same policy as other admin endpoints.

---

## Risks

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| ClassType has no repository interface | Medium | Low | Use direct model check in handler; document exception |
| Middleware alias names differ from class names | Low | Low | Check `bootstrap/app.php` before implementing routes |
| Friday dual-session unique constraint breaks seeder | Low | Medium | Seeder uses `insertOrIgnore()` and inserts different `class_type_id` values for same day+slot on Friday |
| `FIELD()` MySQL function for enum ordering not available in tests | Medium | Low | Use `CASE WHEN` fallback or sort in PHP after query |
