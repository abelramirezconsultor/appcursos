<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_modules', function (Blueprint $table): void {
            $table->foreignId('prerequisite_module_id')
                ->nullable()
                ->after('position')
                ->constrained('course_modules')
                ->nullOnDelete();

            $table->boolean('is_prerequisite_mandatory')
                ->default(false)
                ->after('prerequisite_module_id');
        });
    }

    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('prerequisite_module_id');
            $table->dropColumn('is_prerequisite_mandatory');
        });
    }
};
