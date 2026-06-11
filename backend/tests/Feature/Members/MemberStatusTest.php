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

final class MemberStatusTest extends TestCase
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

    private function createMember(string $status): string
    {
        $planId   = (string) new Ulid();
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                => $planId,
            MembershipPlanTable::NAME              => 'Plan Test',
            MembershipPlanTable::SLUG              => 'plan-test-' . uniqid(),
            MembershipPlanTable::PRICE_CENTS       => 4000,
            MembershipPlanTable::CLASSES_PER_MONTH => 25,
            MembershipPlanTable::IS_ACTIVE         => 1,
        ]);

        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => uniqid() . '@gym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => $status,
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        DB::table(MemberTable::TABLE_NAME)->insert([
            MemberTable::ID            => $memberId,
            MemberTable::USER_ID       => $userId,
            MemberTable::MEMBER_NUMBER => rand(100, 9999),
            MemberTable::FIRST_NAME    => 'Test',
            MemberTable::LAST_NAME     => 'Member',
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

    public function test_activate_pending_member(): void
    {
        $admin    = $this->createAdmin();
        $memberId = $this->createMember('pending_approval');

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$memberId}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'active');
    }

    public function test_activate_already_active_returns_422(): void
    {
        $admin    = $this->createAdmin();
        $memberId = $this->createMember('active');

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$memberId}/activate");

        $response->assertStatus(422)
            ->assertJson(['code' => 'INVALID_STATUS_TRANSITION']);
    }

    public function test_deactivate_active_member(): void
    {
        $admin    = $this->createAdmin();
        $memberId = $this->createMember('active');

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$memberId}/deactivate");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'inactive');
    }

    public function test_deactivate_inactive_member_returns_422(): void
    {
        $admin    = $this->createAdmin();
        $memberId = $this->createMember('inactive');

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$memberId}/deactivate");

        $response->assertStatus(422)
            ->assertJson(['code' => 'INVALID_STATUS_TRANSITION']);
    }

    public function test_activate_nonexistent_member_returns_404(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/members/' . (string) new Ulid() . '/activate');

        $response->assertStatus(404)
            ->assertJson(['code' => 'MEMBER_NOT_FOUND']);
    }

    public function test_deactivate_revokes_member_tokens(): void
    {
        $admin    = $this->createAdmin();
        $memberId = $this->createMember('active');

        // Get the member's user_id to create a token
        $memberRow = DB::table(MemberTable::TABLE_NAME)->where('id', $memberId)->first();
        $userModel = UserModel::find($memberRow->user_id);
        $userModel->createToken('test-token');

        $this->assertSame(1, $userModel->tokens()->count());

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$memberId}/deactivate")
            ->assertStatus(200);

        $this->assertSame(0, $userModel->fresh()->tokens()->count());
    }
}
