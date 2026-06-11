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

final class ListMembersTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): UserModel
    {
        return UserModel::query()->create([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'admin@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'admin',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);
    }

    private function createPlan(string $slug = 'plan-test', bool $active = true): string
    {
        $id = (string) new Ulid();
        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                => $id,
            MembershipPlanTable::NAME              => 'Plan Test',
            MembershipPlanTable::SLUG              => $slug,
            MembershipPlanTable::PRICE_CENTS       => 4000,
            MembershipPlanTable::CLASSES_PER_MONTH => 25,
            MembershipPlanTable::IS_ACTIVE         => $active ? 1 : 0,
        ]);
        return $id;
    }

    private function createMemberWithUser(string $email, string $status, string $planId, int $number): string
    {
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => $email,
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => $status,
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        DB::table(MemberTable::TABLE_NAME)->insert([
            MemberTable::ID            => $memberId,
            MemberTable::USER_ID       => $userId,
            MemberTable::MEMBER_NUMBER => $number,
            MemberTable::FIRST_NAME    => 'Test',
            MemberTable::LAST_NAME     => 'User',
            MemberTable::JOIN_DATE     => '2026-06-10',
        ]);

        DB::table(MemberPlanAssignmentTable::TABLE_NAME)->insert([
            MemberPlanAssignmentTable::ID                 => (string) new Ulid(),
            MemberPlanAssignmentTable::MEMBER_ID          => $memberId,
            MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID => $planId,
            MemberPlanAssignmentTable::ASSIGNED_AT        => '2026-06-10',
        ]);

        return $memberId;
    }

    public function test_returns_paginated_list(): void
    {
        $admin  = $this->createAdmin();
        $planId = $this->createPlan();
        $this->createMemberWithUser('m1@gym.com', 'active', $planId, 1);
        $this->createMemberWithUser('m2@gym.com', 'active', $planId, 2);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/members');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'member_number', 'first_name', 'last_name', 'email', 'status', 'join_date']],
                'meta' => ['total', 'page', 'per_page'],
            ])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_filter_by_status_active(): void
    {
        $admin  = $this->createAdmin();
        $planId = $this->createPlan();
        $this->createMemberWithUser('active@gym.com', 'active', $planId, 1);
        $this->createMemberWithUser('pending@gym.com', 'pending_approval', $planId, 2);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/members?status=active');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'active');
    }

    public function test_filter_by_plan_id(): void
    {
        $admin   = $this->createAdmin();
        $plan1   = $this->createPlan('plan-a');
        $plan2   = $this->createPlan('plan-b');
        $this->createMemberWithUser('m1@gym.com', 'active', $plan1, 1);
        $this->createMemberWithUser('m2@gym.com', 'active', $plan2, 2);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/members?plan_id=' . $plan1);

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_non_admin_returns_403(): void
    {
        $memberUser = UserModel::query()->create([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'member@gym.com',
            UserTable::PASSWORD             => password_hash('P', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        $response = $this->actingAs($memberUser, 'sanctum')->getJson('/api/admin/members');
        $response->assertStatus(403);
    }
}
