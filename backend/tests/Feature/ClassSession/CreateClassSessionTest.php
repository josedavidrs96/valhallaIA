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

final class CreateClassSessionTest extends TestCase
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
            ClassTypeTable::COLOR     => '#2563eb',
            ClassTypeTable::IS_ACTIVE => 1,
        ]);
        return $id;
    }

    private function createCoach(): UserModel
    {
        $data = [
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'coach@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'coach',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ];
        UserModel::query()->create($data);
        return UserModel::find($data[UserTable::ID]);
    }

    public function test_admin_can_create_class_session(): void
    {
        $admin       = $this->createAdmin();
        $classTypeId = $this->createClassType();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/class-sessions', [
            'class_type_id' => $classTypeId,
            'day_of_week'   => 'monday',
            'time_slot'     => '07:45',
            'max_capacity'  => 20,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'class_type' => ['id', 'name', 'slug', 'color'],
                'day_of_week', 'time_slot', 'max_capacity', 'status', 'created_at',
            ])
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('day_of_week', 'monday')
            ->assertJsonPath('time_slot', '07:45');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->postJson('/api/class-sessions', []);

        $response->assertStatus(401);
    }

    public function test_non_admin_returns_403(): void
    {
        $coach = $this->createCoach();

        $response = $this->actingAs($coach, 'sanctum')->postJson('/api/class-sessions', [
            'class_type_id' => (string) new Ulid(),
            'day_of_week'   => 'monday',
            'time_slot'     => '07:45',
            'max_capacity'  => 20,
        ]);

        $response->assertStatus(403);
    }

    public function test_invalid_time_slot_returns_422(): void
    {
        $admin       = $this->createAdmin();
        $classTypeId = $this->createClassType();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/class-sessions', [
            'class_type_id' => $classTypeId,
            'day_of_week'   => 'monday',
            'time_slot'     => '10:00',
            'max_capacity'  => 20,
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_INPUT');
    }

    public function test_invalid_capacity_returns_422(): void
    {
        $admin       = $this->createAdmin();
        $classTypeId = $this->createClassType();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/class-sessions', [
            'class_type_id' => $classTypeId,
            'day_of_week'   => 'monday',
            'time_slot'     => '07:45',
            'max_capacity'  => 0,
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'INVALID_INPUT');
    }

    public function test_unknown_class_type_returns_422(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/class-sessions', [
            'class_type_id' => (string) new Ulid(),
            'day_of_week'   => 'monday',
            'time_slot'     => '07:45',
            'max_capacity'  => 20,
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'CLASS_TYPE_NOT_FOUND');
    }

    public function test_coach_conflict_returns_409(): void
    {
        $admin       = $this->createAdmin();
        $classTypeId = $this->createClassType();
        $coach       = $this->createCoach();

        // Create first session with coach
        $this->actingAs($admin, 'sanctum')->postJson('/api/class-sessions', [
            'class_type_id' => $classTypeId,
            'coach_id'      => $coach->id,
            'day_of_week'   => 'monday',
            'time_slot'     => '07:45',
            'max_capacity'  => 20,
        ]);

        // Create a second class type for conflict test
        $classTypeId2 = (string) new Ulid();
        DB::table(ClassTypeTable::TABLE_NAME)->insert([
            ClassTypeTable::ID        => $classTypeId2,
            ClassTypeTable::NAME      => 'Full Body',
            ClassTypeTable::SLUG      => 'full-body',
            ClassTypeTable::IS_ACTIVE => 1,
        ]);

        // Try to create another session with same coach at same day+slot
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/class-sessions', [
            'class_type_id' => $classTypeId2,
            'coach_id'      => $coach->id,
            'day_of_week'   => 'monday',
            'time_slot'     => '07:45',
            'max_capacity'  => 20,
        ]);

        $response->assertStatus(409)->assertJsonPath('code', 'COACH_CONFLICT');
    }
}
