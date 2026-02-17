<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'alias')) {
                $table->string('alias', 30)->nullable()->unique()->after('slug');
            }

            if (! Schema::hasColumn('tenants', 'course_area')) {
                $table->string('course_area', 100)->nullable()->after('alias');
            }

            if (! Schema::hasColumn('tenants', 'years_experience')) {
                $table->string('years_experience', 10)->nullable()->after('course_area');
            }

            if (! Schema::hasColumn('tenants', 'team_size_range')) {
                $table->string('team_size_range', 30)->nullable()->after('years_experience');
            }

            if (! Schema::hasColumn('tenants', 'expected_students_6m')) {
                $table->unsignedInteger('expected_students_6m')->nullable()->after('team_size_range');
            }

            if (! Schema::hasColumn('tenants', 'planned_courses_year_1')) {
                $table->unsignedSmallInteger('planned_courses_year_1')->nullable()->after('expected_students_6m');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $columns = [
                'alias',
                'course_area',
                'years_experience',
                'team_size_range',
                'expected_students_6m',
                'planned_courses_year_1',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
