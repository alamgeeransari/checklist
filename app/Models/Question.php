<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_set_id',
        'text',
        'answer_type',
        'remarks_required_on_no',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'remarks_required_on_no' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(QuestionSet::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TaskChecklistAnswer::class);
    }
}
