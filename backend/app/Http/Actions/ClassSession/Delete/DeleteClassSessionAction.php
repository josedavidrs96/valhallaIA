<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Delete;

use App\Src\Core\ClassSession\Application\Commands\DeleteClassSession\DeleteClassSessionCommand;
use App\Src\Core\ClassSession\Application\Commands\DeleteClassSession\DeleteClassSessionHandler;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteClassSessionAction
{
    public function __construct(private readonly DeleteClassSessionHandler $handler) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $this->handler->handle(new DeleteClassSessionCommand(
                id: ClassSessionId::fromString($id),
            ));
        } catch (ClassSessionNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'NOT_FOUND'], 404);
        }

        return response()->json(null, 204);
    }
}
