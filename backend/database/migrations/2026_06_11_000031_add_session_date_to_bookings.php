<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->date('session_date')->default('2026-01-01')->after('class_session_id');
        });

        // Drop old unique constraint and add new one scoped to session_date
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('uq_member_session');
            $table->unique(['member_id', 'class_session_id', 'session_date'], 'uq_member_session_date');
            $table->index(['member_id', 'session_date', 'status'], 'idx_b_member_date');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_b_member_date');
            $table->dropUnique('uq_member_session_date');
            $table->unique(['member_id', 'class_session_id'], 'uq_member_session');
            $table->dropColumn('session_date');
        });
    }
};
