<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CarrosselDelivery extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'path', 'status', 'valor_ordem'
    ];

    protected $appends = [
        'img'
    ];

    public function getImgAttribute()
    {
        return env("PATH_URL") . "/carrossel_delivery/" . $this->path;
    }
}
