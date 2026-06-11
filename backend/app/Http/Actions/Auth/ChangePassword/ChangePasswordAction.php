<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\ChangePassword;

use App\Src\Shared\Auth\Application\Commands\ChangePassword\ChangePasswordCommand;
use App\Src\Shared\Auth\Application\Commands\ChangePassword\ChangePasswordHandler;
use App\Src\Shared\Auth\Domain\Exceptions\WeakPasswordException;
use App\Src\Shared\Auth\Domain\Exceptions\WrongCurrentPasswordException;
use App\Src\Shared\Auth\Domain\ValueObjects\UserId;
use Illuminate\Http\JsonResponse;

final class ChangePasswordAction
{
    public function __construct(private readonly ChangePasswordHandler $handler) {}

    public function __invoke(ChangePasswordRequest $request): JsonResponse
    {
        $dto = $request->getDto();

        if ($dto->newPassword !== $dto->newPasswordConfirmation) {
            return response()->json(['error' => 'Password confirmation does not match', 'code' => 'PASSWORD_MISMATCH'], 422);
        }

        try {
            $this->handler->handle(new ChangePasswordCommand(
                userId:          UserId::fromString($request->user()->id),
                currentPassword: $dto->currentPassword,
                newPassword:     $dto->newPassword,
            ));
        } catch (WrongCurrentPasswordException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'WRONG_CURRENT_PASSWORD'], 422);
        } catch (WeakPasswordException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'WEAK_PASSWORD'], 422);
        }

        return response()->json(null, 204);
    }
}
