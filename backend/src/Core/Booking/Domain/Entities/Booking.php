<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Domain\Entities;

use App\Src\Core\Booking\Domain\Enums\BookingStatus;
use App\Src\Core\Booking\Domain\Exceptions\BookingAlreadyCancelledException;
use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;

final class Booking
{
    private BookingStatus $status;

    public function __construct(
        public readonly BookingId $id,
        public readonly MemberId $memberId,
        public readonly ClassSessionId $classSessionId,
        BookingStatus $status,
        public readonly \DateTimeImmutable $createdAt,
    ) {
        $this->status = $status;
    }

    public static function create(BookingId $id, MemberId $memberId, ClassSessionId $classSessionId): self
    {
        return new self($id, $memberId, $classSessionId, BookingStatus::Confirmed, new \DateTimeImmutable());
    }

    public function cancel(): void
    {
        if ($this->status === BookingStatus::Cancelled) {
            throw new BookingAlreadyCancelledException($this->id->value());
        }
        $this->status = BookingStatus::Cancelled;
    }

    public function status(): BookingStatus
    {
        return $this->status;
    }
}
