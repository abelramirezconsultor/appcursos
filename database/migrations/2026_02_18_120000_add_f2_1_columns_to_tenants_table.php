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
            if (! Schema::hasColumn('tenants', 'status')) {
                $table->string('status', 20)->default('active')->after('owner_password');
                $table->index('status');
            }

            if (! Schema::hasColumn('tenants', 'plan_code')) {
                $table->string('plan_code', 20)->default('starter')->after('status');
            }

            if (! Schema::hasColumn('tenants', 'max_users')) {
                $table->unsignedInteger('max_users')->default(100)->after('plan_code');
            }

            if (! Schema::hasColumn('tenants', 'max_courses')) {
                $table->unsignedInteger('max_courses')->default(20)->after('max_users');
            }

            if (! Schema::hasColumn('tenants', 'max_storage_mb')) {
                $table->unsignedInteger('max_storage_mb')->default(1024)->after('max_courses');
            }

            if (! Schema::hasColumn('tenants', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('max_storage_mb');
            }

            if (! Schema::hasColumn('tenants', 'primary_color')) {
                $table->string('primary_color', 20)->nullable()->after('logo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }

            foreach (['plan_code', 'max_users', 'max_courses', 'max_storage_mb', 'logo_path', 'primary_color'] as $column) {
                if (Schema::hasColumn('tenants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
