<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Infrastructure\Repositories;

use App\Src\Core\ClassSession\Domain\Entities\ClassSession;
use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\ReadModels\ClassSessionRM;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use App\Src\Core\ClassSession\Infrastructure\Hydrators\ClassSessionHydrator;
use App\Src\Core\ClassSession\Infrastructure\Persistence\ClassSessionModel;
use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Support\Facades\DB;

final class ClassSessionRepository implements ClassSessionRepositoryInterface
{
    public function __construct(private readonly ClassSessionHydrator $hydrator) {}

    public function getById(ClassSessionId $id): ClassSession
    {
        $model = ClassSessionModel::query()
            ->where(ClassSessionTable::ID, $id->value())
            ->whereNull(ClassSessionTable::DELETED_AT)
            ->first();

        if ($model === null) {
            throw new ClassSessionNotFoundException($id->value());
        }

        return $this->hydrator->hydrate($model);
    }

    public function findById(ClassSessionId $id): ?ClassSession
    {
        $model = ClassSessionModel::query()
            ->where(ClassSessionTable::ID, $id->value())
            ->whereNull(ClassSessionTable::DELETED_AT)
            ->first();

        return $model ? $this->hydrator->hydrate($model) : null;
    }

    public function findAll(?DayOfWeek $day, ?UserId $coachId, ?ClassSessionStatus $status): array
    {
        $query = ClassSessionModel::query()
            ->whereNull(ClassSessionTable::DELETED_AT);

        if ($day !== null) {
            $query->where(ClassSessionTable::DAY_OF_WEEK, $day->value);
        }

        if ($coachId !== null) {
            $query->where(ClassSessionTable::COACH_ID, $coachId->value());
        }

        if ($status !== null) {
            $query->where(ClassSessionTable::STATUS, $status->value);
        }

        return $query->get()
            ->map(fn($model) => $this->hydrator->hydrate($model))
            ->all();
    }

    public function findByCoach(UserId $coachId): array
    {
        return ClassSessionModel::query()
            ->where(ClassSessionTable::COACH_ID, $coachId->value())
            ->whereNull(ClassSessionTable::DELETED_AT)
            ->get()
            ->map(fn($model) => $this->hydrator->hydrate($model))
            ->all();
    }

    public function findWeeklySchedule(): array
    {
        $cs = ClassSessionTable::TABLE_NAME;
        $ct = ClassTypeTable::TABLE_NAME;
        $u  = UserTable::TABLE_NAME;

        // Single JOIN query — no N+1
        $rows = DB::table("{$cs}")
            ->select([
                "{$cs}." . ClassSessionTable::ID,
                "{$cs}." . ClassSessionTable::CLASS_TYPE_ID,
                "{$ct}." . ClassTypeTable::NAME   . ' as class_type_name',
                "{$ct}." . ClassTypeTable::SLUG   . ' as class_type_slug',
                "{$ct}." . ClassTypeTable::COLOR  . ' as class_type_color',
                "{$cs}." . ClassSessionTable::COACH_ID,
                "{$u}." . UserTable::EMAIL        . ' as coach_email',
                "{$cs}." . ClassSessionTable::DAY_OF_WEEK,
                "{$cs}." . ClassSessionTable::TIME_SLOT,
                "{$cs}." . ClassSessionTable::MAX_CAPACITY,
                "{$cs}." . ClassSessionTable::STATUS,
                "{$cs}." . ClassSessionTable::CREATED_AT,
            ])
            ->join("{$ct}", "{$ct}." . ClassTypeTable::ID, '=', "{$cs}." . ClassSessionTable::CLASS_TYPE_ID)
            ->leftJoin("{$u}", "{$u}." . UserTable::ID, '=', "{$cs}." . ClassSessionTable::COACH_ID)
            ->whereNull("{$cs}." . ClassSessionTable::DELETED_AT)
            ->orderByRaw("CASE {$cs}." . ClassSessionTable::DAY_OF_WEEK . " WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 ELSE 6 END")
            ->orderBy("{$cs}." . ClassSessionTable::TIME_SLOT)
            ->get();

        return $rows->map(fn($row) => $this->rowToRM($row))->all();
    }

