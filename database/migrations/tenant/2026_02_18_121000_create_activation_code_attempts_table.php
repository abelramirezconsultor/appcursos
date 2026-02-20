<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activation_code_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activation_code_id')->nullable()->constrained('activation_codes')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code_input', 32)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('status', 20);
            $table->string('reason', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->index(['status', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['code_input', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_code_attempts');
    }
};
