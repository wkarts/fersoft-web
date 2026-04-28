<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class VideoAjuda extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'url_sistema', 'url_video'
    ];
}
