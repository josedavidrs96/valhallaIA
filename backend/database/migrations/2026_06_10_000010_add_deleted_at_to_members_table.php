<?php

use App\Src\Core\Member\Infrastructure\Tables\MemberTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(MemberTable::TABLE_NAME, function (Blueprint $table) {
            $table->softDeletes()->after(MemberTable::UPDATED_AT);
            $table->index(MemberTable::DELETED_AT, 'idx_members_deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table(MemberTable::TABLE_NAME, function (Blueprint $table) {
            $table->dropIndex('idx_members_deleted_at');
            $table->dropSoftDeletes();
        });
    }
};
