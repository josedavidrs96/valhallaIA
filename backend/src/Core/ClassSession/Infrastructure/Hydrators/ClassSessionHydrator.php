<?php

declare(strict_types=1);

namespace App\Src\Core\ClassSession\Infrastructure\Hydrators;

use App\Src\Core\ClassSession\Domain\Entities\ClassSession;
use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassTypeId;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use App\Src\Core\ClassSession\Infrastructure\Persistence\ClassSessionModel;
use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;

final class ClassSessionHydrator
{
    public function hydrate(ClassSessionModel $model): ClassSession
    {
        return new ClassSession(
            id:          ClassSessionId::fromString($model->{ClassSessionTable::ID}),
            classTypeId: ClassTypeId::fromString($model->{ClassSessionTable::CLASS_TYPE_ID}),
            coachId:     $model->{ClassSessionTable::COACH_ID}
                             ? UserId::fromString($model->{ClassSessionTable::COACH_ID})
                             : null,
            dayOfWeek:   DayOfWeek::from($model->{ClassSessionTable::DAY_OF_WEEK}),
            timeSlot:    new TimeSlot($model->{ClassSessionTable::TIME_SLOT}),
            maxCapacity: (int) $model->{ClassSessionTable::MAX_CAPACITY},
            status:      ClassSessionStatus::from($model->{ClassSessionTable::STATUS}),
            createdAt:   $model->{ClassSessionTable::CREATED_AT}
                             ? new \DateTimeImmutable((string) $model->{ClassSessionTable::CREATED_AT})
                             : new \DateTimeImmutable(),
            deletedAt:   $model->{ClassSessionTable::DELETED_AT}
                             ? new \DateTimeImmutable((string) $model->{ClassSessionTable::DELETED_AT})
                             : null,
        );
    }

    public function dehydrate(ClassSession $session): array
    {
        return [
            ClassSessionTable::ID            => $session->id->value(),
            ClassSessionTable::CLASS_TYPE_ID => $session->classTypeId()->value(),
            ClassSessionTable::COACH_ID      => $session->coachId()?->value(),
            ClassSessionTable::DAY_OF_WEEK   => $session->dayOfWeek->value,
            ClassSessionTable::TIME_SLOT     => $session->timeSlot->value,
            ClassSessionTable::MAX_CAPACITY  => $session->maxCapacity(),
            ClassSessionTable::STATUS        => $session->status()->value,
            ClassSessionTable::DELETED_AT    => $session->deletedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
