<?php

declare(strict_types=1);

namespace Tests\Unit\Core\ClassSession\Domain;

use App\Src\Core\ClassSession\Domain\Entities\ClassSession;
use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\Exceptions\InvalidCapacityException;
use App\Src\Core\ClassSession\Domain\Exceptions\SessionAlreadyCancelledException;
use App\Src\Core\ClassSession\Domain\Exceptions\SessionNotCancelledException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassTypeId;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use PHPUnit\Framework\TestCase;

final class ClassSessionTest extends TestCase
{
    private function makeSession(int $capacity = 20): ClassSession
    {
        return ClassSession::create(
            id:          ClassSessionId::random(),
            classTypeId: ClassTypeId::random(),
            coachId:     null,
            dayOfWeek:   DayOfWeek::Monday,
            timeSlot:    new TimeSlot('07:45'),
            maxCapacity: $capacity,
        );
    }

    public function test_create_returns_active_session(): void
    {
        $session = $this->makeSession();

        $this->assertSame(ClassSessionStatus::Active, $session->status());
        $this->assertFalse($session->isDeleted());
        $this->assertNull($session->deletedAt());
    }

    public function test_create_with_invalid_capacity_throws(): void
    {
        $this->expectException(InvalidCapacityException::class);

        $this->makeSession(capacity: 0);
    }

    public function test_create_with_negative_capacity_throws(): void
    {
        $this->expectException(InvalidCapacityException::class);

        $this->makeSession(capacity: -5);
    }

    public function test_cancel_active_session_transitions_to_cancelled(): void
    {
        $session = $this->makeSession();

        $session->cancel();

        $this->assertSame(ClassSessionStatus::Cancelled, $session->status());
    }

    public function test_cancel_already_cancelled_throws(): void
    {
        $this->expectException(SessionAlreadyCancelledException::class);

        $session = $this->makeSession();
        $session->cancel();
        $session->cancel(); // second cancel throws
    }

    public function test_restore_cancelled_session_returns_to_active(): void
    {
        $session = $this->makeSession();
        $session->cancel();

        $session->restore();

        $this->assertSame(ClassSessionStatus::Active, $session->status());
    }

    public function test_restore_active_session_throws(): void
    {
        $this->expectException(SessionNotCancelledException::class);

        $session = $this->makeSession();
        $session->restore(); // not cancelled — throws
    }

    public function test_update_changes_capacity(): void
    {
        $session    = $this->makeSession(20);
        $newTypeId  = ClassTypeId::random();

        $session->update(
            classTypeId: $newTypeId,
            coachId:     null,
            maxCapacity: 30,
        );

        $this->assertSame(30, $session->maxCapacity());
        $this->assertSame($newTypeId->value(), $session->classTypeId()->value());
    }

    public function test_update_with_invalid_capacity_throws(): void
    {
        $this->expectException(InvalidCapacityException::class);

        $session = $this->makeSession();
        $session->update(
            classTypeId: ClassTypeId::random(),
            coachId:     null,
            maxCapacity: 0,
        );
    }

    public function test_soft_delete_sets_deleted_at(): void
    {
        $session = $this->makeSession();

        $session->softDelete();

        $this->assertTrue($session->isDeleted());
        $this->assertInstanceOf(\DateTimeImmutable::class, $session->deletedAt());
    }
}
