<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('member_id', 26);
            $table->char('class_session_id', 26);
            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
            $table->timestamps();
            $table->unique(['member_id', 'class_session_id'], 'uq_member_session');
            $table->index(['class_session_id', 'status'], 'idx_bookings_cs_status');
            $table->index('member_id', 'idx_b_member_id');
            $table->foreign('member_id', 'fk_b_member')->references('id')->on('members')->cascadeOnDelete();
            $table->foreign('class_session_id', 'fk_b_session')->references('id')->on('class_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
