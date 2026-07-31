<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('email');
            $table->string('otp_hash');
            $table->dateTime('expires_at');
            $table->string('status')->default('pending'); // pending, verified, expired
            $table->integer('attempts')->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status')->default('Active')->after('status');
            }
            if (!Schema::hasColumn('users', 'is_email_verified')) {
                $table->boolean('is_email_verified')->default(true)->after('email_verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_otps');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_status', 'is_email_verified']);
        });
    }
};
