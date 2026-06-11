<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Src\Billing\Payment\Infrastructure\Tables\PaymentTable;
use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class OverdueMembersTest extends TestCase
{
    use RefreshDatabase;

    private static int $memberCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        self::$memberCounter = 0;
    }

    private function createAdmin(): UserModel
    {
        $id = (string) new Ulid();
        UserModel::query()->create([
            UserTable::ID                   => $id,
            UserTable::EMAIL                => 'admin@valhallagym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'admin',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);
        return UserModel::find($id);
    }

    private function createMemberPlan(): string
    {
        $id = (string) new Ulid();
        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID                => $id,
            MembershipPlanTable::NAME              => 'Plan 3 Dias',
            MembershipPlanTable::SLUG              => 'plan-3-dias',
            MembershipPlanTable::PRICE_CENTS       => 4500,
            MembershipPlanTable::CLASSES_PER_MONTH => 12,
            MembershipPlanTable::IS_ACTIVE         => 1,
        ]);
        return $id;
    }

    private function createActiveMember(string $email): array
    {
        self::$memberCounter++;
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => $email,
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        DB::table(MemberTable::TABLE_NAME)->insert([
            MemberTable::ID            => $memberId,
            MemberTable::USER_ID       => $userId,
            MemberTable::MEMBER_NUMBER => self::$memberCounter,
            MemberTable::FIRST_NAME    => 'Juan',
            MemberTable::LAST_NAME     => 'Garcia',
            MemberTable::JOIN_DATE     => '2026-01-01',
        ]);

        return ['userId' => $userId, 'memberId' => $memberId, 'email' => $email];
    }

    private function createInactiveMember(string $email): array
    {
        self::$memberCounter++;
        $userId   = (string) new Ulid();
        $memberId = (string) new Ulid();

        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => $email,
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'inactive',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);

        DB::table(MemberTable::TABLE_NAME)->insert([
            MemberTable::ID            => $memberId,
            MemberTable::USER_ID       => $userId,
            MemberTable::MEMBER_NUMBER => self::$memberCounter,
            MemberTable::FIRST_NAME    => 'Pedro',
            MemberTable::LAST_NAME     => 'Diaz',
            MemberTable::JOIN_DATE     => '2026-01-01',
        ]);

        return ['userId' => $userId, 'memberId' => $memberId, 'email' => $email];
    }

    private function insertPayment(string $memberId, string $planId, string $adminId, string $billingMonth): void
    {
        DB::table(PaymentTable::TABLE_NAME)->insert([
            PaymentTable::ID                 => (string) new Ulid(),
            PaymentTable::MEMBER_ID          => $memberId,
            PaymentTable::MEMBERSHIP_PLAN_ID => $planId,
            PaymentTable::RECORDED_BY        => $adminId,
            PaymentTable::AMOUNT_CENTS       => 4500,
            PaymentTable::PAYMENT_DATE       => $billingMonth . '-01',
            PaymentTable::BILLING_MONTH      => $billingMonth,
        ]);
    }

    public function test_member_without_payment_this_month_appears_in_overdue(): void
    {
        $admin      = $this->createAdmin();
        $memberData = $this->createActiveMember('member1@gym.com');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/payments/overdue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['member_id', 'member_number', 'first_name', 'last_name', 'email', 'plan_name', 'last_payment_date']],
                'meta' => ['total'],
            ]);

        $ids = collect($response->json('data'))->pluck('member_id')->all();
        $this->assertContains($memberData['memberId'], $ids);
    }

    public function test_member_with_payment_this_month_not_in_overdue(): void
    {
        $admin        = $this->createAdmin();
        $memberData   = $this->createActiveMember('member2@gym.com');
        $planId       = $this->createMemberPlan();
        $currentMonth = (new \DateTimeImmutable())->format('Y-m');

        $this->insertPayment($memberData['memberId'], $planId, $admin->id, $currentMonth);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/payments/overdue');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('member_id')->all();
        $this->assertNotContains($memberData['memberId'], $ids);
    }

    public function test_inactive_member_not_in_overdue(): void
    {
        $admin      = $this->createAdmin();
        $memberData = $this->createInactiveMember('inactive@gym.com');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/payments/overdue');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('member_id')->all();
        $this->assertNotContains($memberData['memberId'], $ids);
    }

    public function test_last_payment_date_is_returned(): void
    {
        $admin      = $this->createAdmin();
        $memberData = $this->createActiveMember('member3@gym.com');
        $planId     = $this->createMemberPlan();

        // Insert a past payment (previous month)
        $this->insertPayment($memberData['memberId'], $planId, $admin->id, '2026-05');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/payments/overdue');

        $response->assertStatus(200);

        $found = collect($response->json('data'))->firstWhere('member_id', $memberData['memberId']);
        $this->assertNotNull($found);
        $this->assertNotNull($found['last_payment_date']);
    }

    public function test_member_role_returns_403(): void
    {
        self::$memberCounter++;
        $userId = (string) new Ulid();
        UserModel::query()->create([
            UserTable::ID                   => $userId,
            UserTable::EMAIL                => 'member@gym.com',
            UserTable::PASSWORD             => password_hash('Password123', PASSWORD_BCRYPT),
            UserTable::ROLE                 => 'member',
            UserTable::STATUS               => 'active',
            UserTable::MUST_CHANGE_PASSWORD => 0,
        ]);
        $user = UserModel::find($userId);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/payments/overdue')
            ->assertStatus(403);
    }
}
