<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\Cancel;

use App\Http\Actions\Booking\Shared\BookingResource;
use App\Src\Core\Booking\Application\Commands\CancelBooking\CancelBookingCommand;
use App\Src\Core\Booking\Application\Commands\CancelBooking\CancelBookingHandler;
use App\Src\Core\Booking\Application\Queries\GetBookingById\GetBookingByIdHandler;
use App\Src\Core\Booking\Application\Queries\GetBookingById\GetBookingByIdQuery;
use App\Src\Core\Booking\Domain\Exceptions\BookingAlreadyCancelledException;
use App\Src\Core\Booking\Domain\Exceptions\BookingNotFoundException;
use App\Src\Core\Booking\Domain\Exceptions\BookingNotOwnedException;
use App\Src\Core\Booking\Domain\Exceptions\CancellationWindowExpiredException;
use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CancelBookingAction
{
    public function __construct(
        private readonly CancelBookingHandler      $handler,
        private readonly GetBookingByIdHandler     $query,
        private readonly MemberRepositoryInterface $memberRepo,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $userId = UserId::fromString((string) $request->user()->id);
        $member = $this->memberRepo->findByUserId($userId);

        if ($member === null) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        $bookingId = BookingId::fromString($id);

        try {
            $this->handler->handle(new CancelBookingCommand($bookingId, $member->id));
        } catch (BookingNotFoundException) {
            return response()->json(['error' => 'Reserva no encontrada', 'code' => 'BOOKING_NOT_FOUND'], 404);
        } catch (BookingNotOwnedException) {
            return response()->json(['error' => 'No tienes permiso para cancelar esta reserva', 'code' => 'BOOKING_NOT_OWNED'], 403);
        } catch (BookingAlreadyCancelledException) {
            return response()->json(['error' => 'La reserva ya esta cancelada', 'code' => 'BOOKING_ALREADY_CANCELLED'], 422);
        } catch (CancellationWindowExpiredException) {
            return response()->json(['error' => 'No puedes cancelar una sesion que ya ha pasado', 'code' => 'CANCELLATION_WINDOW_EXPIRED'], 422);
        }

        $rm = $this->query->handle(new GetBookingByIdQuery($bookingId));

        return (new BookingResource($rm))->toResponse(200);
    }
}
