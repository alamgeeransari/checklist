<?php

namespace App\Enums;

enum TaskStepStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Skipped = 'skipped';
}
