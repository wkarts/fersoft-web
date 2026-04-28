<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUpdateLog extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'system_update_id',
        'level',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function systemUpdate(): BelongsTo
    {
        return $this->belongsTo(SystemUpdate::class, 'system_update_id');
    }
}
