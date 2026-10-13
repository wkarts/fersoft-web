<?php

namespace App\Models;

class LoginPresentation extends BaseModel
{
    protected $table = 'login_presentations';

    protected $fillable = [
        'name', 'slug', 'layout', 'font', 'settings', 'is_active', 'published_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static function securityResource(): array
    {
        return array_replace(parent::securityResource(), [
            'module' => 'Configurações',
            'name' => 'Modelo da tela de acesso',
            'plural_name' => 'Modelos da tela de acesso',
            'description' => 'Composições administradas exclusivamente pelo superusuário.',
            'route_prefix' => 'config/aparencia',
            'super_admin_only' => true,
        ]);
    }
}
