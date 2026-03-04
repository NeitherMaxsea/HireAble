<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('sex_at_birth')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('academic_level')->nullable();
            $table->string('address_province')->nullable();
            $table->string('address_city')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('pwd_category')->nullable();
            $table->string('pwd_id_number')->nullable();
            $table->string('preferred_role')->nullable();
            $table->json('preferred_skills')->nullable();
            $table->string('years_experience')->nullable();
            $table->string('preferred_province')->nullable();
            $table->string('preferred_city')->nullable();
            $table->string('work_mode')->nullable();
            $table->string('salary_min')->nullable();
            $table->string('salary_max')->nullable();
            $table->text('profile_summary')->nullable();
            $table->text('profile_picture_url')->nullable();
            $table->text('profile_picture_path')->nullable();
            $table->text('pwd_id_image_url')->nullable();
            $table->text('pwd_id_image_path')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_profiles');
    }
};
