<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemUpdate extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'version',
        'mode',
        'target_reference',
        'status',
        'flags',
        'dry_run',
        'backup_code_path',
        'backup_db_path',
        'initiated_by',
        'notes',
        'lock_key',
        'started_at',
        'finished_at',
        'rollback_reference',
    ];

    protected $casts = [
        'flags' => 'array',
        'dry_run' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function files(): HasMany
    {
        return $this->hasMany(SystemUpdateFile::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SystemUpdateLog::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(SystemUpdateNode::class);
    }
}
