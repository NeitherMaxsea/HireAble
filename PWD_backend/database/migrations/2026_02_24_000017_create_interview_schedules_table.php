<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('application_id')->nullable();
            $table->string('job_title')->nullable();
            $table->string('company_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('created_by_uid')->nullable();
            $table->string('applicant_id')->nullable();
            $table->string('applicant_name')->nullable();
            $table->string('applicant_email')->nullable();
            $table->string('interview_type', 50)->default('initial');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('mode', 50)->nullable();
            $table->text('location_or_link')->nullable();
            $table->string('interviewer')->nullable();
            $table->text('notes')->nullable();
            $table->string('schedule_status', 50)->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_schedules');
    }
};

