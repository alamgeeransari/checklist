<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cycle_no');
            $table->foreignId('started_by')->constrained('users')->restrictOnDelete();
            $table->text('restart_reason')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'cycle_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_cycles');
    }
};
