<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUpdateFile extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'system_update_id',
        'path',
        'hash_before',
        'hash_after',
        'backup_path',
        'reference',
    ];

    public function systemUpdate(): BelongsTo
    {
        return $this->belongsTo(SystemUpdate::class, 'system_update_id');
    }
}
