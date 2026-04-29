<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('mail_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('task_created_enabled')->default(true);
            $table->boolean('step_assigned_enabled')->default(true);
            $table->boolean('step_completed_enabled')->default(true);
            $table->boolean('task_restarted_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
