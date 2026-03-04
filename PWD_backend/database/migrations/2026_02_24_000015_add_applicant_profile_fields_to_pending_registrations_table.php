<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            if (!Schema::hasColumn('pending_registrations', 'username')) {
                $table->string('username')->nullable()->after('name');
            }
            if (!Schema::hasColumn('pending_registrations', 'photo_url')) {
                $table->text('photo_url')->nullable()->after('disability');
            }
            if (!Schema::hasColumn('pending_registrations', 'photo_path')) {
                $table->text('photo_path')->nullable()->after('photo_url');
            }
            if (!Schema::hasColumn('pending_registrations', 'onboarding_data')) {
                $table->json('onboarding_data')->nullable()->after('department');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('pending_registrations', 'onboarding_data')) {
                $table->dropColumn('onboarding_data');
            }
            if (Schema::hasColumn('pending_registrations', 'photo_path')) {
                $table->dropColumn('photo_path');
            }
            if (Schema::hasColumn('pending_registrations', 'photo_url')) {
                $table->dropColumn('photo_url');
            }
            if (Schema::hasColumn('pending_registrations', 'username')) {
                $table->dropColumn('username');
            }
        });
    }
};

