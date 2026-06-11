<?php

declare(strict_types=1);

namespace App\Http\Actions\Auth\Login;

use App\Http\Actions\Auth\Shared\AuthResource;
use App\Src\Shared\Auth\Application\Queries\Authenticate\AuthenticateHandler;
use App\Src\Shared\Auth\Application\Queries\Authenticate\AuthenticateQuery;
use App\Src\Shared\Auth\Domain\Exceptions\InvalidCredentialsException;
use App\Src\Shared\Auth\Domain\Exceptions\InvalidUserEmailException;
use App\Src\Shared\Auth\Domain\Exceptions\UserCannotLoginException;
use Illuminate\Http\JsonResponse;

final class LoginAction
{
    public function __construct(private readonly AuthenticateHandler $handler) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $dto = $request->getDto();

        try {
            $rm = $this->handler->handle(new AuthenticateQuery(
                email:      $dto->email,
                password:   $dto->password,
                rememberMe: $dto->rememberMe,
            ));
        } catch (InvalidCredentialsException|InvalidUserEmailException) {
            return response()->json(['error' => 'Credenciales incorrectas', 'code' => 'INVALID_CREDENTIALS'], 401);
        } catch (UserCannotLoginException $e) {
            return response()->json(['error' => $e->getMessage(), 'code' => 'ACCOUNT_NOT_ACTIVE'], 403);
        }

        return (new AuthResource($rm))->toResponse();
    }
}
