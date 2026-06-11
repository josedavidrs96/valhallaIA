<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Repositories;

use App\Src\Core\ClassSession\Domain\Entities\ClassSession;
use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\ReadModels\ClassSessionRM;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

interface ClassSessionRepositoryInterface
{
    /** @throws ClassSessionNotFoundException */
    public function getById(ClassSessionId $id): ClassSession;

    public function findById(ClassSessionId $id): ?ClassSession;

    /**
     * Returns hydrated ClassSession[] (for command handlers).
     *
     * @return ClassSession[]
     */
    public function findAll(?DayOfWeek $day, ?UserId $coachId, ?ClassSessionStatus $status): array;

    /**
     * @return ClassSession[]
     */
    public function findByCoach(UserId $coachId): array;

    /**
     * Returns all non-deleted sessions with class_type and coach data joined.
     * Used by public schedule and list queries.
     *
     * @return ClassSessionRM[]
     */
    public function findWeeklySchedule(): array;

    /**
     * Returns denormalized ClassSessionRM for a single session.
     *
     * @throws ClassSessionNotFoundException
     */
    public function getByIdRM(ClassSessionId $id): ClassSessionRM;

    /**
     * Returns denormalized ClassSessionRM[] for list/coach queries.
     *
     * @return ClassSessionRM[]
     */
    public function findAllRM(?DayOfWeek $day, ?UserId $coachId, ?ClassSessionStatus $status): array;

    /**
     * @return ClassSessionRM[]
     */
    public function findByCoachRM(UserId $coachId): array;

    /** Returns true if another active session (excluding $excludeId) has the same coach at the same day+time. */
    public function hasCoachConflict(UserId $coachId, DayOfWeek $day, TimeSlot $slot, ?ClassSessionId $excludeId): bool;

    public function save(ClassSession $session): void;

    public function softDelete(ClassSessionId $id): void;
}
