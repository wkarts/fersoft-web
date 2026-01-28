<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\EncryptionHelper;

class TraccarConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'base_url',
        'socket_url',
        'mail_user_name',
        'password',
        'token_traccar',
        'default_map',
        'token_google_maps',
    ];

    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = EncryptionHelper::encrypt($value);
        }
    }

    public function getPasswordAttribute($value)
    {
        return $value ? EncryptionHelper::decrypt($value) : null;
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
