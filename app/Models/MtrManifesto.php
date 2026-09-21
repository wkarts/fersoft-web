<?php

namespace App\Models;

class MtrManifesto extends BaseModel
{
    protected $table = 'mtr_manifestos';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'orgao', 'tipo_origem',
        'origem_id', 'venda_id', 'ticket_pesagem_id', 'seu_codigo',
        'numero_mtr', 'data_expedicao', 'gerador_cnpj', 'gerador_nome',
        'transportador_cnpj', 'transportador_nome', 'destinador_cnpj',
        'destinador_nome', 'armazenador_cnpj', 'veiculo_placa',
        'motorista_nome', 'chave_nfe', 'observacao', 'status',
        'pdf_base64', 'mensagem_retorno',
    ];

    protected $casts = [
        'data_expedicao' => 'datetime',
    ];

    public function itens()
    {
        return $this->hasMany(MtrManifestoItem::class, 'mtr_manifesto_id');
    }

    public static function securityResource(): array
    {
        return array_replace_recursive(parent::securityResource(), [
            'module' => 'MTR',
            'name' => 'Manifesto MTR',
            'plural_name' => 'Manifestos MTR',
            'route_prefix' => 'mtr/emissao',
            'tenant_visible' => true,
        ]);
    }
}
