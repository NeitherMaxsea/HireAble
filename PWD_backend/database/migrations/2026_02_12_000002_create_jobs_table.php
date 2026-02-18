<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('location')->nullable();
            $table->string('type', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('qualifications')->nullable();
            $table->string('disability_type')->nullable();
            $table->string('accommodations')->nullable();
            $table->string('salary')->nullable();
            $table->string('benefits')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_url2')->nullable();
            $table->json('images')->nullable();

            $table->string('status', 50)->default('pending_finance_review');
            $table->string('finance_approval_status', 50)->default('pending');
            $table->text('finance_approval_note')->nullable();
            $table->text('finance_rejection_reason')->nullable();
            $table->string('finance_reviewed_by_uid')->nullable();
            $table->string('finance_reviewed_by_name')->nullable();
            $table->string('finance_reviewed_by_email')->nullable();
            $table->timestamp('finance_reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->string('posted_by_name')->nullable();
            $table->string('posted_by_email')->nullable();
            $table->string('posted_by_role')->nullable();
            $table->string('posted_by_uid')->nullable();
            $table->string('company_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('created_by_company_admin_uid')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'finance_approval_status']);
            $table->index('posted_by_uid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
