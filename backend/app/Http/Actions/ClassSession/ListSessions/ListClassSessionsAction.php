<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\ListSessions;

use App\Http\Actions\ClassSession\Shared\ClassSessionListResource;
use App\Src\Core\ClassSession\Application\Queries\ListClassSessions\ListClassSessionsHandler;
use App\Src\Core\ClassSession\Application\Queries\ListClassSessions\ListClassSessionsQuery;
use App\Src\Core\ClassSession\Domain\Enums\ClassSessionStatus;
use App\Src\Core\ClassSession\Domain\Enums\DayOfWeek;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;

final class ListClassSessionsAction
{
    public function __construct(private readonly ListClassSessionsHandler $handler) {}

    public function __invoke(ListClassSessionsRequest $request): JsonResponse
    {
        $dto = $request->getDto();

        $sessions = $this->handler->handle(new ListClassSessionsQuery(
            dayOfWeek: $dto->dayOfWeek ? DayOfWeek::tryFrom($dto->dayOfWeek) : null,
            coachId:   $dto->coachId ? UserId::fromString($dto->coachId) : null,
            status:    $dto->status ? ClassSessionStatus::tryFrom($dto->status) : null,
        ));

        return (new ClassSessionListResource($sessions))->toResponse();
    }
}
