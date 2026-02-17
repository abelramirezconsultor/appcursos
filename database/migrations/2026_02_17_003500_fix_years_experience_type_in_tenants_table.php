<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tenants', 'years_experience')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `tenants` MODIFY `years_experience` VARCHAR(10) NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tenants', 'years_experience')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `tenants` MODIFY `years_experience` TINYINT UNSIGNED NULL');
        }
    }
};
