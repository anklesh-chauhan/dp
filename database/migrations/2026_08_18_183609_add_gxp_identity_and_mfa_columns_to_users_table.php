<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('identity_uuid')->nullable()->unique()->after('id');
            $table->timestamp('deactivated_at')->nullable()->after('remember_token');
            $table->timestamp('password_changed_at')->nullable()->after('deactivated_at');
            $table->boolean('must_change_password')->default(false)->after('password_changed_at');
            $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('must_change_password');
            $table->timestamp('locked_at')->nullable()->after('failed_login_attempts');
            $table->timestamp('last_login_at')->nullable()->after('locked_at');
            $table->text('app_authentication_secret')->nullable()->after('last_login_at');
            $table->json('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
        });

        foreach (DB::table('users')->whereNull('identity_uuid')->orderBy('id')->cursor() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'identity_uuid' => (string) Str::uuid(),
                'password_changed_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'identity_uuid',
                'deactivated_at',
                'password_changed_at',
                'must_change_password',
                'failed_login_attempts',
                'locked_at',
                'last_login_at',
                'app_authentication_secret',
                'app_authentication_recovery_codes',
            ]);
        });
    }
};
