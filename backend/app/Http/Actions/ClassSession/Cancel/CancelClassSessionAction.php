<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Cancel;

use App\Http\Actions\ClassSession\Shared\ClassSessionResource;
use App\Src\Core\ClassSession\Application\Commands\CancelClassSession\CancelClassSessionCommand;
use App\Src\Core\ClassSession\Application\Commands\CancelClassSession\CancelClassSessionHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdQuery;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\Exceptions\SessionAlreadyCancelledException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CancelClassSessionAction
{
    public function __construct(
        private readonly CancelClassSessionHandler $handler,
        private readonly GetClassSessionByIdHandler $query,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $sessionId = ClassSessionId::fromString($id);

        try {
            $this->handler->handle(new CancelClassSessionCommand(id: $sessionId));
        } catch (ClassSessionNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'NOT_FOUND'], 404);
        } catch (SessionAlreadyCancelledException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'ALREADY_CANCELLED'], 422);
        }

        $rm = $this->query->handle(new GetClassSessionByIdQuery($sessionId));

        return (new ClassSessionResource($rm))->toResponse();
    }
}
