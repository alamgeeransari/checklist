<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklistAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_step_id',
        'question_id',
        'answer',
        'remark',
        'submitted_by',
    ];

    protected $casts = [
        'answer' => 'boolean',
    ];

    public function taskStep(): BelongsTo
    {
        return $this->belongsTo(TaskStep::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
