<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

final class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                MembershipPlanTable::SLUG        => 'plan-2-dias',
                MembershipPlanTable::NAME        => 'Plan 2 Dias',
                MembershipPlanTable::DESCRIPTION => '8 clases al mes. Ideal para quienes entrenan 2 dias a la semana.',
                MembershipPlanTable::PRICE_CENTS => 3500,
                MembershipPlanTable::CLASSES_PER_MONTH => 8,
            ],
            [
                MembershipPlanTable::SLUG        => 'plan-3-dias',
                MembershipPlanTable::NAME        => 'Plan 3 Dias',
                MembershipPlanTable::DESCRIPTION => '12 clases al mes. Perfecto para mantener una rutina constante.',
                MembershipPlanTable::PRICE_CENTS => 3800,
                MembershipPlanTable::CLASSES_PER_MONTH => 12,
            ],
            [
                MembershipPlanTable::SLUG        => 'plan-4-5-dias',
                MembershipPlanTable::NAME        => 'Plan 4-5 Dias',
                MembershipPlanTable::DESCRIPTION => '20-25 clases al mes. Para los guerreros que entrenan todos los dias.',
                MembershipPlanTable::PRICE_CENTS => 4000,
                MembershipPlanTable::CLASSES_PER_MONTH => 25,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table(MembershipPlanTable::TABLE_NAME)->updateOrInsert(
                [MembershipPlanTable::SLUG => $plan[MembershipPlanTable::SLUG]],
                array_merge($plan, [
                    MembershipPlanTable::ID        => (string) new Ulid(),
                    MembershipPlanTable::IS_ACTIVE => 1,
                    MembershipPlanTable::CREATED_AT => now(),
                    MembershipPlanTable::UPDATED_AT => now(),
                ]),
            );
        }
    }
}
