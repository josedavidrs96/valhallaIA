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

final class UpdateMemberTest extends TestCase
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

    private function createMember(int $number = 1): array
    {
        $planId   = (string) new Ulid();
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                => $planId,
            MembershipPlanTable::NAME              => 'Plan 4-5 Dias',
            MembershipPlanTable::SLUG              => 'plan-4-5-' . uniqid(),
            MembershipPlanTable::PRICE_CENTS       => 4000,
            MembershipPlanTable::CLASSES_PER_MONTH => 25,
            MembershipPlanTable::IS_ACTIVE         => 1,
        ]);

        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => 'carlos@gym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        DB::table(MemberTable::TABLE_NAME)->insert([
            MemberTable::ID            => $memberId,
            MemberTable::USER_ID       => $userId,
            MemberTable::MEMBER_NUMBER => $number,
            MemberTable::FIRST_NAME    => 'Carlos',
            MemberTable::LAST_NAME     => 'Ruiz',
            MemberTable::JOIN_DATE     => '2026-06-01',
        ]);

        DB::table(MemberPlanAssignmentTable::TABLE_NAME)->insert([
            MemberPlanAssignmentTable::ID                 => (string) new Ulid(),
            MemberPlanAssignmentTable::MEMBER_ID          => $memberId,
            MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID => $planId,
            MemberPlanAssignmentTable::ASSIGNED_AT        => '2026-06-01',
        ]);

        return ['memberId' => $memberId, 'planId' => $planId, 'userId' => $userId];
    }

    public function test_admin_can_update_member_profile(): void
    {
        $admin  = $this->createAdmin();
        $member = $this->createMember();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$member['memberId']}", [
                'first_name'              => 'Carlos Nuevo',
                'last_name'               => 'Ruiz Actualizado',
                'phone'                   => '+34 600 111 222',
                'emergency_contact_name'  => 'Pedro',
                'emergency_contact_phone' => '+34 600 333 444',
                'notes'                   => 'Hipertension leve',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('first_name', 'Carlos Nuevo')
            ->assertJsonPath('last_name', 'Ruiz Actualizado')
            ->assertJsonPath('phone', '+34 600 111 222');
    }

    public function test_email_not_changed_even_if_sent_in_body(): void
    {
        $admin  = $this->createAdmin();
        $member = $this->createMember();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$member['memberId']}", [
                'first_name' => 'A',
                'last_name'  => 'B',
                'email'      => 'hacker@evil.com', // Should be ignored
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('email', 'carlos@gym.com'); // Email unchanged
    }

    public function test_update_nonexistent_member_returns_404(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson('/api/admin/members/' . (string) new Ulid(), [
                'first_name' => 'A',
                'last_name'  => 'B',
            ]);

        $response->assertStatus(404)
            ->assertJson(['code' => 'MEMBER_NOT_FOUND']);
    }

    public function test_admin_can_assign_new_plan(): void
    {
        $admin  = $this->createAdmin();
        $member = $this->createMember();

        // Create a second plan to assign
        $newPlanId = (string) new Ulid();
        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                => $newPlanId,
            MembershipPlanTable::NAME              => 'Plan 2 Dias',
            MembershipPlanTable::SLUG              => 'plan-2-dias-new',
            MembershipPlanTable::PRICE_CENTS       => 3500,
            MembershipPlanTable::CLASSES_PER_MONTH => 8,
            MembershipPlanTable::IS_ACTIVE         => 1,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$member['memberId']}/plan", [
                'membership_plan_id' => $newPlanId,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('plan.id', $newPlanId)
            ->assertJsonPath('plan.name', 'Plan 2 Dias');
    }

    public function test_assign_nonexistent_plan_returns_422(): void
    {
        $admin  = $this->createAdmin();
        $member = $this->createMember();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/members/{$member['memberId']}/plan", [
                'membership_plan_id' => (string) new Ulid(),
            ]);

        $response->assertStatus(422)
            ->assertJson(['code' => 'MEMBERSHIP_PLAN_NOT_FOUND']);
    }
}
