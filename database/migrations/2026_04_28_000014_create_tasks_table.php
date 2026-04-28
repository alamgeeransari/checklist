<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('workflow_id')->constrained('workflows')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('release_tag')->nullable();
            $table->enum('status', [
                'draft',
                'in_progress',
                'changes_requested',
                'rejected',
                'ready_for_release',
                'released',
                'cancelled',
            ])->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('current_cycle_no')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
