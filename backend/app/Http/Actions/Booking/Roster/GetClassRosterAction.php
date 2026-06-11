<?php

declare(strict_types=1);

namespace App\Http\Actions\Booking\Roster;

use App\Http\Actions\Booking\Shared\RosterResource;
use App\Src\Core\Booking\Application\Queries\GetClassRoster\GetClassRosterHandler;
use App\Src\Core\Booking\Application\Queries\GetClassRoster\GetClassRosterQuery;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetClassRosterAction
{
    public function __construct(
        private readonly GetClassRosterHandler $handler,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $sessionId = ClassSessionId::fromString($id);

        try {
            $result = $this->handler->handle(new GetClassRosterQuery($sessionId));
        } catch (ClassSessionNotFoundException) {
            return response()->json(['error' => 'Sesion no encontrada', 'code' => 'SESSION_NOT_FOUND'], 404);
        }

        return (new RosterResource(
            $result['items'],
            $result['confirmed_count'],
            $result['max_capacity'],
        ))->toResponse();
    }
}
