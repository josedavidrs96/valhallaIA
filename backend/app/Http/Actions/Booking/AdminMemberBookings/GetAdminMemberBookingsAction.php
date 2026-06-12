<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\AdminMemberBookings;

use App\Http\Actions\Booking\Shared\BookingListResource;
use App\Src\Core\Booking\Application\Queries\GetMemberBookings\GetMemberBookingsHandler;
use App\Src\Core\Booking\Application\Queries\GetMemberBookings\GetMemberBookingsQuery;
use App\Src\Core\Member\Domain\ValueObjects\MemberId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetAdminMemberBookingsAction
{
    public function __construct(
        private readonly GetMemberBookingsHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $memberId = MemberId::fromString($id);
        $result   = $this->handler->handle(new GetMemberBookingsQuery($memberId));

        return (new BookingListResource(
            $result['bookings'],
            $result['weekly_used'],
            $result['weekly_max'],
        ))->toResponse();
    }
}
