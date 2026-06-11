<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Restore;

use App\Http\Actions\ClassSession\Shared\ClassSessionResource;
use App\Src\Core\ClassSession\Application\Commands\RestoreClassSession\RestoreClassSessionCommand;
use App\Src\Core\ClassSession\Application\Commands\RestoreClassSession\RestoreClassSessionHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdQuery;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\Exceptions\SessionNotCancelledException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RestoreClassSessionAction
{
    public function __construct(
        private readonly RestoreClassSessionHandler $handler,
        private readonly GetClassSessionByIdHandler $query,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $sessionId = ClassSessionId::fromString($id);

        try {
            $this->handler->handle(new RestoreClassSessionCommand(id: $sessionId));
        } catch (ClassSessionNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'NOT_FOUND'], 404);
        } catch (SessionNotCancelledException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'NOT_CANCELLED'], 422);
        }

        $rm = $this->query->handle(new GetClassSessionByIdQuery($sessionId));

        return (new ClassSessionResource($rm))->toResponse();
    }
}
