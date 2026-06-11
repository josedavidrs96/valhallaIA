<?php

declare(strict_types=1);

namespace App\Src\Shared\Auth\Application\Queries\Authenticate;

use App\Src\Shared\Auth\Domain\Exceptions\InvalidCredentialsException;
use App\Src\Shared\Auth\Domain\Exceptions\UserCannotLoginException;
use App\Src\Shared\Auth\Domain\ReadModels\AuthTokenRM;
use App\Src\Shared\Auth\Domain\Repositories\UserRepositoryInterface;
use App\Src\Shared\Auth\Domain\ValueObjects\UserEmail;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;

/**
 * Architectural exception: this Query generates and stores a Sanctum token (modifies state).
 * Acceptable for security/infrastructure operations where the token must be returned immediately.
 */
final class AuthenticateHandler
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    public function handle(AuthenticateQuery $query): AuthTokenRM
    {
        $user = $this->users->findByEmail(new UserEmail($query->email));

        if ($user === null || !$user->password()->verify($query->password)) {
            throw new InvalidCredentialsException();
        }

        if (!$user->canLogin()) {
            throw new UserCannotLoginException($user->status());
        }

        $model = UserModel::query()
            ->where(UserTable::ID, $user->id->value())
            ->whereNull(UserTable::DELETED_AT)
            ->firstOrFail();

        $expiresAt = $query->rememberMe
            ? now()->addYear()
            : now()->addDays(7);

        $token = $model->createToken(
            name:           'auth-token',
            expiresAt:      $expiresAt,
        );

        return new AuthTokenRM(
            userId:             $user->id,
            token:              $token->plainTextToken,
            expiresAt:          new \DateTimeImmutable($expiresAt->toDateTimeString()),
            role:               $user->role,
            mustChangePassword: $user->mustChangePassword(),
        );
    }
}
