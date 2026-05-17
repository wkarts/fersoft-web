<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Devolucao;
use App\Models\ConfigNota;
use App\Models\FormaPagamento;
use App\Models\Compra;
use App\Models\MultiEmpresaTrait;

class Venda extends BaseModel
{
    //use SoftDeletes;
use MultiEmpresaTrait;
  
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    //public const DELETED_AT = 'deleted_at';

    protected $fillable = [
        'cliente_id', 'usuario_id', 'frete_id', 'valor_total', 'forma_pagamento', 'NfNumero',
        'natureza_id', 'chave', 'path_xml', 'estado', 'observacao', 'desconto',
        'transportadora_id', 'sequencia_cce', 'tipo_pagamento', 'empresa_id',
        'pedido_ecommerce_id', 'bandeira_cartao', 'cnpj_cartao', 'cAut_cartao',
        'descricao_pag_outros', 'acrescimo', 'data_entrega', 'pedido_nuvemshop_id',
        'nSerie', 'data_emissao', 'troca', 'credito_troca', 'data_retroativa', 'numero_sequencial',
        'vendedor_id', 'filial_id', 'data_saida',

        // 1) ICMS Monofásico - Totais
        'total_qbcmono',
        'total_icmsmono',
        'total_qbcmonoreten',
        'total_icmsmonoreten',
        'total_qbcmonoret',
        'total_icmsmonoret',

        // 2) Destino operação
        'destino_operacao',

        // 3) Retenções (IRRF/PIS/COFINS/CSLL)
        'ret_bc_irrf',
        'ret_aliq_irrf',
        'ret_virrf',

        'ret_bc_pis',
        'ret_aliq_pis',
        'ret_vpis',

        'ret_bc_cofins',
        'ret_aliq_cofins',
        'ret_vcofins',

        'ret_bc_csll',
        'ret_aliq_csll',
        'ret_vcsll',

        'flag_normativa_irrf',

        // 4) Totais adicionais
        'total_ipi_devolvido',
        'fk_pes_retirada',
        'flag_end_entrega',
        'total_ii',

        // 5) Receituário / Responsável técnico
        'nreceituario',
        'cpfresptec',

        // 6) Guia de Trânsito
        'tipo_guia_transito',
        'uf_guia_transito',
        'serie_guia_transito',
        'num_guia_transito',

        // 7) IBS/CBS/IS - Totais
        'total_is',
        'total_bc_ibs_cbs',
        'total_ibs',
        'total_ibs_cred_pres',
        'total_ibs_cred_pres_cond_sus',

        'total_ibs_uf_dif',
        'total_ibs_uf_dev_trib',
        'total_ibs_uf',

        'total_ibs_mun_dif',
        'total_ibs_mun_dev_trib',
        'total_ibs_mun',

        'total_cbs_dif',
        'total_cbs_dev_trib',
        'total_cbs',
        'total_cbs_cred_pres',
        'total_cbs_cred_pres_cond_sus',

        'total_ibs_mono',
        'total_cbs_mono',
        'total_ibs_mono_reten',
        'total_cbs_mono_reten',
        'total_ibs_mono_ret',
        'total_cbs_mono_ret',

        'total_nf_ibc_cbs_is',
        'total_ibs_cbs',

        'tipo_nfcredito',
        'tipo_nfdebito',
        'tipo_entegov',
        'perc_redutor_gov',
        'tipo_opergov',

        // 8) Padrão Eloquent (sem ID) - campos adicionais
        'eloquent_uuid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Casts mínimos para evitar strings em campos monetários e datas.
     * Ajuste se algum campo for INT/BOOLEAN no seu banco.
     */
    protected $casts = [
        'valor_total' => 'decimal:2',
        'desconto' => 'decimal:2',
        'acrescimo' => 'decimal:2',

        'total_is' => 'decimal:2',
        'total_bc_ibs_cbs' => 'decimal:2',
        'total_ibs' => 'decimal:2',
        'total_cbs' => 'decimal:2',
        'total_ibs_cbs' => 'decimal:2',

        'total_ii' => 'decimal:2',
        'total_ipi_devolvido' => 'decimal:2',

        'ret_bc_irrf' => 'decimal:2',
        'ret_aliq_irrf' => 'decimal:6',
        'ret_virrf' => 'decimal:2',

        'ret_bc_pis' => 'decimal:2',
        'ret_aliq_pis' => 'decimal:6',
        'ret_vpis' => 'decimal:2',

        'ret_bc_cofins' => 'decimal:2',
        'ret_aliq_cofins' => 'decimal:6',
        'ret_vcofins' => 'decimal:2',

        'ret_bc_csll' => 'decimal:2',
        'ret_aliq_csll' => 'decimal:6',
        'ret_vcsll' => 'decimal:2',

        'data_entrega' => 'date',
        'data_emissao' => 'date',
        'data_saida' => 'date',
        'data_retroativa' => 'date',
    ];

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function vendedor_setado(){
        return $this->belongsTo(Usuario::class, 'vendedor_id');
    }

    public function duplicatas(){
        return $this->hasMany('App\Models\ContaReceber', 'venda_id', 'id');
    }

    public function cliente(){
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function natureza(){
        return $this->belongsTo(NaturezaOperacao::class, 'natureza_id');
    }

    public function troca(){
        return TrocaVenda::where('venda_id', $this->id)
            ->where('tipo', 'pedido')
            ->first();
    }

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function vendedor(){
        $usuario = Usuario::find($this->usuario_id);
        if($usuario->funcionario) return $usuario->funcionario->nome;
        else return '--';
    }

    public function frete(){
        return $this->belongsTo(Frete::class, 'frete_id');
    }

    public function transportadora(){
        return $this->belongsTo(Transportadora::class, 'transportadora_id');
    }

    public function itens(){
        return $this->hasMany('App\Models\ItemVenda', 'venda_id', 'id')->with('produto');
    }

    public function itensApi(){
        return $this->hasMany('App\Models\ItemVenda', 'venda_id', 'id')->with('produto');
    }

    public function referencias(){
        return $this->hasMany('App\Models\NFeReferecia', 'venda_id', 'id');
    }

    public static function lastNF($empresa_id = null){
        if($empresa_id == null){
            $value = session('user_logged');
            $empresa_id = $value['empresa'];
        }else{
            $empresa_id = $empresa_id;
        }

        $venda = Venda::
        where('NfNumero', '!=', 0)
            ->where('empresa_id', $empresa_id)
            ->orderBy('NfNumero', 'desc')
            ->first();

        $devolucao = Devolucao::
        where('numero_gerado', '!=', 0)
            ->where('empresa_id', $empresa_id)
            ->orderBy('numero_gerado', 'desc')
            ->first();

        $compra = Compra::
        where('numero_emissao', '!=', 0)
            ->where('empresa_id', $empresa_id)
            ->orderBy('numero_emissao', 'desc')
            ->first();

        $numeroDevolucao = $devolucao != null ? $devolucao->numero_gerado : 0;
        $numeroCompra = $compra != null ? $compra->numero_emissao : 0;
        $numeroVenda = $venda != null ? $venda->NfNumero : 0;

        if($venda != null || $devolucao != null || $compra != null){
            $numeroConfig = ConfigNota::
            where('empresa_id', $empresa_id)->first()->ultimo_numero_nfe ?? 0;
            if($numeroDevolucao > $numeroVenda && $numeroDevolucao > $numeroCompra && $numeroDevolucao > $numeroConfig){
                return $numeroDevolucao;
            }
            else if($numeroVenda > $numeroDevolucao && $numeroVenda > $numeroCompra && $numeroVenda > $numeroConfig){
                $c = ConfigNota::where('empresa_id', $empresa_id)->first();
                if($venda->nSerie != $c->numero_serie_nfe){
                    return Venda::alteraNumeroSerie($c, $empresa_id);
                }
                return $numeroVenda;
            }
            else if($numeroCompra > $numeroDevolucao && $numeroCompra > $numeroVenda && $numeroCompra > $numeroConfig){
                return $numeroCompra;
            }else{
                return $numeroConfig;
            }


        }else{
            return ConfigNota::where('empresa_id', $empresa_id)->first()->ultimo_numero_nfe;
        }

    }

    public static function alteraNumeroSerie($config, $empresa_id){
        $venda = Venda::
        where('NfNumero', '!=', 0)
            ->where('empresa_id', $empresa_id)
            ->where('nSerie', $config->numero_serie_nfe)
            ->orderBy('NfNumero', 'desc')
            ->first();

        $numeroConfig = ConfigNota::
        where('empresa_id', $empresa_id)->first()->ultimo_numero_nfe ?? 0;

        if($venda == null || $numeroConfig > $venda->NfNumero){
            return $numeroConfig;
        }
        return $venda->NfNumero;
    }

    public static function tiposPagamento(){
        return [
            '01' => 'Dinheiro',
            '02' => 'Cheque',
            '03' => 'Cartão de Crédito',
            '04' => 'Cartão de Débito',
            '05' => 'Crédito Loja',
            '06' => 'Crediário',
            '10' => 'Vale Alimentação',
            '11' => 'Vale Refeição',
            '12' => 'Vale Presente',
            '13' => 'Vale Combustível',
            '14' => 'Duplicata Mercantil',
            '15' => 'Boleto Bancário',
            '16' => 'Depósito Bancário',
            '17' => 'Pagamento Instantâneo (PIX)',
            '18' => 'Transferência bancária, Carteira Digital',
            '19' => 'Programa de fidelidade, Cashback, Crédito Virtual',
            // '20' => 'Pagamento Instantâneo (PIX) – Estático',
            // '21' => 'Crédito em Loja',
            // '22' => 'Pagamento Eletrônico não Informado - falha de hardware do sistema emissor',
            '90' => 'Sem Pagamento',
            // '99' => 'Outros',
        ];
    }

    public static function getTipoPagamentoNFe($tipo){
        $values = [
            'Dinheiro' => '01',
            'Cheque' => '02',
            'Cartão de Crédito' => '03',
            'Cartão de Débito' => '04',
            'Crédito Loja' => '05',
            'Crediário' => '06',
            'Vale Alimentação' => '10',
            'Vale Refeição' => '11',
            'Vale Presente' => '12',
            'Vale Combustível' => '13',
            'Duplicata Mercantil' => '14',
            'Boleto Bancário' => '15',
            'Depósito Bancário' => '16',
            'Pagamento Instantâneo (PIX)' => '17',
            'Transferência bancária, Carteira Digital' => '18',
            'Programa de fidelidade, Cashback, Crédito Virtual' => '19',
            // 'Pagamento Instantâneo (PIX) – Estático' => '20',
            // 'Crédito em Loja' => '21',
            // 'Pagamento Eletrônico não Informado - falha de hardware do sistema emissor' => '22',
            'Sem Pagamento' => '90',
            // 'Outros' => '99',
        ];
        try{
            return $values[$tipo];
        }catch(\Exception $e){
            return $values["Dinheiro"];
        }
    }

    public static function bandeiras(){
        return [
            '01' => 'Visa',
            '02' => 'Mastercard',
            '03' => 'American Express',
            '04' => 'Sorocred',
            '05' => 'Diners Club',
            '06' => 'Elo',
            '07' => 'Hipercard',
            '08' => 'Aura',
            '09' => 'Cabal',
            '99' => 'Outros'
        ];
    }

    public static function getTipo($tipo){
        $tipos = Venda::tiposPagamento();
        if($tipo == ''){
            $tipo = '01';
        }
        return $tipos[$tipo];
    }

    public static function filtroData($dataInicial, $dataFinal, $estado, $tipoPesquisaData, $numero_nfe, $dataEmissao){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = Venda::
        select('vendas.*')
            ->whereBetween($tipoPesquisaData, [$dataInicial,
                $dataFinal])
            ->where('vendas.empresa_id', $empresa_id)
            ->where('vendas.forma_pagamento', '!=', 'conta_crediario');

        if($estado != 'TODOS') $c->where('vendas.estado', $estado);
        if($numero_nfe != ""){
            $c->where('NfNumero', $numero_nfe);
        }

        if($dataEmissao != '1969-12-31'){
            $c->whereDate('data_emissao', $dataEmissao);
        }

        return $c->get();
    }

    public static function filtroDataCliente($cliente, $dataInicial, $dataFinal, $estado,
                                             $tipoPesquisa, $tipoPesquisaData, $numero_nfe){

        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = Venda::
        select('vendas.*')
            ->join('clientes', 'clientes.id' , '=', 'vendas.cliente_id')
            ->where('clientes.'.$tipoPesquisa, 'LIKE', "%$cliente%")
            ->where('vendas.forma_pagamento', '!=', 'conta_crediario')
            ->where('vendas.empresa_id', $empresa_id)

            ->whereBetween($tipoPesquisaData, [$dataInicial,
                $dataFinal]);
        if($numero_nfe != ""){
            $c->where('NfNumero', $numero_nfe);
        }

        if($estado != 'TODOS') $c->where('vendas.estado', $estado);
        return $c->get();
    }

    public static function filtroCliente($cliente, $estado, $tipoPesquisa, $numero_nfe){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = Venda::
        select('vendas.*')
            ->join('clientes', 'clientes.id' , '=', 'vendas.cliente_id')
            ->where('clientes.'.$tipoPesquisa, 'LIKE', "%$cliente%")
            ->where('vendas.empresa_id', $empresa_id)
            ->where('vendas.forma_pagamento', '!=', 'conta_crediario');

        if($estado != 'TODOS') $c->where('vendas.estado', $estado);
        if($numero_nfe != ""){
            $c->where('NfNumero', $numero_nfe);
        }
        return $c->get();
    }

    public static function filtroEstado($estado, $numero_nfe){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $c = Venda::
        where('vendas.estado', $estado)
            ->where('vendas.empresa_id', $empresa_id)
            ->where('vendas.forma_pagamento', '!=', 'conta_crediario');

        if($numero_nfe != ""){
            $c->where('NfNumero', $numero_nfe);
        }
        return $c->get();
    }


    public static function filtroDataApp($dataInicial, $dataFinal, $estado, $empresa_id){

        $c = Venda::
        select('vendas.*')
            ->whereBetween('data_registro', [$dataInicial,
                $dataFinal])
            ->where('vendas.empresa_id', $empresa_id)
            ->where('vendas.forma_pagamento', '!=', 'conta_crediario');

        if($estado != 'TODOS') $c->where('vendas.estado', $estado);

        return $c->get();
    }

    public static function filtroDataClienteApp($cliente, $dataInicial, $dataFinal, $estado, $empresa_id){

        $c = Venda::
        select('vendas.*')
            ->join('clientes', 'clientes.id' , '=', 'vendas.cliente_id')
            ->where('clientes.razao_social', 'LIKE', "%$cliente%")
            ->where('vendas.forma_pagamento', '!=', 'conta_crediario')
            ->where('vendas.empresa_id', $empresa_id)

            ->whereBetween('data_registro', [$dataInicial,
                $dataFinal]);

        if($estado != 'TODOS') $c->where('vendas.estado', $estado);
        return $c->get();
    }

    public static function filtroClienteApp($cliente, $estado, $empresa_id){

        $c = Venda::
        select('vendas.*')
            ->join('clientes', 'clientes.id' , '=', 'vendas.cliente_id')
            ->where('clientes.razao_social', 'LIKE', "%$cliente%")
            ->where('vendas.empresa_id', $empresa_id)
            ->where('vendas.forma_pagamento', '!=', 'conta_crediario');

        if($estado != 'TODOS') $c->where('vendas.estado', $estado);

        return $c->get();
    }

    public static function filtroEstadoApp($estado, $empresa_id){

        $c = Venda::
        where('vendas.estado', $estado)
            ->where('vendas.empresa_id', $empresa_id)
            ->where('vendas.forma_pagamento', '!=', 'conta_crediario');
        return $c->get();
    }

    public function getTipoPagamento(){
        foreach(Venda::tiposPagamento() as $key => $t){
            if($this->tipo_pagamento == $key) return $t;
        }
    }

    public static function estados(){
        return [
            "AC",
            "AL",
            "AM",
            "AP",
            "BA",
            "CE",
            "DF",
            "ES",
            "GO",
            "MA",
            "MG",
            "MS",
            "MT",
            "PA",
            "PB",
            "PE",
            "PI",
            "PR",
            "RJ",
            "RN",
            "RS",
            "RO",
            "RR",
            "SC",
            "SE",
            "SP",
            "TO",

        ];
    }

    public function multiplo(){
        return "Outros";
    }

    public function taxaFormaPagamento(){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $formaPag = FormaPagamento::
        where('nome', $this->forma_pagamento)
            ->where('empresa_id', $empresa_id)
            ->first();
        if($formaPag != null){
            if($formaPag->tipo_taxa == 'perc'){
                return number_format($formaPag->taxa,2,',', '.').'%' ;
            }else{
                return 'R$ ' . number_format($formaPag->taxa,2,',', '.') ;
            }
        }else{
            return "0,00";
        }
    }

    public function valorLiquido(){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $formaPag = FormaPagamento::
        where('nome', $this->forma_pagamento)
            ->where('empresa_id', $empresa_id)
            ->first();
        if($formaPag != null){
            $total = $this->valor_total+$this->acrescimo-$this->desconto;
            $valor = 0;
            if($formaPag->tipo_taxa == 'perc'){
                $valor = $total - (($total*$formaPag->taxa)/100);
            }else{
                $valor = $total - $formaPag->taxa;
            }

            return $valor;

        }else{
            return $this->valor_total-$this->desconto+$this->acrescimo;
        }
    }

    public function valorDespesaOperacionais(){
        $value = session('user_logged');
        $empresa_id = $value['empresa'];
        $formaPag = FormaPagamento::
        where('nome', $this->forma_pagamento)
            ->where('empresa_id', $empresa_id)
            ->first();
        if($formaPag != null){
            $total = $this->valor_total+$this->acrescimo-$this->desconto;
            $valor = 0;
            if($formaPag->tipo_taxa == 'perc'){
                $valor = (($total*$formaPag->taxa)/100);
            }else{
                $valor = $formaPag->taxa;
            }

            return 'R$ ' . number_format($valor,2,',', '.') ;

        }else{
            return "R$ 0,00";
        }
    }

    public function getFormaPagamento($empresa_id = null){
        if($empresa_id == null){
            $value = session('user_logged');
            $empresa_id = $value['empresa'];
        }
        $forma = FormaPagamento::
        where('chave', $this->forma_pagamento)
            ->where('empresa_id', $empresa_id)
            ->first();

        return $forma;
    }

    public static function randSuccess(){
        $arr = [
            'success.json',
            'success2.json',
            'success3.json',
            'success4.json',
        ];
        $rand = rand(0, sizeof($arr)-1);
        return $arr[$rand];
    }

    public function tiposDePagamento(){
        if(sizeof($this->duplicatas) > 0){
            $tipo = $this->duplicatas[0]->tipo_pagamento;
            foreach($this->duplicatas as $d){
                if($tipo != $d->tipo_pagamento){
                    return "<button onclick='detalhePagamento(".$this->id.")' class='btn btn-sm btn-danger'>ver pagamentos</button>";
                }
            }
            return $tipo;
        }else{
            return Venda::getTipoPagamento($this->tipo_pagamento);
        }
    }

    public function temVinculoNFe(): bool
    {
        if($this->NfNumero > 0){
            return true;
        }

        if($this->chave != null && trim($this->chave) != ''){
            return true;
        }

        if($this->path_xml != null && trim($this->path_xml) != ''){
            return true;
        }

        $estado = strtoupper(trim($this->estado ?? ''));
        $estadosComRetornoFiscal = [
            'APROVADO', 'APROVADA', 'AUTORIZADO', 'AUTORIZADA', 'TRANSMITIDO', 'ENVIADO',
            'EM_PROCESSAMENTO', 'REJEITADO', 'DENEGADO', 'CANCELADO', 'INUTILIZADA'
        ];

        if(in_array($estado, $estadosComRetornoFiscal)){
            return true;
        }

        return false;
    }

    public function podeSerExcluida(): bool
    {
        return !$this->temVinculoNFe();
    }

    public function podeSerInutilizada(): bool
    {
        $estado = strtoupper($this->estado ?? '');
        return $this->temVinculoNFe() && in_array($estado, ['REJEITADO', 'DENEGADO']) && $estado != 'INUTILIZADA';
    }

    public function podeSerEditada(): bool
    {
        return !$this->temVinculoNFe();
    }

    public static function securityResource(): array
    {
        return array_replace_recursive(parent::securityResource(), [
            'module' => 'Vendas',
            'name' => 'Venda',
            'plural_name' => 'Vendas',
            'description' => 'Movimentações comerciais e fiscais de vendas.',
            'route_prefix' => 'vendas',
            'icon' => 'shopping-cart',
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
