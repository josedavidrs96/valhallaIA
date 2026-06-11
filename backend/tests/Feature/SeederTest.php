<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Src\Core\ClassType\Infrastructure\Tables\ClassTypeTable;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_membership_plans_seeded(): void
    {
        $plans = DB::table(MembershipPlanTable::TABLE_NAME)->get();

        $this->assertCount(3, $plans);

        $slugs = $plans->pluck('slug')->all();
        $this->assertContains('plan-2-dias', $slugs);
        $this->assertContains('plan-3-dias', $slugs);
        $this->assertContains('plan-4-5-dias', $slugs);

        $plan2 = $plans->firstWhere('slug', 'plan-2-dias');
        $this->assertSame(3500, (int) $plan2->price_cents);
    }

    public function test_class_types_seeded(): void
    {
        $types = DB::table(ClassTypeTable::TABLE_NAME)->get();

        $this->assertCount(5, $types);

        $slugs = $types->pluck('slug')->all();
        $this->assertContains('tren-superior', $slugs);
        $this->assertContains('tren-inferior', $slugs);
        $this->assertContains('full-body', $slugs);
        $this->assertContains('gap', $slugs);
        $this->assertContains('entrenamiento-libre', $slugs);
    }

    public function test_default_admin_seeded_with_must_change_password(): void
    {
        $admin = DB::table(UserTable::TABLE_NAME)
            ->where(UserTable::EMAIL, 'admin@valhallagym.com')
            ->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertSame(1, (int) $admin->must_change_password);
    }

    public function test_seeders_are_idempotent(): void
    {
        $this->seed();

        $this->assertCount(3, DB::table(MembershipPlanTable::TABLE_NAME)->get());
        $this->assertCount(5, DB::table(ClassTypeTable::TABLE_NAME)->get());
        $this->assertCount(1, DB::table(UserTable::TABLE_NAME)->get());
    }
}
