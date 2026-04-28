<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'project_id',
        'workflow_id',
        'title',
        'description',
        'release_tag',
        'status',
        'created_by',
        'current_cycle_no',
        'is_active',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'is_active' => 'boolean',
        'current_cycle_no' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(TaskCycle::class);
    }

    public function releaseNotes(): HasMany
    {
        return $this->hasMany(ReleaseNote::class);
    }
}
