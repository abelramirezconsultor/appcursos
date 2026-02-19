<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('course_lessons', 'duration_seconds')) {
                $table->unsignedSmallInteger('duration_seconds')->nullable()->after('video_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_lessons', function (Blueprint $table) {
            if (Schema::hasColumn('course_lessons', 'duration_seconds')) {
                $table->dropColumn('duration_seconds');
            }
        });
    }
};
