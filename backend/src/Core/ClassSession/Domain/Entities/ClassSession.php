<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Domain\Entities;

use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\Exceptions\InvalidCapacityException;
use App\Src\Core\ClassSession\Domain\Exceptions\SessionAlreadyCancelledException;
use App\Src\Core\ClassSession\Domain\Exceptions\SessionNotCancelledException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassTypeId;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class ClassSession
{
    // Mutable: can be changed via update()
    private ClassTypeId $classTypeId;
    private ?UserId $coachId;
    private int $maxCapacity;

    // Mutable state transitions
    private ClassSessionStatus $status;
    private ?\DateTimeImmutable $deletedAt;

    public function __construct(
        public readonly ClassSessionId $id,
        ClassTypeId $classTypeId,
        ?UserId $coachId,
        public readonly DayOfWeek $dayOfWeek,
        public readonly TimeSlot $timeSlot,
        int $maxCapacity,
        ClassSessionStatus $status,
        public readonly \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $deletedAt = null,
    ) {
        $this->classTypeId = $classTypeId;
        $this->coachId     = $coachId;
        $this->maxCapacity = $maxCapacity;
        $this->status      = $status;
        $this->deletedAt   = $deletedAt;
    }

    public static function create(
        ClassSessionId $id,
        ClassTypeId $classTypeId,
        ?UserId $coachId,
        DayOfWeek $dayOfWeek,
        TimeSlot $timeSlot,
        int $maxCapacity,
    ): self {
        if ($maxCapacity < 1) {
            throw new InvalidCapacityException($maxCapacity);
        }

        return new self(
            id:          $id,
            classTypeId: $classTypeId,
            coachId:     $coachId,
            dayOfWeek:   $dayOfWeek,
            timeSlot:    $timeSlot,
            maxCapacity: $maxCapacity,
            status:      ClassSessionStatus::Active,
            createdAt:   new \DateTimeImmutable(),
        );
    }

    public function update(
        ClassTypeId $classTypeId,
        ?UserId $coachId,
        int $maxCapacity,
    ): void {
        if ($maxCapacity < 1) {
            throw new InvalidCapacityException($maxCapacity);
        }

        $this->classTypeId = $classTypeId;
        $this->coachId     = $coachId;
        $this->maxCapacity = $maxCapacity;
    }

    public function cancel(): void
    {
        if ($this->status === ClassSessionStatus::Cancelled) {
            throw new SessionAlreadyCancelledException();
        }

        $this->status = ClassSessionStatus::Cancelled;
    }

    public function restore(): void
    {
        if ($this->status !== ClassSessionStatus::Cancelled) {
            throw new SessionNotCancelledException();
        }

        $this->status = ClassSessionStatus::Active;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function classTypeId(): ClassTypeId
    {
        return $this->classTypeId;
    }

    public function coachId(): ?UserId
    {
        return $this->coachId;
    }

    public function maxCapacity(): int
    {
        return $this->maxCapacity;
    }

    public function status(): ClassSessionStatus
    {
        return $this->status;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
