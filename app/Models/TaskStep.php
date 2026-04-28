<?php

namespace App\Models;

use App\Enums\TaskStepStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_cycle_id',
        'workflow_step_id',
        'team_id',
        'step_order',
        'status',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'completed_by',
        'completed_at',
        'manager_override',
        'manager_comment',
    ];

    protected $casts = [
        'status' => TaskStepStatus::class,
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'manager_override' => 'boolean',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(TaskCycle::class, 'task_cycle_id');
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TaskChecklistAnswer::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskStepAssignment::class);
    }
}
