<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

class ConnectApiInstance extends BaseModel
{
    protected $table = 'connect_api_instances';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'filial_id',
        'instance_name',
        'remote_instance_id',
        'instance_token',
        'connected_number',
        'connected_name',
        'connection_status',
        'is_blocked',
        'provisioned_at',
        'paired_at',
        'connected_at',
        'disconnected_at',
        'last_event_at',
        'last_status_at',
        'last_error_code',
        'last_error_message',
        'last_error_at',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['instance_token'];

    protected $casts = [
        'is_blocked' => 'boolean',
        'provisioned_at' => 'datetime',
        'paired_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_event_at' => 'datetime',
        'last_status_at' => 'datetime',
        'last_error_at' => 'datetime',
    ];

    protected function instanceToken(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) {
                    return null;
                }

                try {
                    return Crypt::decryptString($value);
                } catch (\Throwable $e) {
                    return $value;
                }
            },
            set: fn ($value) => empty($value) ? null : Crypt::encryptString((string) $value),
        );
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function scopeAtivas($query)
    {
        return $query->whereNull('deleted_at');
    }

    public static function securityResource(): array
    {
        return array_replace_recursive(parent::securityResource(), [
            'module' => 'Integrações',
            'name' => 'Instância Connect|API',
            'plural_name' => 'Instâncias Connect|API',
            'description' => 'Conexões WhatsApp administradas pelo FERSOFT WEB por empresa.',
            'route_prefix' => 'connect-api',
            'icon' => 'whatsapp',
            'sensitive' => true,
        ]);
    }
}
