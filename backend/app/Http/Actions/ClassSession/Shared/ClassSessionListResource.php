<?php

declare(strict_types=1);

namespace App\Http\Actions\ClassSession\Shared;

use App\Src\Core\ClassSession\Domain\ReadModels\ClassSessionRM;
use Illuminate\Http\JsonResponse;

final class ClassSessionListResource
{
    /** @param ClassSessionRM[] $sessions */
    public function __construct(private readonly array $sessions) {}

    public function toResponse(): JsonResponse
    {
        $data = array_map(
            fn(ClassSessionRM $rm) => (new ClassSessionResource($rm))->toArray(),
            $this->sessions,
        );

        return response()->json(['data' => $data]);
    }
}
