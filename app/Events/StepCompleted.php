<?php

namespace App\Events;

use App\Models\TaskStep;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StepCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TaskStep $taskStep,
        public User $actor
    )
    {
    }
}
