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

final class GetMemberDetailTest extends TestCase
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

    private function createMember(): array
    {
        $planId   = (string) new Ulid();
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                => $planId,
            MembershipPlanTable::NAME              => 'Plan 4-5 Dias',
            MembershipPlanTable::SLUG              => 'plan-4-5-dias',
            MembershipPlanTable::PRICE_CENTS       => 4000,
            MembershipPlanTable::CLASSES_PER_MONTH => 25,
            MembershipPlanTable::IS_ACTIVE         => 1,
        ]);

        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => 'detail@gym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
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

        return ['memberId' => $memberId, 'planId' => $planId];
    }

    public function test_admin_retrieves_member_detail(): void
    {
        $admin  = $this->createAdmin();
        $member = $this->createMember();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/members/{$member['memberId']}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id', 'user_id', 'member_number', 'first_name', 'last_name',
                'email', 'phone', 'date_of_birth', 'profile_photo',
                'join_date', 'status', 'emergency_contact_name',
                'emergency_contact_phone', 'notes', 'created_at',
                'plan' => ['id', 'name', 'price_cents', 'classes_per_month'],
            ])
            ->assertJsonPath('email', 'detail@gym.com')
            ->assertJsonPath('plan.id', $member['planId']);
    }

    public function test_nonexistent_member_returns_404(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/members/' . (string) new Ulid());

        $response->assertStatus(404)
            ->assertJson(['code' => 'MEMBER_NOT_FOUND']);
    }

    public function test_non_admin_returns_403(): void
    {
        $member = $this->createMember();

        $memberUser = UserModel::query()->create([
            UserTable::ID                   => (string) new Ulid(),
            UserTable::EMAIL                => 'other@gym.com',
            UserTable::PASSWORD             => password_hash('P', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        $response = $this->actingAs($memberUser, 'sanctum')
            ->getJson("/api/admin/members/{$member['memberId']}");

        $response->assertStatus(403);
    }
}
