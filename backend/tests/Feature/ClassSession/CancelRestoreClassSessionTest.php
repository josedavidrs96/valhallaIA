<?php

declare(strict_types=1);

namespace Tests\Feature\ClassSession;

use App\Src\Core\ClassSession\Infrastructure\Tables\ClassSessionTable;
use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class CancelRestoreClassSessionTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): UserModel
    {
        $data = [
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'admin@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'admin',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ];
        UserModel::query()->create($data);
        return UserModel::find($data[UserTable::ID]);
    }

    private function createClassType(): string
    {
        $id = (string) new Ulid();
        DB::table(ClassTypeTable::TABLE_NAME)->insert([
            ClassTypeTable::ID        => $id,
            ClassTypeTable::NAME      => 'Tren Superior',
            ClassTypeTable::SLUG      => 'tren-superior',
            ClassTypeTable::IS_ACTIVE => 1,
        ]);
        return $id;
    }

    private function insertSession(string $classTypeId, string $status = 'active'): string
    {
        $id = (string) new Ulid();
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => $id,
            ClassSessionTable::CLASS_TYPE_ID => $classTypeId,
            ClassSessionTable::COACH_ID      => null,
            ClassSessionTable::DAY_OF_WEEK   => 'monday',
            ClassSessionTable::TIME_SLOT     => '07:45',
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => $status,
        ]);
        return $id;
    }

    public function test_admin_can_cancel_active_session(): void
    {
        $admin  = $this->createAdmin();
        $typeId = $this->createClassType();
        $id     = $this->insertSession($typeId);

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/class-sessions/{$id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'cancelled');
    }

    public function test_cancel_already_cancelled_returns_422(): void
    {
        $admin  = $this->createAdmin();
        $typeId = $this->createClassType();
        $id     = $this->insertSession($typeId, 'cancelled');

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/class-sessions/{$id}/cancel");

        $response->assertStatus(422)->assertJsonPath('code', 'ALREADY_CANCELLED');
    }

    public function test_admin_can_restore_cancelled_session(): void
    {
        $admin  = $this->createAdmin();
        $typeId = $this->createClassType();
        $id     = $this->insertSession($typeId, 'cancelled');

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/class-sessions/{$id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'active');
    }

    public function test_restore_active_session_returns_422(): void
    {
        $admin  = $this->createAdmin();
        $typeId = $this->createClassType();
        $id     = $this->insertSession($typeId, 'active');

        $response = $this->actingAs($admin, 'sanctum')->patchJson("/api/class-sessions/{$id}/restore");

        $response->assertStatus(422)->assertJsonPath('code', 'NOT_CANCELLED');
    }

    public function test_cancel_unknown_session_returns_404(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')->patchJson('/api/class-sessions/' . new Ulid() . '/cancel');

        $response->assertStatus(404);
    }
}
