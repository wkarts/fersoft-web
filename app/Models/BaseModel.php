<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FilialInjectable;

class BaseModel extends Model
{
    use FilialInjectable;
}
