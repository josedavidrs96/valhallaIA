<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Coach;

use App\Http\Actions\ClassSession\Shared\ClassSessionListResource;
use App\Src\Core\ClassSession\Application\Queries\GetCoachSessions\GetCoachSessionsHandler;
use App\Src\Core\ClassSession\Application\Queries\GetCoachSessions\GetCoachSessionsQuery;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetCoachSessionsAction
{
    public function __construct(private readonly GetCoachSessionsHandler $handler) {}

    public function __invoke(Request $request): JsonResponse
    {
        $sessions = $this->handler->handle(new GetCoachSessionsQuery(
            coachId: UserId::fromString($request->user()->id),
        ));

        return (new ClassSessionListResource($sessions))->toResponse();
    }
}
