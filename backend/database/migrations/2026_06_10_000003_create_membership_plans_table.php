<?php

use App\Src\Core\Member\Infrastructure\Tables\MembershipPlanTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(MembershipPlanTable::TABLE_NAME, function (Blueprint $table) {
            $table->char(MembershipPlanTable::ID, 26)->primary();
            $table->string(MembershipPlanTable::NAME, 100);
            $table->string(MembershipPlanTable::SLUG, 100)->unique();
            $table->text(MembershipPlanTable::DESCRIPTION)->nullable();
            $table->integer(MembershipPlanTable::PRICE_CENTS)->unsigned();
            $table->integer(MembershipPlanTable::CLASSES_PER_MONTH)->unsigned()->nullable();
            $table->tinyInteger(MembershipPlanTable::IS_ACTIVE)->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(MembershipPlanTable::TABLE_NAME);
    }
};
