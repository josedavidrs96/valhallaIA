<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Auth;

use App\Src\Shared\Auth\Application\Queries\Authenticate\AuthenticateHandler;
use App\Src\Shared\Auth\Application\Queries\Authenticate\AuthenticateQuery;
use App\Src\Shared\Auth\Domain\Exceptions\InvalidCredentialsException;
use App\Src\Shared\Auth\Domain\Exceptions\UserCannotLoginException;
use App\Src\Shared\Auth\Domain\ReadModels\AuthTokenRM;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Repositories\UserRepository;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use App\Src\Shared\Auth\Infrastructure\Hydrators\UserHydrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class AuthenticateHandlerTest extends TestCase
{
    use RefreshDatabase;

    private AuthenticateHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new AuthenticateHandler(new UserRepository(new UserHydrator()));
    }

    private function createUser(array $overrides = []): void
    {
        UserModel::query()->create(array_merge([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'admin@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'admin',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ], $overrides));
    }

    public function test_valid_credentials_return_auth_token_rm(): void
    {
        $this->createUser();

        $result = $this->handler->handle(new AuthenticateQuery('admin@valhallagym.com', 'Password123', false));

        $this->assertInstanceOf(AuthTokenRM::class, $result);
        $this->assertNotEmpty($result->token);
        $this->assertSame('admin', $result->role->value);
    }

    public function test_wrong_password_throws_invalid_credentials(): void
    {
        $this->createUser();

        $this->expectException(InvalidCredentialsException::class);
        $this->handler->handle(new AuthenticateQuery('admin@valhallagym.com', 'WrongPass', false));
    }

    public function test_unknown_email_throws_invalid_credentials(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->handler->handle(new AuthenticateQuery('nobody@valhallagym.com', 'Password123', false));
    }

    public function test_inactive_user_throws_cannot_login(): void
    {
        $this->createUser([UserTable::STATUS => 'inactive']);

        $this->expectException(UserCannotLoginException::class);
        $this->handler->handle(new AuthenticateQuery('admin@valhallagym.com', 'Password123', false));
    }

    public function test_suspended_user_throws_cannot_login(): void
    {
        $this->createUser([UserTable::ROLE => 'member', UserTable::STATUS => 'suspended']);

        $this->expectException(UserCannotLoginException::class);
        $this->handler->handle(new AuthenticateQuery('admin@valhallagym.com', 'Password123', false));
    }

    public function test_pending_approval_user_throws_cannot_login(): void
    {
        $this->createUser([UserTable::STATUS => 'pending_approval']);

        $this->expectException(UserCannotLoginException::class);
        $this->handler->handle(new AuthenticateQuery('admin@valhallagym.com', 'Password123', false));
    }

    public function test_remember_me_false_sets_7_day_expiry(): void
    {
        $this->createUser();

        $result = $this->handler->handle(new AuthenticateQuery('admin@valhallagym.com', 'Password123', false));

        $diff = $result->expiresAt->getTimestamp() - time();
        $this->assertGreaterThan(6 * 86400, $diff);
        $this->assertLessThan(8 * 86400, $diff);
    }

    public function test_remember_me_true_sets_365_day_expiry(): void
    {
        $this->createUser();

        $result = $this->handler->handle(new AuthenticateQuery('admin@valhallagym.com', 'Password123', true));

        $diff = $result->expiresAt->getTimestamp() - time();
        $this->assertGreaterThan(364 * 86400, $diff);
        $this->assertLessThan(366 * 86400, $diff);
    }

    public function test_must_change_password_flag_propagated(): void
    {
        $this->createUser([UserTable::MUST_CHANGE_PASSWORD => 1]);

        $result = $this->handler->handle(new AuthenticateQuery('admin@valhallagym.com', 'Password123', false));

        $this->assertTrue($result->mustChangePassword);
    }
}
