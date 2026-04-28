<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskStepAssignment extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'task_step_id',
        'from_user_id',
        'to_user_id',
        'assigned_by',
        'reason',
        'is_reassignment',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_reassignment' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function taskStep(): BelongsTo
    {
        return $this->belongsTo(TaskStep::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
