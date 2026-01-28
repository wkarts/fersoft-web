<?php

namespace App\Models\Updates;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdateVersionMigration extends Model
{
    use HasFactory;

    protected $fillable = [
        'update_version_id',
        'migration',
        'direction',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(UpdateVersion::class);
    }
}
