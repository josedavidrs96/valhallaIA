<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\Me;

use App\Src\Shared\Auth\Application\Queries\GetAuthenticatedUser\GetAuthenticatedUserHandler;
use App\Src\Shared\Auth\Application\Queries\GetAuthenticatedUser\GetAuthenticatedUserQuery;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetCurrentUserAction
{
    public function __construct(private readonly GetAuthenticatedUserHandler $handler) {}

    public function __invoke(Request $request): JsonResponse
    {
        $rm = $this->handler->handle(
            new GetAuthenticatedUserQuery(UserId::fromString($request->user()->id))
        );

        return (new CurrentUserResource($rm))->toResponse();
    }
}
