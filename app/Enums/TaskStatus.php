<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case ChangesRequested = 'changes_requested';
    case Rejected = 'rejected';
    case ReadyForRelease = 'ready_for_release';
    case Released = 'released';
    case Cancelled = 'cancelled';
}
