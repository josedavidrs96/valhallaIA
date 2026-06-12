<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Infrastructure\Hydrators;

use App\Src\Core\Booking\Domain\Entities\Booking;
use App\Src\Core\Booking\Domain\Enums\BookingStatus;
use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\Booking\Infrastructure\Persistence\BookingModel;
use App\Src\Core\Booking\Infrastructure\Tables\BookingTable;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final class BookingHydrator
{
    public function hydrate(BookingModel $model): Booking
    {
        return new Booking(
            id:             BookingId::fromString((string) $model->{BookingTable::ID}),
            memberId:       MemberId::fromString((string) $model->{BookingTable::MEMBER_ID}),
            classSessionId: ClassSessionId::fromString((string) $model->{BookingTable::CLASS_SESSION_ID}),
            sessionDate:    new \DateTimeImmutable((string) $model->{BookingTable::SESSION_DATE}),
            status:         BookingStatus::from((string) $model->{BookingTable::STATUS}),
            createdAt:      $model->{BookingTable::CREATED_AT}
                                ? new \DateTimeImmutable((string) $model->{BookingTable::CREATED_AT})
                                : new \DateTimeImmutable(),
        );
    }

    public function dehydrate(Booking $booking): array
    {
        return [
            BookingTable::ID               => $booking->id->value(),
            BookingTable::MEMBER_ID        => $booking->memberId->value(),
            BookingTable::CLASS_SESSION_ID => $booking->classSessionId->value(),
            BookingTable::SESSION_DATE     => $booking->sessionDate->format('Y-m-d'),
            BookingTable::STATUS           => $booking->status()->value,
        ];
    }
}
