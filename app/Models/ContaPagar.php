<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FilialInjectable;
use App\Models\MultiEmpresaTrait;

class ContaPagar extends BaseModel
{
    
  use MultiEmpresaTrait;
  use FilialInjectable;

  protected static function boot()
{
    parent::boot();
    // Teste: Se a tela de Contas a Pagar abrir sem esse erro, 
    // significa que ela NÃO usa este Model.
    // dd("O Model está sendo carregado!"); 
}
  
    protected $fillable = [
        'compra_id', 'data_vencimento', 'data_emissao', 'data_pagamento','numero_emissao',
        'valor_integral', 'valor_original', 'valor_pago', 'referencia','nf',
        'categoria_id', 'status', 'empresa_id', 'fornecedor_id',
        'tipo_pagamento', 'numero_nota_fiscal', 'filial_id', 'observacao',
        'valor_inss', 'valor_iss', 'valor_pis', 'valor_cofins', 'valor_ir', 'valor_csll', 'usuario_edicao_id',
        'outras_retencoes', 'usuario_id', 'usuario_baixa_id', 'veiculo_id','juros', 'multa'
    ];

    // Atributo virtual para retornar valor líquido
    public function getValorLiquidoAttribute()
    {
        $totalRetencoes =
            ($this->valor_inss ?: 0) +
            ($this->valor_iss ?: 0) +
            ($this->valor_pis ?: 0) +
            ($this->valor_cofins ?: 0) +
            ($this->valor_ir ?: 0) +
            ($this->outras_retencoes ?: 0);

        return (float)($this->valor_original ?: $this->valor_integral) - $totalRetencoes;
    }

