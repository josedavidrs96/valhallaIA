<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class RoleGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'force.password.change', 'role.admin'])
            ->get('/api/test/admin-only', fn() => response()->json(['ok' => true]));

        Route::middleware(['auth:sanctum', 'force.password.change'])
            ->get('/api/test/authenticated', fn() => response()->json(['ok' => true]));
    }

    private function createUserAndToken(string $role, bool $mustChange = false): string
    {
        $model = UserModel::query()->create([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => "{$role}@valhallagym.com",
            UserTable::PASSWORD             => password_hash('Pass123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => $role,
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => $mustChange ? 1 : 0,
        ]);

        return $model->createToken('test')->plainTextToken;
    }

    public function test_no_token_returns_401(): void
    {
        $this->getJson('/api/test/authenticated')->assertStatus(401);
    }

    public function test_member_cannot_access_admin_route(): void
    {
        $token = $this->createUserAndToken('member');

        $this->withToken($token)->getJson('/api/test/admin-only')->assertStatus(403)
            ->assertJson(['code' => 'INSUFFICIENT_ROLE']);
    }

    public function test_coach_cannot_access_admin_route(): void
    {
        $token = $this->createUserAndToken('coach');

        $this->withToken($token)->getJson('/api/test/admin-only')->assertStatus(403)
            ->assertJson(['code' => 'INSUFFICIENT_ROLE']);
    }

    public function test_admin_can_access_admin_route(): void
    {
        $token = $this->createUserAndToken('admin');

        $this->withToken($token)->getJson('/api/test/admin-only')->assertStatus(200);
    }

    public function test_must_change_password_blocks_protected_routes(): void
    {
        $token = $this->createUserAndToken('admin', mustChange: true);

        $this->withToken($token)->getJson('/api/test/authenticated')
            ->assertStatus(403)
            ->assertJson(['code' => 'MUST_CHANGE_PASSWORD']);
    }

    public function test_must_change_password_does_not_block_password_change_route(): void
    {
        $token = $this->createUserAndToken('admin', mustChange: true);

        $response = $this->withToken($token)->putJson('/api/auth/password', [
            'current_password'          => 'Pass123',
            'new_password'              => 'NewPass456!',
            'new_password_confirmation' => 'NewPass456!',
        ]);

        $response->assertStatus(204);
    }
}
