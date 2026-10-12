<?php

namespace App\Models;

class LoginPresentationContent extends BaseModel
{
    protected $table = 'login_presentation_contents';

    protected $fillable = [
        'presentation_id', 'type', 'title', 'body', 'image_path', 'link_url',
        'status', 'sort_order', 'starts_at', 'ends_at', 'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public static function securityResource(): array
    {
        return array_replace(parent::securityResource(), [
            'module' => 'Configurações',
            'name' => 'Conteúdo da tela de acesso',
            'plural_name' => 'Conteúdos da tela de acesso',
            'description' => 'Frases, avisos e publicações institucionais.',
            'route_prefix' => 'config/aparencia',
            'super_admin_only' => true,
        ]);
    }
}
