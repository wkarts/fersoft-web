<?php

namespace App\Models;

class MtrDeparaResiduo extends BaseModel
{
    protected $table = 'mtr_depara_residuos';

    protected $fillable = [
        'empresa_id', 'filial_id', 'usuario_id', 'orgao', 'produto_id',
        'categoria_id', 'sub_categoria_id', 'ncm', 'cod_ibama',
        'descricao_residuo', 'classe_residuo', 'estado_fisico',
        'acondicionamento_id', 'tratamento_id', 'unidade_medida',
        'fator_conversao',
    ];

    protected $casts = [
        'fator_conversao' => 'decimal:4',
    ];

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subCategoria()
    {
        return $this->belongsTo(SubCategoria::class, 'sub_categoria_id');
    }

    public function getProdutoNomeAttribute(): ?string
    {
        return $this->produto?->nome;
    }

    public function getProdutoNcmAttribute(): ?string
    {
        return $this->produto?->NCM;
    }

    public function getCategoriaNomeAttribute(): ?string
    {
        if ($this->categoria) {
            return $this->categoria->nome;
        }

        if ($this->subCategoria) {
            $categoria = $this->subCategoria->categoria?->nome;

            return $categoria
                ? $categoria . ' / ' . $this->subCategoria->nome
                : $this->subCategoria->nome;
        }

        return null;
    }
}
