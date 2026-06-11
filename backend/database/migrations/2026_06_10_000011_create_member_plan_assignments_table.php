<?php

use App\Src\Core\Member\Infrastructure\Tables\MemberPlanAssignmentTable;
use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(MemberPlanAssignmentTable::TABLE_NAME, function (Blueprint $table) {
            $table->char(MemberPlanAssignmentTable::ID, 26)->primary();
            $table->char(MemberPlanAssignmentTable::MEMBER_ID, 26);
            $table->char(MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID, 26);
            $table->date(MemberPlanAssignmentTable::ASSIGNED_AT);
            $table->timestamps();

            $table->foreign(MemberPlanAssignmentTable::MEMBER_ID)
                ->references(MemberTable::ID)
                ->on(MemberTable::TABLE_NAME)
                ->onDelete('cascade');

            $table->foreign(MemberPlanAssignmentTable::MEMBERSHIP_PLAN_ID)
                ->references(MembershipPlanTable::ID)
                ->on(MembershipPlanTable::TABLE_NAME);

            $table->index(
                [MemberPlanAssignmentTable::MEMBER_ID, MemberPlanAssignmentTable::ASSIGNED_AT],
                'idx_mpa_member_assigned'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(MemberPlanAssignmentTable::TABLE_NAME);
    }
};
