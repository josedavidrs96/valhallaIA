<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): array
    {
        $defaults = [
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'test@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ];

        $data = array_merge($defaults, $overrides);
        UserModel::query()->create($data);

        return $data;
    }

    public function test_valid_credentials_return_token(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@valhallagym.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'expires_at', 'user' => ['id', 'role', 'must_change_password']]);
    }

    public function test_wrong_password_returns_401(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@valhallagym.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['code' => 'INVALID_CREDENTIALS']);
    }

    public function test_unknown_email_returns_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'nobody@valhallagym.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['code' => 'INVALID_CREDENTIALS']);
    }

    public function test_inactive_user_returns_403(): void
    {
        $this->createUser([UserTable::STATUS => 'inactive']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@valhallagym.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['code' => 'ACCOUNT_NOT_ACTIVE']);
    }

    public function test_pending_user_returns_403(): void
    {
        $this->createUser([UserTable::STATUS => 'pending_approval']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@valhallagym.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(403)
            ->assertJson(['code' => 'ACCOUNT_NOT_ACTIVE']);
    }

    public function test_must_change_password_flag_in_response(): void
    {
        $this->createUser([UserTable::MUST_CHANGE_PASSWORD => 1]);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@valhallagym.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.must_change_password', true);
    }

    public function test_rate_limit_triggers_on_6th_attempt(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email'    => 'test@valhallagym.com',
                'password' => 'WrongPassword',
            ]);
        }

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@valhallagym.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(429);
    }
}
