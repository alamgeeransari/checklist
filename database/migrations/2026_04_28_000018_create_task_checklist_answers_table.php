<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_checklist_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_step_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->boolean('answer');
            $table->text('remark')->nullable();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['task_step_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_checklist_answers');
    }
};
