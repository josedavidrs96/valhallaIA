<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('member_id', 26);
            $table->char('membership_plan_id', 26);
            $table->char('recorded_by', 26);
            $table->integer('amount_cents');
            $table->date('payment_date');
            $table->char('billing_month', 7); // YYYY-MM
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'billing_month'], 'uq_member_billing');
            $table->index('billing_month', 'idx_billing_month');
            $table->index('member_id', 'idx_p_member_id');
            $table->index('payment_date', 'idx_payment_date');

            $table->foreign('member_id', 'fk_p_member')->references('id')->on('members');
            $table->foreign('membership_plan_id', 'fk_p_plan')->references('id')->on('membership_plans');
            $table->foreign('recorded_by', 'fk_p_admin')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
