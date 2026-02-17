<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'alias')) {
                $table->string('alias', 30)->nullable()->after('slug');
                $table->unique('alias');
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
                $table->unsignedInteger('planned_courses_year_1')->nullable()->after('expected_students_6m');
            }

            if (! Schema::hasColumn('tenants', 'owner_name')) {
                $table->string('owner_name')->nullable()->after('planned_courses_year_1');
            }

            if (! Schema::hasColumn('tenants', 'owner_email')) {
                $table->string('owner_email')->nullable()->after('owner_name');
                $table->index('owner_email');
            }

            if (! Schema::hasColumn('tenants', 'owner_password')) {
                $table->string('owner_password')->nullable()->after('owner_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $columns = [
                'owner_password',
                'owner_email',
                'owner_name',
                'planned_courses_year_1',
                'expected_students_6m',
                'team_size_range',
                'years_experience',
                'course_area',
                'alias',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    if ($column === 'owner_email') {
                        $table->dropIndex(['owner_email']);
                    }

                    if ($column === 'alias') {
                        $table->dropUnique(['alias']);
                    }

                    $table->dropColumn($column);
                }
            }
        });
    }
};
