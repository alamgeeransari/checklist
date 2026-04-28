<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_cycle_id')->constrained('task_cycles')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->constrained('workflow_steps')->restrictOnDelete();
            $table->foreignId('team_id')->constrained('teams')->restrictOnDelete();
            $table->unsignedInteger('step_order');
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed', 'rejected', 'skipped'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('manager_override')->default(false);
            $table->text('manager_comment')->nullable();
            $table->timestamps();

            $table->index(['task_cycle_id', 'step_order', 'status'], 'task_steps_cycle_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_steps');
    }
};
