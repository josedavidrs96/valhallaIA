<?php

declare(strict_types=1);

namespace Tests\Feature\Members;

use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class CreateMemberTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(array $overrides = []): array
    {
        $data = array_merge([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'admin@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'admin',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ], $overrides);

        UserModel::query()->create($data);
        return $data;
    }

    private function createPlan(array $overrides = []): array
    {
        $data = array_merge([
            MembershipPlanTable::ID                 => (string) new Ulid(),
            MembershipPlanTable::NAME               => 'Plan 4-5 Dias',
            MembershipPlanTable::SLUG               => 'plan-4-5-dias',
            MembershipPlanTable::PRICE_CENTS        => 4000,
            MembershipPlanTable::CLASSES_PER_MONTH  => 25,
            MembershipPlanTable::IS_ACTIVE          => 1,
        ], $overrides);

        \Illuminate\Support\Facades\DB::table(MembershipPlanTable::TABLE_NAME)->insert($data);
        return $data;
    }

    private function actingAsAdmin(): UserModel
    {
        $admin = $this->createAdmin();
        return UserModel::find($admin[UserTable::ID]);
    }

    public function test_admin_can_create_member(): void
    {
        $admin = $this->actingAsAdmin();
        $plan  = $this->createPlan();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/members', [
            'email'              => 'carlos@gym.com',
            'first_name'         => 'Carlos',
            'last_name'          => 'Ruiz',
            'membership_plan_id' => $plan[MembershipPlanTable::ID],
            'join_date'          => '2026-06-10',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id', 'user_id', 'member_number', 'first_name', 'last_name',
                'email', 'status', 'join_date', 'plan' => ['id', 'name'],
            ])
            ->assertJsonPath('email', 'carlos@gym.com')
            ->assertJsonPath('status', 'pending_approval')
            ->assertJsonPath('member_number', 1);
    }

    public function test_duplicate_email_returns_409(): void
    {
        $admin = $this->actingAsAdmin();
        $plan  = $this->createPlan();

        // Create first member
        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/members', [
            'email'              => 'dup@gym.com',
            'first_name'         => 'A',
            'last_name'          => 'B',
            'membership_plan_id' => $plan[MembershipPlanTable::ID],
            'join_date'          => '2026-06-10',
        ]);

        // Try again with same email
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/members', [
            'email'              => 'dup@gym.com',
            'first_name'         => 'C',
            'last_name'          => 'D',
            'membership_plan_id' => $plan[MembershipPlanTable::ID],
            'join_date'          => '2026-06-10',
        ]);

        $response->assertStatus(409)
            ->assertJson(['code' => 'MEMBER_EMAIL_ALREADY_EXISTS']);
    }

    public function test_non_existent_plan_returns_422(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/members', [
            'email'              => 'ok@gym.com',
            'first_name'         => 'A',
            'last_name'          => 'B',
            'membership_plan_id' => (string) new Ulid(), // Random non-existent
            'join_date'          => '2026-06-10',
        ]);

        $response->assertStatus(422)
            ->assertJson(['code' => 'MEMBERSHIP_PLAN_NOT_FOUND']);
    }

    public function test_non_admin_member_role_returns_403(): void
    {
        $plan = $this->createPlan();

        // Create a member user
        $memberUser = UserModel::query()->create([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'member@gym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        $response = $this->actingAs($memberUser, 'sanctum')->postJson('/api/admin/members', [
            'email'              => 'new@gym.com',
            'first_name'         => 'X',
            'last_name'          => 'Y',
            'membership_plan_id' => $plan[MembershipPlanTable::ID],
            'join_date'          => '2026-06-10',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->postJson('/api/admin/members', [
            'email'              => 'x@gym.com',
            'first_name'         => 'A',
            'last_name'          => 'B',
            'membership_plan_id' => (string) new Ulid(),
            'join_date'          => '2026-06-10',
        ]);

        $response->assertStatus(401);
    }
}
