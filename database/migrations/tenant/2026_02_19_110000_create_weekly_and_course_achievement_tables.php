<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_weekly_gamification_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('week_start');
            $table->unsignedInteger('xp_earned')->default(0);
            $table->unsignedInteger('lessons_completed')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'week_start']);
            $table->index('week_start');
        });

        Schema::create('user_course_achievements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->unique(['user_id', 'course_id', 'slug']);
            $table->index(['course_id', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_course_achievements');
        Schema::dropIfExists('user_weekly_gamification_stats');
    }
};
