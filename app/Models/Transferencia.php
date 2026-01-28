<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id', 'filial_saida_id', 'filial_entrada_id', 'usuario_id', 'observacao', 'chave', 'numero_nfe',
        'natureza_id', 'transportadora_id', 'data_emissao', 'finNFe', 'tpNF', 'sequencia_cce', 'estado',
    ];

    public function filial_saida(){
        return $this->belongsTo(Filial::class, 'filial_saida_id');
    }

    public function filial_entrada(){
        return $this->belongsTo(Filial::class, 'filial_entrada_id');
    }

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function itens(){
        return $this->hasMany(ItemTransferencia::class, 'transferencia_id');
    }

    public static function finalidades(){
        return [
            '1' => '1 - NF-e normal',
            '2' => '2 - NF-e complementar',
            '3' => '3 - NF-e de ajuste',
            '4' => '4 - Devolução de mercadoria'
        ];
    }

    public function natureza(){
        return $this->belongsTo(NaturezaOperacao::class, 'natureza_id');
    }

    public function transportadora(){
        return $this->belongsTo(Transportadora::class, 'transportadora_id');
    }

    public static function lastNFe($empresa_id = null){
        if($empresa_id == null){
            $value = session('user_logged');
            $empresa_id = $value['empresa'];
        }
        $numeroVenda = Venda::lastNF($empresa_id);

        $transferencia = Transferencia::
        where('numero_nfe', '!=', 0)
        ->where('empresa_id', $empresa_id)
        ->orderBy('numero_nfe', 'desc')
        ->first();

        $numeroRemessa = $transferencia != null ? $transferencia->numero_nfe : 0;

        if($numeroRemessa > $numeroVenda){
            return $numeroRemessa;
        }else{
            return $numeroVenda;
        }
    }


}
