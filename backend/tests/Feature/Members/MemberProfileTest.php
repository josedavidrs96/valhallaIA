<?php

declare(strict_types=1);

namespace Tests\Feature\Members;

use App\Src\Core\Member\Infrastructure\Tables\MemberPlanAssignmentTable;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createUserAndMember(string $role = 'member', string $status = 'active'): array
    {
        $planId   = (string) new Ulid();
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                => $planId,
            MembershipPlanTable::NAME              => 'Plan 4-5 Dias',
            MembershipPlanTable::SLUG              => 'plan-4-5-dias-' . uniqid(),
            MembershipPlanTable::PRICE_CENTS       => 4000,
            MembershipPlanTable::CLASSES_PER_MONTH => 25,
            MembershipPlanTable::IS_ACTIVE         => 1,
        ]);

        $user = UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => 'member@gym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => $role,
            UserTable::STATUS               => $status,
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        DB::table(MemberTable::TABLE_NAME)->insert([
            MemberTable::ID            => $memberId,
            MemberTable::USER_ID       => $userId,
            MemberTable::MEMBER_NUMBER => 1,
            MemberTable::FIRST_NAME    => 'Carlos',
            MemberTable::LAST_NAME     => 'Ruiz',
            MemberTable::JOIN_DATE     => '2026-06-10',
        ]);

        DB::table(MemberPlanAssignmentTable::TABLE_NAME)->insert([
            MemberPlanAssignmentTable::ID                 => (string) new Ulid(),
            MemberPlanAssignmentTable::MEMBER_ID          => $memberId,
            MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID => $planId,
            MemberPlanAssignmentTable::ASSIGNED_AT        => '2026-06-10',
        ]);

        return ['user' => $user, 'memberId' => $memberId, 'planId' => $planId];
    }

    public function test_authenticated_member_sees_own_profile(): void
    {
        $data = $this->createUserAndMember();

        $response = $this->actingAs($data['user'], 'sanctum')->getJson('/api/member/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'member_number', 'first_name', 'last_name', 'email',
                'status', 'join_date', 'plan' => ['id', 'name'],
            ])
            ->assertJsonPath('email', 'member@gym.com');
    }

    public function test_profile_includes_plan_details(): void
    {
        $data = $this->createUserAndMember();

        $response = $this->actingAs($data['user'], 'sanctum')->getJson('/api/member/profile');

        $response->assertStatus(200)
            ->assertJsonPath('plan.id', $data['planId'])
            ->assertJsonPath('plan.name', 'Plan 4-5 Dias');
    }

    public function test_admin_cannot_access_member_profile_route(): void
    {
        $admin = UserModel::query()->create([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'admin@valhallagym.com',
            UserTable::PASSWORD             => password_hash('P', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'admin',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/member/profile');
        $response->assertStatus(403);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->getJson('/api/member/profile');
        $response->assertStatus(401);
    }

    public function test_member_cannot_access_admin_members_route(): void
    {
        $data = $this->createUserAndMember();

        $response = $this->actingAs($data['user'], 'sanctum')->getJson('/api/admin/members');
        $response->assertStatus(403);
    }
}
