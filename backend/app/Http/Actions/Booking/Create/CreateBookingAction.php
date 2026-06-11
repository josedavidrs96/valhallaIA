<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\Create;

use App\Http\Actions\Booking\Shared\BookingResource;
use App\Src\Core\Booking\Application\Commands\CreateBooking\CreateBookingCommand;
use App\Src\Core\Booking\Application\Commands\CreateBooking\CreateBookingHandler;
use App\Src\Core\Booking\Application\Queries\GetBookingById\GetBookingByIdHandler;
use App\Src\Core\Booking\Application\Queries\GetBookingById\GetBookingByIdQuery;
use App\Src\Core\Booking\Domain\Exceptions\BookingAlreadyExistsException;
use App\Src\Core\Booking\Domain\Exceptions\SessionFullException;
use App\Src\Core\Booking\Domain\Exceptions\SessionNotAvailableException;
use App\Src\Core\Booking\Domain\ValueObjects\BookingId;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\Member\Domain\Repositories\MemberRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;

final class CreateBookingAction
{
    public function __construct(
        private readonly CreateBookingHandler      $handler,
        private readonly GetBookingByIdHandler     $query,
        private readonly MemberRepositoryInterface $memberRepo,
    ) {}

    public function __invoke(CreateBookingRequest $request): JsonResponse
    {
        $dto    = $request->getDto();
        $userId = UserId::fromString((string) $request->user()->id);
        $member = $this->memberRepo->findByUserId($userId);

        if ($member === null) {
            return response()->json(['error' => 'Socio no encontrado', 'code' => 'MEMBER_NOT_FOUND'], 404);
        }

        $bookingId = BookingId::random();

        try {
            $this->handler->handle(new CreateBookingCommand(
                $bookingId,
                $member->id,
                ClassSessionId::fromString($dto->classSessionId),
            ));
        } catch (ClassSessionNotFoundException) {
            return response()->json(['error' => 'Sesion no encontrada', 'code' => 'SESSION_NOT_FOUND'], 404);
        } catch (SessionNotAvailableException) {
            return response()->json(['error' => 'La sesion no esta disponible', 'code' => 'SESSION_NOT_AVAILABLE'], 422);
        } catch (SessionFullException) {
            return response()->json(['error' => 'La sesion esta completa', 'code' => 'SESSION_FULL'], 422);
        } catch (BookingAlreadyExistsException) {
            return response()->json(['error' => 'Ya tienes una reserva para esta sesion', 'code' => 'BOOKING_ALREADY_EXISTS'], 409);
        }

        $rm = $this->query->handle(new GetBookingByIdQuery($bookingId));

        return (new BookingResource($rm))->toResponse(201);
    }
}
