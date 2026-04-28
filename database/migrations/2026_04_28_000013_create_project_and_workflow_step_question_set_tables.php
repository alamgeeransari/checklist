<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_question_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_set_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['project_id', 'question_set_id']);
        });

        Schema::create('workflow_step_question_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_set_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['workflow_step_id', 'question_set_id'], 'workflow_step_question_set_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_question_sets');
        Schema::dropIfExists('project_question_sets');
    }
};
