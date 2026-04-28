<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskCycle extends Model
{
    protected $fillable = [
        'task_id',
        'cycle_no',
        'started_by',
        'restart_reason',
        'started_at',
        'ended_at',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(TaskStep::class, 'task_cycle_id');
    }
}
