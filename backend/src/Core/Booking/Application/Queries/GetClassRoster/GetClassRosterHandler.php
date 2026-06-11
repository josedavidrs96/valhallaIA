<?php

declare(strict_types=1);

namespace App\Src\Core\Booking\Application\Queries\GetClassRoster;

use App\Src\Core\Booking\Domain\Repositories\BookingRepositoryInterface;
use App\Src\Core\ClassSession\Domain\Repositories\ClassSessionRepositoryInterface;

final class GetClassRosterHandler
{
    public function __construct(
        private readonly BookingRepositoryInterface      $bookingRepo,
        private readonly ClassSessionRepositoryInterface $sessionRepo,
    ) {}

    public function handle(GetClassRosterQuery $query): array
    {
        $session        = $this->sessionRepo->getById($query->classSessionId);
        $items          = $this->bookingRepo->getRoster($query->classSessionId);
        $confirmedCount = $this->bookingRepo->countConfirmedBySession($query->classSessionId);

        return [
            'items'          => $items,
            'confirmed_count' => $confirmedCount,
            'max_capacity'   => $session->maxCapacity(),
        ];
    }
}
