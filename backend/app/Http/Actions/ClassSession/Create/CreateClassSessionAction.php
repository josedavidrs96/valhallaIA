<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Create;

use App\Http\Actions\ClassSession\Shared\ClassSessionResource;
use App\Src\Core\ClassSession\Application\Commands\CreateClassSession\CreateClassSessionCommand;
use App\Src\Core\ClassSession\Application\Commands\CreateClassSession\CreateClassSessionHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdQuery;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassTypeNotFoundException;
use App\Src\Core\ClassSession\Domain\Exceptions\CoachConflictException;
use App\Src\Core\ClassSession\Domain\Exceptions\CoachNotFoundException;
use App\Src\Core\ClassSession\Domain\Exceptions\InvalidCapacityException;
use App\Src\Core\ClassSession\Domain\Exceptions\InvalidTimeSlotException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassTypeId;
use App\Src\Core\ClassSession\Domain\ValueObjects\TimeSlot;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;

final class CreateClassSessionAction
{
    public function __construct(
        private readonly CreateClassSessionHandler $handler,
        private readonly GetClassSessionByIdHandler $query,
    ) {}

    public function __invoke(CreateClassSessionRequest $request): JsonResponse
    {
        $dto = $request->getDto();
        $id  = ClassSessionId::random();

        try {
            $this->handler->handle(new CreateClassSessionCommand(
                id:          $id,
                classTypeId: ClassTypeId::fromString($dto->classTypeId),
                coachId:     $dto->coachId ? UserId::fromString($dto->coachId) : null,
                dayOfWeek:   DayOfWeek::from($dto->dayOfWeek),
                timeSlot:    new TimeSlot($dto->timeSlot),
                maxCapacity: $dto->maxCapacity,
            ));
        } catch (ClassTypeNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'CLASS_TYPE_NOT_FOUND'], 422);
        } catch (CoachNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'COACH_NOT_FOUND'], 422);
        } catch (CoachConflictException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'COACH_CONFLICT'], 409);
        } catch (InvalidCapacityException|InvalidTimeSlotException|\ValueError $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'INVALID_INPUT'], 422);
        }

        $rm = $this->query->handle(new GetClassSessionByIdQuery($id));

        return (new ClassSessionResource($rm))->toResponse(201);
    }
}
