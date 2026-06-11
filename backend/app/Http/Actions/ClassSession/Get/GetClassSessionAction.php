<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Get;

use App\Http\Actions\ClassSession\Shared\ClassSessionResource;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdHandler;
use App\Src\Core\ClassSession\Application\Queries\GetClassSessionById\GetClassSessionByIdQuery;
use App\Src\Core\ClassSession\Domain\Exceptions\ClassSessionNotFoundException;
use App\Src\Core\ClassSession\Domain\ValueObjects\ClassSessionId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetClassSessionAction
{
    public function __construct(private readonly GetClassSessionByIdHandler $handler) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        try {
            $rm = $this->handler->handle(new GetClassSessionByIdQuery(
                id: ClassSessionId::fromString($id),
            ));
        } catch (ClassSessionNotFoundException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'NOT_FOUND'], 404);
        }

        return (new ClassSessionResource($rm))->toResponse();
    }
}
