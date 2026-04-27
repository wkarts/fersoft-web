<?php

namespace App\Models\Updates;

use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UpdateVersion extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'version',
        'status',
        'mode',
        'provider',
        'metadata',
        'composer_action',
        'is_downgrade',
        'observations',
        'released_at',
        'applied_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_downgrade' => 'boolean',
        'released_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(UpdateLog::class);
    }

    public function migrations(): HasMany
    {
        return $this->hasMany(UpdateVersionMigration::class);
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(UpdateLog::class)->latestOfMany();
    }
}
