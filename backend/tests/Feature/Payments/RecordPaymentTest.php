<?php

declare(strict_types=1);

namespace Tests\Feature\Payments;

use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use App\Src\Shared\Auth\Infrastructure\Persistence\UserModel;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

final class RecordPaymentTest extends TestCase
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

    private function createMemberPlan(): string
    {
        $id = (string) new Ulid();
        DB::table(MembershipPlanTable::TABLE_NAME)->insert([
            MembershipPlanTable::ID               => $id,
            MembershipPlanTable::NAME             => 'Plan 3 Dias',
            MembershipPlanTable::SLUG             => 'plan-3-dias',
            MembershipPlanTable::PRICE_CENTS      => 4500,
            MembershipPlanTable::CLASSES_PER_MONTH => 12,
            MembershipPlanTable::IS_ACTIVE        => 1,
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

        return [
            'user'     => UserModel::find($userId),
            'memberId' => $memberId,
        ];
    }

    public function test_admin_can_record_payment_returns_201(): void
    {
        $admin      = $this->createAdmin();
        $memberData = $this->createMemberUser();
        $planId     = $this->createMemberPlan();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/payments', [
                'member_id'          => $memberData['memberId'],
                'membership_plan_id' => $planId,
                'amount_cents'       => 4500,
                'payment_date'       => '2026-06-01',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'member' => ['id', 'member_number', 'name'],
                'plan'   => ['id', 'name'],
                'recorded_by',
                'amount_cents',
                'payment_date',
                'billing_month',
                'notes',
                'created_at',
            ])
            ->assertJsonPath('amount_cents', 4500)
            ->assertJsonPath('billing_month', '2026-06');
    }

    public function test_duplicate_payment_same_month_returns_409(): void
    {
        $admin      = $this->createAdmin();
        $memberData = $this->createMemberUser();
        $planId     = $this->createMemberPlan();

        $payload = [
            'member_id'          => $memberData['memberId'],
            'membership_plan_id' => $planId,
            'amount_cents'       => 4500,
            'payment_date'       => '2026-06-01',
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/payments', $payload)
            ->assertStatus(201);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/payments', $payload);

        $response->assertStatus(409)
            ->assertJsonPath('code', 'PAYMENT_ALREADY_EXISTS_FOR_MONTH');
    }

    public function test_invalid_member_id_returns_404(): void
    {
        $admin  = $this->createAdmin();
        $planId = $this->createMemberPlan();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/payments', [
                'member_id'          => (string) new Ulid(),
                'membership_plan_id' => $planId,
                'amount_cents'       => 4500,
                'payment_date'       => '2026-06-01',
            ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', 'MEMBER_NOT_FOUND');
    }

    public function test_zero_amount_returns_422(): void
    {
        $admin      = $this->createAdmin();
        $memberData = $this->createMemberUser();
        $planId     = $this->createMemberPlan();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/payments', [
                'member_id'          => $memberData['memberId'],
                'membership_plan_id' => $planId,
                'amount_cents'       => 0,
                'payment_date'       => '2026-06-01',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INVALID_PAYMENT_AMOUNT');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->postJson('/api/admin/payments', [
            'member_id'          => (string) new Ulid(),
            'membership_plan_id' => (string) new Ulid(),
            'amount_cents'       => 4500,
            'payment_date'       => '2026-06-01',
        ]);

        $response->assertStatus(401);
    }

    public function test_member_role_returns_403(): void
    {
        $memberData = $this->createMemberUser();
        $planId     = $this->createMemberPlan();

        $response = $this->actingAs($memberData['user'], 'sanctum')
            ->postJson('/api/admin/payments', [
                'member_id'          => $memberData['memberId'],
                'membership_plan_id' => $planId,
                'amount_cents'       => 4500,
                'payment_date'       => '2026-06-01',
            ]);

        $response->assertStatus(403);
    }
}
