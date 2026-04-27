<?php

namespace App\Models;

class OrdemServico extends BaseModel
{

    protected $fillable = [
        'descricao', 'cliente_id', 'usuario_id', 'empresa_id', 'valor', 'desconto', 'acrescimo', 'observacao',
        'numero_sequencial', 'filial_id'
    ];

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function servicos(){
        return $this->hasMany(ServicoOs::class, 'ordem_servico_id', 'id');
    }

    public function produtos(){
        return $this->hasMany(ProdutoOs::class, 'ordem_servico_id', 'id');
    }

    public function relatorios(){
        return $this->hasMany('App\Models\RelatorioOs', 'ordem_servico_id', 'id');
    }

    public function contaReceber(){
        return $this->belongsTo(ContaReceber::class, 'conta_receber_id');
    }

    public function funcionarios(){
        return $this->hasMany('App\Models\FuncionarioOs', 'ordem_servico_id', 'id');
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function venda(){
        return $this->belongsTo(Venda::class, 'venda_id');
    }

    public function vendaCaixa(){
        return $this->belongsTo(VendaCaixa::class, 'venda_id');
    }

    public function nfse(){
        return $this->belongsTo(Nfse::class, 'nfse_id');
    }

    public static function filtroData($dataInicial, $dataFinal, $estado){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = OrdemServico::
        whereBetween('data_vencimento', [$dataInicial, 
            $dataFinal])
        ->where('ordem_servicos.empresa_id', $empresa_id)
        ->where('estado', $estado);

        return $c->get();
    }
    public static function filtroDataFornecedor($cliente, $dataInicial, $dataFinal, $estado){

        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = OrdemServico::
        join('clientes', 'clientes.id' , '=', 'ordem_servicos.cliente_id')
        ->where('razao_social.nome', 'LIKE', "%$cliente%")
        ->whereBetween('data_vencimento', [$dataInicial, 
            $dataFinal])
        ->where('ordem_servicos.empresa_id', $empresa_id)
        ->where('estado', $estado);
        return $c->get();
    }

    public static function filtroCliente($cliente, $estado){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = OrdemServico::
        join('clientes', 'clientes.id' , '=', 'ordem_servicos.cliente_id')
        ->where('razao_social', 'LIKE', "%$cliente%")
        ->where('ordem_servicos.empresa_id', $empresa_id)
        ->where('estado', $estado);
        
        return $c->get();
    }

    public static function tiposPagamento(){
        return [
            'Dinheiro',
            'Cheque',
            'Cartão de Crédito',
            'Cartão de Débito',
            'Crédito Loja',
            'Crediário',
            'Vale Alimentação',
            'Vale Refeição',
            'Vale Presente',
            'Vale Combustível',
            'Duplicata Mercantil',
            'Boleto Bancário',
            'Depósito Bancário',
            'Pagamento Instantâneo (PIX)',
            'Sem Pagamento',
            'Outros',
        ];
    }

    public function total_os(){
        return $this->produtos->sum('sub_total') + $this->servicos->sum('sub_total') + 
        $this->acrescimo - $this->desconto;
    }
    
}
