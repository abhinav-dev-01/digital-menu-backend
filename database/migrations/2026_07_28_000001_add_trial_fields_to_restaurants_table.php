<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('account_status')->default('trial')->after('status'); // trial, active, expired, suspended
            $table->string('plan_name')->default('Free Trial')->after('account_status'); // Free Trial, Starter, Business, Enterprise
            $table->string('plan_status')->default('trial')->after('plan_name'); // trial, active, inactive, expired
            $table->timestamp('trial_starts_at')->nullable()->after('plan_status');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_starts_at');
            $table->boolean('trial_reminder_sent_7')->default(false)->after('trial_ends_at');
            $table->boolean('trial_reminder_sent_3')->default(false)->after('trial_reminder_sent_7');
            $table->boolean('trial_reminder_sent_1')->default(false)->after('trial_reminder_sent_3');
            $table->boolean('trial_expired_sent')->default(false)->after('trial_reminder_sent_1');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'account_status',
                'plan_name',
                'plan_status',
                'trial_starts_at',
                'trial_ends_at',
                'trial_reminder_sent_7',
                'trial_reminder_sent_3',
                'trial_reminder_sent_1',
                'trial_expired_sent',
            ]);
        });
    }
};
