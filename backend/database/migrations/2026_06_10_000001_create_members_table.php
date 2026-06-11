<?php

use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use App\Src\Shared\Auth\Infrastructure\Tables\UserTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(MemberTable::TABLE_NAME, function (Blueprint $table) {
            $table->char(MemberTable::ID, 26)->primary();
            $table->char(MemberTable::USER_ID, 26)->unique();
            $table->unsignedInteger(MemberTable::MEMBER_NUMBER)->unique();
            $table->string(MemberTable::FIRST_NAME, 100);
            $table->string(MemberTable::LAST_NAME, 100);
            $table->string(MemberTable::PHONE, 20)->nullable();
            $table->date(MemberTable::DATE_OF_BIRTH)->nullable();
            $table->string(MemberTable::PROFILE_PHOTO)->nullable();
            $table->date(MemberTable::JOIN_DATE);
            $table->string(MemberTable::EMERGENCY_CONTACT_NAME, 100)->nullable();
            $table->string(MemberTable::EMERGENCY_CONTACT_PHONE, 20)->nullable();
            $table->text(MemberTable::NOTES)->nullable();
            $table->timestamps();

            $table->foreign(MemberTable::USER_ID)
                ->references(UserTable::ID)
                ->on(UserTable::TABLE_NAME)
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(MemberTable::TABLE_NAME);
    }
};
