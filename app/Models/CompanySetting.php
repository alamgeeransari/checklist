<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_id',
        'ui_theme',
        'mail_notifications_enabled',
        'theme_config',
    ];

    protected $casts = [
        'mail_notifications_enabled' => 'boolean',
        'theme_config' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
