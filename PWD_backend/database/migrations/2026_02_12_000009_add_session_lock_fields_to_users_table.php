<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('active_session_key', 191)->nullable()->after('password_reset_requested_at');
            $table->timestamp('session_last_seen_at')->nullable()->after('active_session_key');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'active_session_key',
                'session_last_seen_at',
            ]);
        });
    }
};
