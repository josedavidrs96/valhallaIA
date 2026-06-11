<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Update;

use App\Http\Actions\ClassSession\Shared\ClassSessionResource;
use App\Src\Core\ClassSession\Application\Commands\UpdateClassSession\UpdateClassSessionCommand;
use App\Src\Core\ClassSession\Application\Commands\UpdateClassSession\UpdateClassSessionHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdQuery;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassTypeNotFoundException;
use App\Src\Core\ClassSession\Domain\Exceptions\CoachConflictException;
use App\Src\Core\ClassSession\Domain\Exceptions\CoachNotFoundException;
use App\Src\Core\ClassSession\Domain\Exceptions\InvalidCapacityException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassTypeId;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;

final class UpdateClassSessionAction
{
    public function __construct(
        private readonly UpdateClassSessionHandler $handler,
        private readonly GetClassSessionByIdHandler $query,
    ) {}

    public function __invoke(UpdateClassSessionRequest $request, string $id): JsonResponse
    {
        $dto       = $request->getDto();
        $sessionId = ClassSessionId::fromString($id);

        try {
            $this->handler->handle(new UpdateClassSessionCommand(
                id:          $sessionId,
                classTypeId: ClassTypeId::fromString($dto->classTypeId),
                coachId:     $dto->coachId ? UserId::fromString($dto->coachId) : null,
                maxCapacity: $dto->maxCapacity,
            ));
        } catch (ClassSessionNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'NOT_FOUND'], 404);
        } catch (ClassTypeNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'CLASS_TYPE_NOT_FOUND'], 422);
        } catch (CoachNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'COACH_NOT_FOUND'], 422);
        } catch (CoachConflictException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'COACH_CONFLICT'], 409);
        } catch (InvalidCapacityException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'INVALID_INPUT'], 422);
        }

        $rm = $this->query->handle(new GetClassSessionByIdQuery($sessionId));

        return (new ClassSessionResource($rm))->toResponse();
    }
}
