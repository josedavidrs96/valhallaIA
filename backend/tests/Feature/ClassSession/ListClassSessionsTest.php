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

final class ListClassSessionsTest extends TestCase
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

    private function createClassType(string $slug = 'tren-superior'): string
    {
        $id = (string) new Ulid();
        DB::table(ClassTypeTable::TABLE_NAME)->insert([
            ClassTypeTable::ID        => $id,
            ClassTypeTable::NAME      => ucfirst(str_replace('-', ' ', $slug)),
            ClassTypeTable::SLUG      => $slug,
            ClassTypeTable::IS_ACTIVE => 1,
        ]);
        return $id;
    }

    private function insertSession(string $classTypeId, string $day, string $slot, string $status = 'active'): void
    {
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => (string) new Ulid(),
            ClassSessionTable::CLASS_TYPE_ID => $classTypeId,
            ClassSessionTable::COACH_ID      => null,
            ClassSessionTable::DAY_OF_WEEK   => $day,
            ClassSessionTable::TIME_SLOT     => $slot,
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => $status,
        ]);
    }

    public function test_admin_can_list_sessions(): void
    {
        $admin  = $this->createAdmin();
        $typeId = $this->createClassType();
        $this->insertSession($typeId, 'monday', '07:45');

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/class-sessions');

        $response->assertStatus(200)
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/class-sessions');

        $response->assertStatus(401);
    }

    public function test_filter_by_day_of_week(): void
    {
        $admin  = $this->createAdmin();
        $typeId = $this->createClassType();
        $this->insertSession($typeId, 'monday', '07:45');
        $this->insertSession($typeId, 'tuesday', '07:45');

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/class-sessions?day_of_week=monday');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.day_of_week', 'monday');
    }

    public function test_filter_by_status(): void
    {
        $admin  = $this->createAdmin();
        $typeId = $this->createClassType();
        $this->insertSession($typeId, 'monday', '07:45', 'active');
        $this->insertSession($typeId, 'tuesday', '07:45', 'cancelled');

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/class-sessions?status=cancelled');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'cancelled');
    }

    public function test_soft_deleted_sessions_excluded(): void
    {
        $admin  = $this->createAdmin();
        $typeId = $this->createClassType();
        DB::table(ClassSessionTable::TABLE_NAME)->insert([
            ClassSessionTable::ID            => (string) new Ulid(),
            ClassSessionTable::CLASS_TYPE_ID => $typeId,
            ClassSessionTable::COACH_ID      => null,
            ClassSessionTable::DAY_OF_WEEK   => 'monday',
            ClassSessionTable::TIME_SLOT     => '07:45',
            ClassSessionTable::MAX_CAPACITY  => 20,
            ClassSessionTable::STATUS        => 'active',
            ClassSessionTable::DELETED_AT    => now()->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/class-sessions');

        $response->assertStatus(200)->assertJsonCount(0, 'data');
    }
}
