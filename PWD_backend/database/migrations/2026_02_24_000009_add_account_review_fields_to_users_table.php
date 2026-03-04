<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'review_rejection_reason')) {
                $table->text('review_rejection_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('review_rejection_reason');
            }
            if (!Schema::hasColumn('users', 'reviewed_by_email')) {
                $table->string('reviewed_by_email')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['review_rejection_reason', 'reviewed_at', 'reviewed_by_email'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

