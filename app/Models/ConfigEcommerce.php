<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigEcommerce extends Model
{
	protected $fillable = [
		'nome', 'link', 'logo', 'rua', 'numero', 'bairro', 'cidade', 'cep', 'telefone',
		'email', 'link_facebook', 'link_twiter', 'link_instagram', 'frete_gratis_valor', 
		'mercadopago_public_key', 'mercadopago_access_token', 'funcionamento', 'latitude',
		'longitude', 'politica_privacidade', 'empresa_id', 'src_mapa', 'cor_principal', 
		'google_api', 'tema_ecommerce', 'uf', 'habilitar_retirada', 'desconto_padrao_boleto',
		'desconto_padrao_pix', 'desconto_padrao_cartao', 'api_token', 'usar_api', 'fav_icon', 
		'timer_carrossel', 'img_contato', 'cor_fundo', 'cor_btn', 'mensagem_agradecimento',
        'formas_pagamento', 'modelo_orcamento'
	];

	protected $appends = ['logo_url', 'contato_url', 'fav_url'];

	public function getLogoUrlAttribute()
    {
        if (!empty($this->logo)) {
            $image_url = asset('/ecommerce/logos/' . rawurlencode($this->logo));
        } else {
            $image_url = '';
        }
        return $image_url;
    }


    public function getContatoUrlAttribute()
    {
        if (!empty($this->img_contato)) {
            $image_url = asset('/ecommerce/assets/img/contato/' . rawurlencode($this->img_contato));
        } else {
            $image_url = '';
        }
        return $image_url;
    }

    public function getFavUrlAttribute()
    {
        if (!empty($this->fav_icon)) {
            $image_url = asset('/ecommerce/assets/img/favicon/' . rawurlencode($this->fav_icon));
        } else {
            $image_url = '';
        }
        return $image_url;
    }

    public static function formasPagamento(){
        return [
            'cartao' => 'Cartão de credito',
            'pix' => 'Pix',
            'boleto' => 'Boleto',
        ];
    }
}
