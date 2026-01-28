<?php

namespace App\Models\Updates;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdateLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'update_version_id',
        'action',
        'status',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(UpdateVersion::class, 'update_version_id');
    }
}
