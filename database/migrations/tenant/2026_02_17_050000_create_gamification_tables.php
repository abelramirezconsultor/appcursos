<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_gamification_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('xp_total')->default(0);
            $table->unsignedSmallInteger('level')->default(1);
            $table->unsignedSmallInteger('streak_days')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->unsignedInteger('lessons_completed')->default(0);
            $table->timestamps();

            $table->unique('user_id');
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description');
            $table->unsignedInteger('xp_required')->default(0);
            $table->unsignedSmallInteger('streak_required')->default(0);
            $table->unsignedInteger('lessons_required')->default(0);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->unique(['user_id', 'badge_id']);
        });

        DB::table('badges')->insert([
            [
                'slug' => 'first_lesson',
                'name' => 'Primer paso',
                'description' => 'Completaste tu primera lección.',
                'xp_required' => 0,
                'streak_required' => 0,
                'lessons_required' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'focus_3',
                'name' => 'Enfocado 3 días',
                'description' => 'Mantén una racha de 3 días.',
                'xp_required' => 0,
                'streak_required' => 3,
                'lessons_required' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'xp_100',
                'name' => '100 XP',
                'description' => 'Acumula 100 XP.',
                'xp_required' => 100,
                'streak_required' => 0,
                'lessons_required' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'xp_300',
                'name' => '300 XP',
                'description' => 'Acumula 300 XP.',
                'xp_required' => 300,
                'streak_required' => 0,
                'lessons_required' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'lessons_10',
                'name' => '10 lecciones',
                'description' => 'Completa 10 lecciones.',
                'xp_required' => 0,
                'streak_required' => 0,
                'lessons_required' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('user_gamification_stats');
    }
};
