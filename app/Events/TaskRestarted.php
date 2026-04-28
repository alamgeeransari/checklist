<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskRestarted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Task $task,
        public string $reason,
        public User $actor
    ) {
    }
}
