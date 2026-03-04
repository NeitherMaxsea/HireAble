<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->string('applicant_response', 50)->nullable()->after('schedule_status');
            $table->text('applicant_response_note')->nullable()->after('applicant_response');
            $table->timestamp('applicant_response_at')->nullable()->after('applicant_response_note');
        });
    }

    public function down(): void
    {
        Schema::table('interview_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_response',
                'applicant_response_note',
                'applicant_response_at',
            ]);
        });
    }
};

