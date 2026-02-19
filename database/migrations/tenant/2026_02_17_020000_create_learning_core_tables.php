<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('course_modules')->cascadeOnDelete();
            $table->string('title');
            $table->string('video_url')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->unsignedInteger('xp_reward')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->timestamps();
        });

        Schema::create('activation_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 32)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('max_uses')->default(1);
            $table->unsignedSmallInteger('uses_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('activation_code_id')->nullable()->constrained('activation_codes')->nullOnDelete();
            $table->enum('status', ['active', 'revoked', 'completed'])->default('active');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('activation_code_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activation_code_id')->constrained('activation_codes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('redeemed_at');
            $table->timestamps();
            $table->unique(['activation_code_id', 'user_id']);
        });

        Schema::create('course_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('course_enrollments')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('course_lessons')->nullOnDelete();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_progress');
        Schema::dropIfExists('activation_code_redemptions');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('activation_codes');
        Schema::dropIfExists('course_lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
    }
};
