<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ConfigNota;

class VendaCaixa extends Model
{
	protected $fillable = [
		'cliente_id', 'usuario_id', 'valor_total', 'NFcNumero',
		'natureza_id', 'chave', 'path_xml', 'estado', 'tipo_pagamento', 'forma_pagamento',
		'dinheiro_recebido', 'troco', 'nome', 'cpf', 'observacao', 'desconto', 'acrescimo',
		'pedido_delivery_id', 'tipo_pagamento_1', 'valor_pagamento_1', 'tipo_pagamento_2',
		'valor_pagamento_2', 'tipo_pagamento_3', 'valor_pagamento_3', 'empresa_id',
		'bandeira_cartao', 'cnpj_cartao', 'cAut_cartao', 'descricao_pag_outros', 'rascunho',
		'consignado', 'pdv_java', 'retorno_estoque', 'troca', 'credito_troca', 'prevenda_nivel',
		'pedido_ifood_id', 'filial_id', 'valor_cashback', 'vendedor_id',

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

	public function filial(){
		return $this->belongsTo(Filial::class, 'filial_id');
	}

	public function itens(){
		return $this->hasMany('App\Models\ItemVendaCaixa', 'venda_caixa_id', 'id');
	}

	public function itensApi(){
		return $this->hasMany('App\Models\ItemVendaCaixa', 'venda_caixa_id', 'id')->with('produto');
	}

	public function fatura(){
		return $this->hasMany('App\Models\FaturaFrenteCaixa', 'venda_caixa_id', 'id');
	}

	public function comissaoAssessor(){
		return $this->hasOne('App\Models\ComissaoAssessor', 'venda_caixa_id', 'id');
	}

	public function vendaNfe(){
		return $this->hasOne('App\Models\RemessaNfe', 'venda_caixa_id', 'id');
	}

	public function troca(){
		return TrocaVenda::where('venda_id', $this->id)
		->where('tipo', 'pdv')
		->first();
	}

	public function cliente(){
		return $this->belongsTo(Cliente::class, 'cliente_id');
	}

	public function pedidoDelivery(){
		return $this->belongsTo(PedidoDelivery::class, 'pedido_delivery_id');
	}

	public function natureza(){
		return $this->belongsTo(NaturezaOperacao::class, 'natureza_id');
	}

	public function usuario(){
		return $this->belongsTo(Usuario::class, 'usuario_id');
	}

	public function vendedor_setado(){
		return $this->belongsTo(Usuario::class, 'vendedor_id');
	}

	public function vendedor(){
		$usuario = Usuario::find($this->usuario_id);
		if($usuario->funcionario) return $usuario->funcionario->nome;
		else return '--';
	}

	public static function lastNumero($empresa_id){
		$numero_sequencial = 0;
		$last = VendaCaixa::where('empresa_id', $empresa_id)
		->orderBy('id', 'desc')
		->first();

		$numero_sequencial = $last != null ? ($last->numero_sequencial + 1) : 1;
		return $numero_sequencial;
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
			'90' => 'Sem pagamento',
			// '99' => 'Outros',
		];
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

	public static function getTipoPagamento($tipo){
		if(isset(VendaCaixa::tiposPagamento()[$tipo])){
			if(VendaCaixa::tiposPagamento()[$tipo] == 'Pagamento Instantâneo (PIX)') return 'PIX';
			return VendaCaixa::tiposPagamento()[$tipo];
		}else{
			return "Não identificado";
		}
	}

	public function getTipoPagamento2(){
		foreach(VendaCaixa::tiposPagamento() as $key => $t){
			if($this->tipo_pagamento == $key) return $t;
		}
	}

	public static function lastNFCe($empresa_id = null){
		if($empresa_id == null){
			$value = session('user_logged');
			$empresa_id = $value['empresa'];
		}else{
			$empresa_id = $empresa_id;
		}

		$venda = VendaCaixa::
		where('NFcNumero', '!=', 0)
		->where('empresa_id', $empresa_id)
		->orderBy('NFcNumero', 'desc')
		->first();

		if($venda == null) {
			return ConfigNota::
			where('empresa_id', $empresa_id)
			->first()->ultimo_numero_nfce;
		}
		else{
			$configNum = ConfigNota::
			where('empresa_id', $empresa_id)->first()->ultimo_numero_nfce;
			return $configNum > $venda->NFcNumero ? $configNum : $venda->NFcNumero;
		}
	}

	public static function filtroData($dataInicial, $dataFinal, $config){
		$value = session('user_logged');
		$empresa_id = $value['empresa'];

		return VendaCaixa::
		orderBy('id', 'desc')
		->whereBetween('data_registro', [$dataInicial,
			$dataFinal])
		->where('empresa_id', $empresa_id)
		->when($config->caixa_por_usuario == 1, function ($q) use ($config) {
			return $q->where('usuario_id', get_id_user());
		})
		->get();
	}

	public static function filtroCliente($cliente){

		$value = session('user_logged');
		$empresa_id = $value['empresa'];

		return VendaCaixa::
		join('clientes', 'clientes.id' , '=', 'venda_caixas.cliente_id')
		->where('clientes.razao_social', 'LIKE', "%$cliente%")
		->where('venda_caixas.empresa_id', $empresa_id)
		->get();
	}

	public static function filtroData2($data){

		$value = session('user_logged');
		$empresa_id = $value['empresa'];
		$data = str_replace("/", "-", $data);
		$data = \Carbon\Carbon::parse($data)->format('Y-m-d');

		return VendaCaixa::
		whereBetween('created_at', [
			$data . " 00:00:00",
			$data . " 23:59:59",
		])
		->where('empresa_id', $empresa_id)
		->get();
	}

	public static function filtroNFCe($nfce){

		$value = session('user_logged');
		$empresa_id = $value['empresa'];

		return VendaCaixa::
		where('NFcNumero', $nfce)
		->where('empresa_id', $empresa_id)
		->get();
	}

	public static function filtroValor($valor){

		$value = session('user_logged');
		$empresa_id = $value['empresa'];
		return VendaCaixa::
		where('valor_total', 'LIKE', "%$valor%")
		->where('empresa_id', $empresa_id)
		->get();
	}

	public static function filtroEstado($estado){

		$value = session('user_logged');
		$empresa_id = $value['empresa'];
		$c = VendaCaixa::
		where('estado', $estado)
		->where('empresa_id', $empresa_id)
		->where('forma_pagamento', '!=', 'conta_crediario');
		return $c->get();
	}

	public static function filtroDataApp($dataInicial, $dataFinal, $empresa_id){

		return VendaCaixa::
		orderBy('id', 'desc')
		->whereBetween('data_registro', [$dataInicial,
			$dataFinal])
		->where('empresa_id', $empresa_id)
		->get();
	}

	public static function filtroEstadoApp($estado, $empresa_id){
		$c = VendaCaixa::
		where('estado', $estado)
		->where('empresa_id', $empresa_id)
		->where('forma_pagamento', '!=', 'conta_crediario');
		return $c->get();
	}

	public function multiplo(){
		$text = '';
		// if($this->valor_pagamento_1 > 0){
		// 	$text .= VendaCaixa::getTipoPagamento($this->tipo_pagamento_1) . ' - R$ ' . number_format($this->valor_pagamento_1, 2);
		// }

		// if($this->valor_pagamento_2 > 0){
		// 	$text .= ' | '.VendaCaixa::getTipoPagamento($this->tipo_pagamento_2) . ' - R$ ' . number_format($this->valor_pagamento_2, 2);
		// }

		// if($this->valor_pagamento_3 > 0){
		// 	$text .= ' | '.VendaCaixa::getTipoPagamento($this->tipo_pagamento_3) . ' - R$ ' . number_format($this->valor_pagamento_3, 2);
		// }

		foreach($this->fatura as $key => $f){
			$fp = (string)trim($f->forma_pagamento);
			$text .= ($key > 0 ? ' | ' : '') .VendaCaixa::getTipoPagamento($fp) . ' - R$ ' . moeda($f->valor);
		}
		return $text;
	}

	public static function tiposPagamentoMulti(){
		return [
			'DINHEIRO',
			'CARTÃO DE DÉBITO',
			'CARTÃO DE CRÉDITO',
			'VALE REFEIÇÃO'
		];
	}

	public function isComprovanteAssessor(){
		foreach($this->itens as $i){
			if($i->valor_comissao_assessor > 0) return true;
		}
		return false;
	}

	public function tiposDePagamento(){
		if(sizeof($this->fatura) > 0){
			$tipo = $this->fatura[0]->forma_pagamento;
			foreach($this->fatura as $d){
				if($tipo != $d->forma_pagamento){
					return "<button onclick='detalhePagamentoPdv(".$this->id.")' class='btn btn-sm btn-danger'>ver pagamentos</button>";
				}
			}
			return VendaCaixa::getTipoPagamento($tipo);
		}else{
			return VendaCaixa::getTipoPagamento($this->tipo_pagamento);
		}
	}

}
