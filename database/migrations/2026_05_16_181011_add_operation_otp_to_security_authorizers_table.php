<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_authorizers', function (Blueprint $table) {
            if (!Schema::hasColumn('security_authorizers', 'operation_otp_secret')) {
                $table->text('operation_otp_secret')->nullable()->after('can_authorize_print');
            }

            if (!Schema::hasColumn('security_authorizers', 'operation_otp_enabled')) {
                $table->boolean('operation_otp_enabled')->default(false)->after('operation_otp_secret');
            }

            if (!Schema::hasColumn('security_authorizers', 'operation_otp_confirmed_at')) {
                $table->timestamp('operation_otp_confirmed_at')->nullable()->after('operation_otp_enabled');
            }

            if (!Schema::hasColumn('security_authorizers', 'operation_otp_last_used_at')) {
                $table->timestamp('operation_otp_last_used_at')->nullable()->after('operation_otp_confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('security_authorizers', function (Blueprint $table) {
            if (Schema::hasColumn('security_authorizers', 'operation_otp_last_used_at')) {
                $table->dropColumn('operation_otp_last_used_at');
            }

            if (Schema::hasColumn('security_authorizers', 'operation_otp_confirmed_at')) {
                $table->dropColumn('operation_otp_confirmed_at');
            }

            if (Schema::hasColumn('security_authorizers', 'operation_otp_enabled')) {
                $table->dropColumn('operation_otp_enabled');
            }

            if (Schema::hasColumn('security_authorizers', 'operation_otp_secret')) {
                $table->dropColumn('operation_otp_secret');
            }
        });
    }
};