    public function getByIdRM(ClassSessionId $id): ClassSessionRM
    {
        $cs = ClassSessionTable::TABLE_NAME;
        $ct = ClassTypeTable::TABLE_NAME;
        $u  = UserTable::TABLE_NAME;

        $row = DB::table("{$cs}")
            ->select([
                "{$cs}." . ClassSessionTable::ID,
                "{$cs}." . ClassSessionTable::CLASS_TYPE_ID,
                "{$ct}." . ClassTypeTable::NAME   . ' as class_type_name',
                "{$ct}." . ClassTypeTable::SLUG   . ' as class_type_slug',
                "{$ct}." . ClassTypeTable::COLOR  . ' as class_type_color',
                "{$cs}." . ClassSessionTable::COACH_ID,
                "{$u}." . UserTable::EMAIL        . ' as coach_email',
                "{$cs}." . ClassSessionTable::DAY_OF_WEEK,
                "{$cs}." . ClassSessionTable::TIME_SLOT,
                "{$cs}." . ClassSessionTable::MAX_CAPACITY,
                "{$cs}." . ClassSessionTable::STATUS,
                "{$cs}." . ClassSessionTable::CREATED_AT,
            ])
            ->join("{$ct}", "{$ct}." . ClassTypeTable::ID, '=', "{$cs}." . ClassSessionTable::CLASS_TYPE_ID)
            ->leftJoin("{$u}", "{$u}." . UserTable::ID, '=', "{$cs}." . ClassSessionTable::COACH_ID)
            ->where("{$cs}." . ClassSessionTable::ID, $id->value())
            ->whereNull("{$cs}." . ClassSessionTable::DELETED_AT)
            ->first();

        if ($row === null) {
            throw new ClassSessionNotFoundException($id->value());
        }

        return $this->rowToRM($row);
    }

    public function findAllRM(?DayOfWeek $day, ?UserId $coachId, ?ClassSessionStatus $status): array
    {
        $cs = ClassSessionTable::TABLE_NAME;
        $ct = ClassTypeTable::TABLE_NAME;
        $u  = UserTable::TABLE_NAME;

        $query = DB::table("{$cs}")
            ->select([
                "{$cs}." . ClassSessionTable::ID,
                "{$cs}." . ClassSessionTable::CLASS_TYPE_ID,
                "{$ct}." . ClassTypeTable::NAME   . ' as class_type_name',
                "{$ct}." . ClassTypeTable::SLUG   . ' as class_type_slug',
                "{$ct}." . ClassTypeTable::COLOR  . ' as class_type_color',
                "{$cs}." . ClassSessionTable::COACH_ID,
                "{$u}." . UserTable::EMAIL        . ' as coach_email',
                "{$cs}." . ClassSessionTable::DAY_OF_WEEK,
                "{$cs}." . ClassSessionTable::TIME_SLOT,
                "{$cs}." . ClassSessionTable::MAX_CAPACITY,
                "{$cs}." . ClassSessionTable::STATUS,
                "{$cs}." . ClassSessionTable::CREATED_AT,
            ])
            ->join("{$ct}", "{$ct}." . ClassTypeTable::ID, '=', "{$cs}." . ClassSessionTable::CLASS_TYPE_ID)
            ->leftJoin("{$u}", "{$u}." . UserTable::ID, '=', "{$cs}." . ClassSessionTable::COACH_ID)
            ->whereNull("{$cs}." . ClassSessionTable::DELETED_AT);

        if ($day !== null) {
            $query->where("{$cs}." . ClassSessionTable::DAY_OF_WEEK, $day->value);
        }

        if ($coachId !== null) {
            $query->where("{$cs}." . ClassSessionTable::COACH_ID, $coachId->value());
        }

        if ($status !== null) {
            $query->where("{$cs}." . ClassSessionTable::STATUS, $status->value);
        }

        $query->orderByRaw("CASE {$cs}." . ClassSessionTable::DAY_OF_WEEK . " WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 ELSE 6 END")
              ->orderBy("{$cs}." . ClassSessionTable::TIME_SLOT);

        return $query->get()->map(fn($row) => $this->rowToRM($row))->all();
    }

