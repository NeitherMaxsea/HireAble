<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->after('name');
            $table->string('role', 50)->nullable()->after('password');
            $table->string('status', 50)->nullable()->after('role');
            $table->boolean('is_active')->default(false)->after('status');
            $table->string('contact', 100)->nullable()->after('is_active');
            $table->string('disability')->nullable()->after('contact');
            $table->string('photo_url')->nullable()->after('disability');
            $table->string('photo_path')->nullable()->after('photo_url');
            $table->string('company_id')->nullable()->after('photo_path');
            $table->string('company_name')->nullable()->after('company_id');
            $table->string('position')->nullable()->after('company_name');
            $table->string('department')->nullable()->after('position');
            $table->timestamp('last_login_at')->nullable()->after('department');
            $table->timestamp('last_logout_at')->nullable()->after('last_login_at');
            $table->timestamp('password_reset_requested_at')->nullable()->after('last_logout_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'role',
                'status',
                'is_active',
                'contact',
                'disability',
                'photo_url',
                'photo_path',
                'company_id',
                'company_name',
                'position',
                'department',
                'last_login_at',
                'last_logout_at',
                'password_reset_requested_at',
            ]);
        });
    }
};
