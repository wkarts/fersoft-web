<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemSetting extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];
}
