<?php

namespace App\Models;

class LoginPresentationAsset extends BaseModel
{
    protected $table = 'login_presentation_assets';

    protected $fillable = [
        'asset_key', 'path', 'mime_type', 'sha256', 'width', 'height',
        'size_bytes', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function securityResource(): array
    {
        return array_replace(parent::securityResource(), [
            'module' => 'Configurações',
            'name' => 'Arquivo da identidade visual',
            'plural_name' => 'Arquivos da identidade visual',
            'description' => 'Arquivos versionados da identidade institucional.',
            'route_prefix' => 'config/aparencia',
            'super_admin_only' => true,
        ]);
    }
}
