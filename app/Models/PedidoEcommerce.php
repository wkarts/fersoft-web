<?php

namespace App\Models;

use App\Traits\FilialInjectable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ItemPedidoEcommerce;

class PedidoEcommerce extends BaseModel
{
    use HasFactory, FilialInjectable;

	protected $fillable = [
        'empresa_id',
        'cliente_id',
        'endereco_id',
        'status',
        'valor_total',
        'valor_frete',
        'tipo_frete',
		'venda_id',
        'numero_nfe',
        'observacao',
        'rand_pedido',
        'link_boleto',
		'qr_code_base64',
        'qr_code', 'transacao_id',
        'forma_pagamento',
        'status_pagamento',
		'status_detalhe',
        'status_preparacao',
        'codigo_rastreio',
        'token', 'desconto',
		'cupom_desconto',
        'modelo_orcamento'
	];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function filial(){
        return $this->belongsTo(Filial::class, 'filial_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

	public function itens(){
		return $this->hasMany('App\Models\ItemPedidoEcommerce', 'pedido_id', 'id');
	}

	public function venda(){
		return $this->hasOne('App\Models\Venda', 'pedido_ecommerce_id', 'id');
	}

	public function cliente(){
		return $this->belongsTo(ClienteEcommerce::class, 'cliente_id');
	}

	public function endereco(){
		return $this->belongsTo(EnderecoEcommerce::class, 'endereco_id');
	}

	public function somaItens(){
		$soma = 0;
		foreach($this->itens as $i){
			$soma += $i->quantidade * $i->produto->valor;
		}
		return $soma;
	}

	public function somaItensPorCep($cep){
		$itensPedido = ItemPedidoEcommerce::
		select('item_pedido_ecommerces.*')
		->join('produto_ecommerces', 'produto_ecommerces.id', '=',
			'item_pedido_ecommerces.produto_id')
		->where('item_pedido_ecommerces.pedido_id', $this->id)
		->where('produto_ecommerces.cep', $cep)
		->get();
		$soma = 0;
		foreach($itensPedido as $i){
			$soma += $i->quantidade * $i->produto->valor;
		}
		return $soma;
	}

	public function somaPeso(){
		$soma = 0;
		foreach($this->itens as $i){
			if($i->produto->produto->peso_bruto == 0 || !$i->produto->produto->peso_bruto){
				$i->produto->produto->peso_bruto = 0.300;
			}
			$soma += $i->quantidade * $i->produto->produto->peso_bruto;
		}
		return $soma;
	}

	public function somaPesoPorCep($cep){
		$itensPedido = ItemPedidoEcommerce::
		select('item_pedido_ecommerces.*')
		->join('produto_ecommerces', 'produto_ecommerces.id', '=',
			'item_pedido_ecommerces.produto_id')
		->where('item_pedido_ecommerces.pedido_id', $this->id)
		->where('produto_ecommerces.cep', $cep)
		->get();
		$soma = 0;
		foreach($itensPedido as $i){
			if($i->produto->produto->peso_bruto == 0 || !$i->produto->produto->peso_bruto){
				$i->produto->produto->peso_bruto = 0.300;
			}
			$soma += $i->quantidade * $i->produto->produto->peso_bruto;
		}
		return $soma;
	}

	public function somaDimensoes(){
		$data = [
			'comprimento' => 0,
			'altura' => 0,
			'largura' => 0
		];
		foreach($this->itens as $key => $i){
			if($i->produto->produto->comprimento == 0 || !$i->produto->produto->comprimento){
				$i->produto->produto->comprimento = 13;
			}
			if($i->produto->produto->largura == 0 || !$i->produto->produto->largura){
				$i->produto->produto->largura = 8;
			}
			if($i->produto->produto->altura == 0 || !$i->produto->produto->altura){
				$i->produto->produto->altura = 1;
			}
			if($i->produto->produto->comprimento > $data['comprimento']){
				$data['comprimento'] = $i->produto->produto->comprimento;
			}

			// if($i->produto->produto->altura > $data['altura']){
			$data['altura'] += $i->produto->produto->altura;
			// }

			if($i->produto->produto->largura > $data['largura']){
				$data['largura'] = $i->produto->produto->largura;
			}

			$data['largura'] = $data['largura'];
		}
		return $data;
	}

	public function somaDimensoesPorCep($cep){
		$data = [
			'comprimento' => 0,
			'altura' => 0,
			'largura' => 0
		];

		$itensPedido = ItemPedidoEcommerce::
		select('item_pedido_ecommerces.*')
		->join('produto_ecommerces', 'produto_ecommerces.id', '=',
			'item_pedido_ecommerces.produto_id')
		->where('item_pedido_ecommerces.pedido_id', $this->id)
		->where('produto_ecommerces.cep', $cep)
		->get();
		foreach($itensPedido as $key => $i){
			if($i->produto->produto->comprimento > $data['comprimento']){
				$data['comprimento'] = $i->produto->produto->comprimento;
			}

			// if($i->produto->produto->altura > $data['altura']){
			$data['altura'] += $i->produto->produto->altura;
			// }

			if($i->produto->produto->largura > $data['largura']){
				$data['largura'] = $i->produto->produto->largura;
			}

			$data['largura'] = $data['largura'];
		}
		return $data;
	}

	public function getCepsDoPedido($cepOrigem){
		$ceps = [];
		foreach($this->itens as $key => $i){
			if(!in_array($i->produto->cep, $ceps)){
				if($i->produto->cep != ""){
					array_push($ceps, $i->produto->cep);
				}
			}
		}

		return $ceps;
	}

}