    public function findByCoachRM(UserId $coachId): array
    {
        $cs = ClassSessionTable::TABLE_NAME;
        $ct = ClassTypeTable::TABLE_NAME;
        $u  = UserTable::TABLE_NAME;

        $rows = DB::table("{$cs}")
            ->select([
                "{$cs}." . ClassSessionTable::ID,
                "{$cs}." . ClassSessionTable::CLASS_TYPE_ID,
                "{$ct}." . ClassTypeTable::NAME   . ' as class_type_name',
                "{$ct}." . ClassTypeTable::SLUG   . ' as class_type_slug',
                "{$ct}." . ClassTypeTable::COLOR  . ' as class_type_color',
                "{$cs}." . ClassSessionTable::COACH_ID,
                "{$u}." . UserTable::EMAIL        . ' as coach_email',
                "{$cs}." . ClassSessionTable::DAY_OF_WEEK,
                "{$cs}." . ClassSessionTable::TIME_SLOT,
                "{$cs}." . ClassSessionTable::MAX_CAPACITY,
                "{$cs}." . ClassSessionTable::STATUS,
                "{$cs}." . ClassSessionTable::CREATED_AT,
            ])
            ->join("{$ct}", "{$ct}." . ClassTypeTable::ID, '=', "{$cs}." . ClassSessionTable::CLASS_TYPE_ID)
            ->leftJoin("{$u}", "{$u}." . UserTable::ID, '=', "{$cs}." . ClassSessionTable::COACH_ID)
            ->where("{$cs}." . ClassSessionTable::COACH_ID, $coachId->value())
            ->whereNull("{$cs}." . ClassSessionTable::DELETED_AT)
            ->orderByRaw("CASE {$cs}." . ClassSessionTable::DAY_OF_WEEK . " WHEN 'monday' THEN 1 WHEN 'tuesday' THEN 2 WHEN 'wednesday' THEN 3 WHEN 'thursday' THEN 4 WHEN 'friday' THEN 5 ELSE 6 END")
            ->orderBy("{$cs}." . ClassSessionTable::TIME_SLOT)
            ->get();

        return $rows->map(fn($row) => $this->rowToRM($row))->all();
    }

    public function hasCoachConflict(UserId $coachId, DayOfWeek $day, TimeSlot $slot, ?ClassSessionId $excludeId): bool
    {
        $query = ClassSessionModel::query()
            ->where(ClassSessionTable::COACH_ID, $coachId->value())
            ->where(ClassSessionTable::DAY_OF_WEEK, $day->value)
            ->where(ClassSessionTable::TIME_SLOT, $slot->value)
            ->whereNull(ClassSessionTable::DELETED_AT);

        if ($excludeId !== null) {
            $query->where(ClassSessionTable::ID, '!=', $excludeId->value());
        }

        return $query->exists();
    }

    public function save(ClassSession $session): void
    {
        ClassSessionModel::query()->updateOrCreate(
            [ClassSessionTable::ID => $session->id->value()],
            $this->hydrator->dehydrate($session),
        );
    }

    public function softDelete(ClassSessionId $id): void
    {
        ClassSessionModel::query()
            ->where(ClassSessionTable::ID, $id->value())
            ->update([ClassSessionTable::DELETED_AT => now()]);
    }

    private function rowToRM(object $row): ClassSessionRM
    {
        return new ClassSessionRM(
            id:             ClassSessionId::fromString($row->{ClassSessionTable::ID}),
            classTypeId:    $row->{ClassSessionTable::CLASS_TYPE_ID},
            classTypeName:  $row->class_type_name,
            classTypeSlug:  $row->class_type_slug,
            classTypeColor: $row->class_type_color ?? null,
            coachId:        $row->{ClassSessionTable::COACH_ID} ?? null,
            coachEmail:     $row->coach_email ?? null,
            dayOfWeek:      DayOfWeek::from($row->{ClassSessionTable::DAY_OF_WEEK}),
            timeSlot:       $row->{ClassSessionTable::TIME_SLOT},
            maxCapacity:    (int) $row->{ClassSessionTable::MAX_CAPACITY},
            status:         ClassSessionStatus::from($row->{ClassSessionTable::STATUS}),
            createdAt:      $row->{ClassSessionTable::CREATED_AT}
                                ? new \DateTimeImmutable((string) $row->{ClassSessionTable::CREATED_AT})
                                : new \DateTimeImmutable(),
        );
    }
}
