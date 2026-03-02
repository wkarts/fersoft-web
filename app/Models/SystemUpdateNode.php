<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemUpdateNode extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_update_id',
        'hostname',
        'status',
        'last_error',
        'started_at',
        'finished_at',
    ];

    protected $dates = [
        'started_at',
        'finished_at',
    ];

    public function systemUpdate(): BelongsTo
    {
        return $this->belongsTo(SystemUpdate::class, 'system_update_id');
    }
}
