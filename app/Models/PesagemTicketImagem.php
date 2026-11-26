<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PesagemTicketImagem extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'pesagem_ticket_imagens';

    protected $fillable = [
        'empresa_id',
        'pesagem_id',
        'ticket_pesagem_id',
        'balanca_config_id',
        'adp_camera_id',
        'camera_uuid',
        'camera_descricao',
        'ordem',
        'arquivo_path',
        'thumb_path',
        'arquivo_url',
        'mime_type',
        'tamanho_bytes',
        'largura',
        'altura',
        'capturado_em',
        'metadata_json',
        'ativo',
        'storage_disk',
        'storage_base_path',
    ];

    protected $casts = [
        'capturado_em' => 'datetime',
        'metadata_json' => 'array',
        'ativo' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(TicketPesagem::class, 'ticket_pesagem_id');
    }

    public function pesagem()
    {
        return $this->belongsTo(Pesagem::class, 'pesagem_id');
    }

    public function balanca()
    {
        return $this->belongsTo(BalancaConfig::class, 'balanca_config_id');
    }

    public function adpCamera()
    {
        return $this->belongsTo(AdpCamera::class, 'adp_camera_id');
    }

    public function getImagemUrlAttribute(): ?string
    {
        if ($this->arquivo_url) {
            return $this->arquivo_url;
        }

        if ($this->arquivo_path) {
            $disk = $this->storage_disk ?: config('pesagem.snapshot_disk', env('PESAGEM_SNAPSHOT_DISK', 'public_path'));

            if ($disk === 'public_path') {
                return asset(ltrim($this->arquivo_path, '/'));
            }

            if ($disk === 'public') {
                return asset('storage/' . ltrim($this->arquivo_path, '/'));
            }

            try {
                return \Illuminate\Support\Facades\Storage::disk($disk)->url($this->arquivo_path);
            } catch (\Throwable $e) {
                return $this->arquivo_url;
            }
        }

        return null;
    }
}