    public function setFilialIdAttribute($value)
    {
        $this->attributes['filial_id'] = ((int) $value === -1 ? null : $value);
    }

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function compra(){
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function fornecedor(){
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    // RELAÇÕES ADICIONADAS / AJUSTADAS
    public function categoria()
    {
        return $this->belongsTo(\App\Models\CategoriaConta::class, 'categoria_id');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'usuario_id');
    }

    public function usuarioBaixa()
    {
        return $this->belongsTo(\App\Models\Usuario::class, 'usuario_baixa_id');
    }
  
   public function usuarioEdicao()
     {
    // Vincula o campo 'usuario_edicao_id' da tabela ao Model de Usuário
    return $this->belongsTo(User::class, 'usuario_edicao_id');
    }
  

    public function itemConta()
    {
        return $this->hasOne(ItemContaEmpresa::class, 'conta_pagar_id'); 
    }

    public function contaEmpresa()
    {
        return $this->belongsTo(ContaEmpresa::class, 'conta_empresa_id');
    }

    public function diasAtraso(){
        $d = date('Y-m-d');
        $d2 = $this->data_vencimento;
        $dif = strtotime($d2) - strtotime($d);
        $dias = floor($dif / (60 * 60 * 24));

        if($dias == 0) return "conta vence hoje";
        if($dias > 0) return "$dias dia(s) para o vencimento";
        return "conta vencida à " . ($dias*-1) . " dia(s)";
    }

    public static function filtroData($dataInicial, $dataFinal, $status, $tipoFiltro = 'data_vencimento'){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $coluna = 'data_vencimento';

        if($tipoFiltro == 'emissao') $coluna = 'data_emissao';
        if($tipoFiltro == 'registro') $coluna = 'created_at';

        $c = ContaPagar::orderBy($coluna, 'asc')
            ->where('empresa_id', $empresa_id)
            ->whereBetween($coluna, [$dataInicial . " 00:00:00", $dataFinal . " 23:59:59"]);

        if($status == 'pago') $c->where('status', true);
        else if($status == 'pendente') $c->where('status', false);

        return $c->get();
    }

    public static function filtroDataFornecedor($fornecedor, $dataInicial, $dataFinal, $status){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $contas = [];

        $c = ContaPagar::orderBy('conta_pagars.data_vencimento', 'asc')
            ->join('compras', 'compras.id' , '=', 'conta_pagars.compra_id')
            ->join('fornecedors', 'fornecedors.id' , '=', 'compras.fornecedor_id')
            ->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%")
            ->where('conta_pagars.empresa_id', $empresa_id)
            ->whereBetween('data_vencimento', [$dataInicial, $dataFinal]);

        if($status == 'pago') $c->where('status', true);
        else if($status == 'pendente') $c->where('status', false);

        foreach($c->get() as $t) array_push($contas, $t);

        $c2 = ContaPagar::select('conta_pagars.*')
            ->orderBy('conta_pagars.data_vencimento', 'asc')
            ->join('fornecedors', 'fornecedors.id' , '=', 'conta_pagars.fornecedor_id')
            ->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%")
            ->where('conta_pagars.empresa_id', $empresa_id)
            ->whereBetween('data_vencimento', [$dataInicial, $dataFinal]);

        if($status == 'pago') $c2->where('status', true);
        else if($status == 'pendente') $c2->where('status', false);

        foreach($c2->get() as $t) array_push($contas, $t);

        return $contas;
    }

    public static function filtroFornecedor($fornecedor, $status){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $contas = [];

        $c = ContaPagar::select('conta_pagars.*')
            ->orderBy('conta_pagars.data_vencimento', 'asc')
            ->join('compras', 'compras.id' , '=', 'conta_pagars.compra_id')
            ->join('fornecedors', 'fornecedors.id' , '=', 'compras.fornecedor_id')
            ->where('conta_pagars.empresa_id', $empresa_id)
            ->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");

        if($status == 'pago') $c->where('status', true);
        else if($status == 'pendente') $c->where('status', false);

        foreach($c->get() as $t) array_push($contas, $t);

        $c2 = ContaPagar::select('conta_pagars.*')
            ->orderBy('conta_pagars.data_vencimento', 'asc')
            ->join('fornecedors', 'fornecedors.id' , '=', 'conta_pagars.fornecedor_id')
            ->where('conta_pagars.empresa_id', $empresa_id)
            ->where('fornecedors.razao_social', 'LIKE', "%$fornecedor%");

        if($status == 'pago') $c2->where('status', true);
        else if($status == 'pendente') $c2->where('status', false);

        foreach($c2->get() as $t) array_push($contas, $t);

        return $contas;
    }

    public static function filtroStatus($status){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];

        $c = ContaPagar::where('empresa_id', $empresa_id)
            ->orderBy('conta_pagars.data_vencimento', 'asc');

        if($status == 'pago') $c->where('status', true);
        else if($status == 'pendente') $c->where('status', false);

        return $c->get();
    }
 
  public function veiculo()
  {
    // Verifique se o nome da classe do seu modelo de veículos é Veiculo
    return $this->belongsTo(Veiculo::class, 'veiculo_id');
  }

    public static function tiposPagamento(){
        return [
            'Dinheiro', 'Cheque', 'Banco Itau', 'Boleto', 'Banco Santander',
            'Banco Bradesco', 'Banco do Brasil', 'Banco Inter', 'C6Bank', 'Cora',
            'Caixa 01', 'Caixa 02', 'Credito Fornecedor', 'Crédito Cliente',
            'Cartão de Crédito', 'Cartão de Débito', 'Vale Alimentação',
            'Vale Refeição', 'Vale Presente', 'Vale Combustível',
            'Depósito Bancário', 'Pix', 'Outros'
        ];
    }

    public static function securityResource(): array
    {
        return array_replace_recursive(parent::securityResource(), [
            'module' => 'Financeiro',
            'name' => 'Conta a Pagar',
            'plural_name' => 'Contas a Pagar',
            'description' => 'Controle financeiro de contas a pagar.',
            'route_prefix' => 'contasPagar',
            'icon' => 'money-check-alt',
            'sensitive' => true,
            'tenant_visible' => true,
            'super_admin_only' => false,
            'actions' => [
                'view' => true,
                'create' => true,
                'edit' => true,
                'delete' => true,
                'restore' => false,
                'export' => true,
                'print' => true,
            ],
        ]);
    }

}