<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'mail_enabled',
        'push_enabled',
        'task_created_enabled',
        'step_assigned_enabled',
        'step_completed_enabled',
        'task_restarted_enabled',
    ];

    protected $casts = [
        'mail_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'task_created_enabled' => 'boolean',
        'step_assigned_enabled' => 'boolean',
        'step_completed_enabled' => 'boolean',
        'task_restarted_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
