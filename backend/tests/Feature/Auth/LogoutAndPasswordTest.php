<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class LogoutAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function createUserAndToken(array $overrides = []): array
    {
        $model = UserModel::query()->create([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'test@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'admin',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
            ...$overrides,
        ]);

        $token = $model->createToken('test-token')->plainTextToken;

        return ['model' => $model, 'token' => $token];
    }

    public function test_logout_revokes_token(): void
    {
        ['token' => $token] = $this->createUserAndToken();

        $this->withToken($token)->postJson('/api/auth/logout')->assertStatus(204);

        // Clear the Sanctum guard cache so the next request re-checks the DB
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_change_password_succeeds(): void
    {
        ['token' => $token] = $this->createUserAndToken();

        $this->withToken($token)->putJson('/api/auth/password', [
            'current_password'          => 'Password123',
            'new_password'              => 'NewPass456!',
            'new_password_confirmation' => 'NewPass456!',
        ])->assertStatus(204);

        // Old password no longer works
        $this->postJson('/api/auth/login', [
            'email' => 'test@valhallagym.com', 'password' => 'Password123',
        ])->assertStatus(401);

        // New password works
        $this->postJson('/api/auth/login', [
            'email' => 'test@valhallagym.com', 'password' => 'NewPass456!',
        ])->assertStatus(200);
    }

    public function test_wrong_current_password_returns_422(): void
    {
        ['token' => $token] = $this->createUserAndToken();

        $this->withToken($token)->putJson('/api/auth/password', [
            'current_password'          => 'WrongPass',
            'new_password'              => 'NewPass456!',
            'new_password_confirmation' => 'NewPass456!',
        ])->assertStatus(422)->assertJson(['code' => 'WRONG_CURRENT_PASSWORD']);
    }

    public function test_weak_new_password_returns_422(): void
    {
        ['token' => $token] = $this->createUserAndToken();

        $this->withToken($token)->putJson('/api/auth/password', [
            'current_password'          => 'Password123',
            'new_password'              => 'short',
            'new_password_confirmation' => 'short',
        ])->assertStatus(422)->assertJson(['code' => 'WEAK_PASSWORD']);
    }

    public function test_password_confirmation_mismatch_returns_422(): void
    {
        ['token' => $token] = $this->createUserAndToken();

        $this->withToken($token)->putJson('/api/auth/password', [
            'current_password'          => 'Password123',
            'new_password'              => 'NewPass456!',
            'new_password_confirmation' => 'DifferentPass!',
        ])->assertStatus(422)->assertJson(['code' => 'PASSWORD_MISMATCH']);
    }
}
