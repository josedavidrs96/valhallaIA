<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\Logout;

use App\Src\Shared\Auth\Application\Commands\Logout\LogoutCommand;
use App\Src\Shared\Auth\Application\Commands\Logout\LogoutHandler;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogoutAction
{
    public function __construct(private readonly LogoutHandler $handler) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user  = $request->user();
        $token = $user->currentAccessToken();

        $this->handler->handle(new LogoutCommand(
            userId:  UserId::fromString($user->id),
            tokenId: $token->id,
        ));

        return response()->json(null, 204);
    }
}
