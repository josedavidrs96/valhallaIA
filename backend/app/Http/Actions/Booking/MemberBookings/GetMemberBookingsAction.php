<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\MemberBookings;

use App\Http\Actions\Booking\Shared\BookingListResource;
use App\Src\Core\Booking\Application\Queries\GetMemberBookings\GetMemberBookingsHandler;
use App\Src\Core\Booking\Application\Queries\GetMemberBookings\GetMemberBookingsQuery;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetMemberBookingsAction
{
    public function __construct(
        private readonly GetMemberBookingsHandler  $handler,
        private readonly MemberRepositoryInterface $memberRepo,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $userId = UserId::fromString((string) $request->user()->id);
        $member = $this->memberRepo->findByUserId($userId);

        if ($member === null) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        $result = $this->handler->handle(new GetMemberBookingsQuery($member->id));

        return (new BookingListResource(
            $result['bookings'],
            $result['weekly_used'],
            $result['weekly_max'],
        ))->toResponse();
    }
}
