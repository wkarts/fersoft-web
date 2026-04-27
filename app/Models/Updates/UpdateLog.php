<?php

namespace App\Models\Updates;

use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdateLog extends BaseModel
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
