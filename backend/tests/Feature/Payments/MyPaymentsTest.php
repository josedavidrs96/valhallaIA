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

final class MyPaymentsTest extends TestCase
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

    private function createMemberUser(string $email = 'member@gym.com'): array
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
            MemberTable::FIRST_NAME    => 'Carlos',
            MemberTable::LAST_NAME     => 'Ruiz',
            MemberTable::JOIN_DATE     => '2026-06-10',
        ]);

        return ['user' => UserModel::find($userId), 'memberId' => $memberId];
    }

    private function insertPayment(string $memberId, string $planId, string $adminId, string $billingMonth): string
    {
        $id = (string) new Ulid();
        DB::table(PaymentTable::TABLE_NAME)->insert([
            PaymentTable::ID                 => $id,
            PaymentTable::MEMBER_ID          => $memberId,
            PaymentTable::MEMBERSHIP_PLAN_ID => $planId,
            PaymentTable::RECORDED_BY        => $adminId,
            PaymentTable::AMOUNT_CENTS       => 4500,
            PaymentTable::PAYMENT_DATE       => $billingMonth . '-01',
            PaymentTable::BILLING_MONTH      => $billingMonth,
        ]);
        return $id;
    }

    public function test_member_can_see_own_payments(): void
    {
        $admin      = $this->createAdmin();
        $memberData = $this->createMemberUser();
        $planId     = $this->createMemberPlan();

        $this->insertPayment($memberData['memberId'], $planId, $admin->id, '2026-06');
        $this->insertPayment($memberData['memberId'], $planId, $admin->id, '2026-05');

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->getJson('/api/member/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'amount_cents', 'payment_date', 'billing_month', 'plan_name']],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_admin_role_returns_403_on_member_payments_endpoint(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/member/payments')
            ->assertStatus(403);
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/member/payments')
            ->assertStatus(401);
    }
}
