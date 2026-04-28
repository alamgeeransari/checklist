<?php

namespace App\Events;

use App\Models\TaskStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StepAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TaskStep $step,
        public bool $isReassignment = false
    )
    {
    }
}
