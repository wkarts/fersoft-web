<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeliveryConfig;
use App\Models\CidadeDelivery;
use App\Models\DeliveryConfigGaleria;
use Illuminate\Support\Str;

class ConfigDeliveryController extends Controller
{
	protected $empresa_id = null;
	public function __construct(){
		$this->middleware(function ($request, $next) {
			$this->empresa_id = $request->empresa_id;
			$value = session('user_logged');
			if(!$value){
				return redirect("/login");
			}
			return $next($request);
		});
	}

	function sanitizeString($str){
		return preg_replace('{\W}', ' ', preg_replace('{ +}', ' ', strtr(
			utf8_decode(html_entity_decode($str)),
			utf8_decode('ÀÁÃÂÉÊÍÓÕÔÚÜÇÑàáãâéêíóõôúüçñ'),
			'AAAAEEIOOOUUCNaaaaeeiooouucn')));
	}

	public function index(){
		$config = DeliveryConfig::
		where('empresa_id', $this->empresa_id)
		->first();

		if($config != null)
			$config->tipos_pagamento = json_decode($config->tipos_pagamento);

		$cidades = CidadeDelivery::all();
		return view('configDelivery/index')
		->with('config', $config)
		->with('cidades', $cidades)
		->with('title', 'Configurar Parametros de Delivery');
	}


	public function save(Request $request){
		$this->_validate($request);
		$result = false;
		$nomeImagem = "";
		if($request->hasFile('file')){
    		//unlink anterior
			$file = $request->file('file');
			$nomeImagem = Str::random(20).".png";
			$upload = $file->move(public_path('delivery/logos'), $nomeImagem);
		}

		if(!isset($request->tipos_pagamento)){
			$request->tipos_pagamento = [];
		}

		if($request->id == 0){

			$result = DeliveryConfig::create([
				'link_face' => $request->link_face ?? '',
				'link_twiteer' => $request->link_twiteer ?? '',
				'link_google' => $request->link_google ?? '',
				'link_instagram' => $request->link_instagram ?? '',
				'telefone' => $this->sanitizeString($request->telefone),
				'rua' => $request->rua,
				'numero' => $request->numero,
				'bairro' => $request->bairro,
				'cep' => $request->cep,
				'tempo_medio_entrega' => $this->sanitizeString($request->tempo_medio_entrega),
				'valor_entrega' => str_replace(",", ".", $request->valor_entrega),
				'tempo_maximo_cancelamento' => $request->tempo_maximo_cancelamento,
				'nome' => $request->nome,
				'one_signal_app_id' => $request->one_signal_app_id ?? '',
				'one_signal_key' => $request->one_signal_key ?? '',
				'descricao' => $request->descricao,
				'latitude' => '',
				'cidade_id' => $request->cidade_id,
				'longitude' => '',
				'politica_privacidade' => $request->politica_privacidade ?? '',
				'mercadopago_public_key' => $request->mercadopago_public_key ?? '',
				'mercadopago_access_token' => $request->mercadopago_access_token ?? '',
				'valor_entrega_gratis' => $request->valor_entrega_gratis ?? 0,
				'maximo_sabores_pizza' => $request->maximo_sabores_pizza ?? 0,
				'pedido_minimo' => $request->pedido_minimo ? __replace($request->pedido_minimo) : 0,
				'valor_km' => $request->valor_km ? str_replace(",", ".", $request->valor_km) : 0,
				'usar_bairros' => $request->usar_bairros ? true : false,
				'maximo_km_entrega' => $request->maximo_km_entrega ? true : false,
				'notificacao_novo_pedido' => $request->notificacao_novo_pedido ? true : false,
				'autenticacao_sms' => $request->autenticacao_sms ? true : false,
				'confirmacao_pedido_cliente' => $request->confirmacao_pedido_cliente ? true : false,
				'maximo_adicionais' => $request->maximo_adicionais,
				'tipo_divisao_pizza' => $request->tipo_divisao_pizza,
				'maximo_adicionais_pizza' => $request->maximo_adicionais_pizza,
				'tipo_entrega' => $request->tipo_entrega,
				'empresa_id' => $this->empresa_id,
				'api_token' => $request->api_token ?? "",
				'logo' => $nomeImagem,
				'tipos_pagamento' => json_encode($request->tipos_pagamento)
			]);
		}else{

			$config = DeliveryConfig::
			where('empresa_id', $this->empresa_id)
			->first();

			if($request->hasFile('file')){

				if(file_exists(public_path('delivery/logos/').$config->logo) && $config->logo != '')
					unlink(public_path('delivery/logos/').$config->logo);
			}

			$config->tipos_pagamento = json_encode($request->tipos_pagamento);
			$config->link_face = $request->link_face ?? '';
			$config->link_twiteer = $request->link_twiteer ?? '';
			$config->link_google = $request->link_google ?? '';
			$config->link_instagram = $request->link_instagram ?? '';
			$config->telefone = $this->sanitizeString($request->telefone);
			$config->rua = $request->rua;
			$config->numero = $request->numero;
			$config->bairro = $request->bairro;
			$config->cep = $request->cep;
			$config->tempo_medio_entrega = $this->sanitizeString($request->tempo_medio_entrega);
			$config->valor_entrega = str_replace(",", ".", $request->valor_entrega);
			$config->tempo_maximo_cancelamento = $request->tempo_maximo_cancelamento;
			$config->nome = $request->nome;
			$config->descricao = $request->descricao;
			// $config->latitude = $request->latitude;
			$config->one_signal_app_id = $request->one_signal_app_id ?? '';
			$config->tipo_entrega = $request->tipo_entrega ?? '';
			$config->one_signal_key = $request->one_signal_key ?? '';
			$config->mercadopago_public_key = $request->mercadopago_public_key ?? '';
			$config->mercadopago_access_token = $request->mercadopago_access_token ?? '';
			// $config->longitude = $request->longitude;
			$config->politica_privacidade = $request->politica_privacidade ?? '';
			$config->valor_entrega_gratis = $request->valor_entrega_gratis ?? 0;
			$config->pedido_minimo = $request->pedido_minimo ? __replace($request->pedido_minimo) : 0;
			$config->valor_km = $request->valor_km ?? 0;
			$config->maximo_sabores_pizza = $request->maximo_sabores_pizza ?? 0;
			$config->maximo_km_entrega = $request->maximo_km_entrega ?? 0;
			$config->usar_bairros = $request->usar_bairros ? true : false;
			$config->notificacao_novo_pedido = $request->notificacao_novo_pedido ? true : false;
			$config->autenticacao_sms = $request->autenticacao_sms ? true : false;
			$config->confirmacao_pedido_cliente = $request->confirmacao_pedido_cliente ? true : false;
			$config->maximo_adicionais = $request->maximo_adicionais;
			$config->maximo_adicionais_pizza = $request->maximo_adicionais_pizza;
			$config->cidade_id = $request->cidade_id;
			$config->api_token = $request->api_token ?? "";
			$config->tipo_divisao_pizza = $request->tipo_divisao_pizza;
			if($nomeImagem != ""){
				$config->logo = $nomeImagem;
			}

			$result = $config->save();

		}

		if($result){
			session()->flash("mensagem_sucesso", "Configurado com sucesso!");
		}else{
			session()->flash('mensagem_erro', 'Erro ao configurar!');
		}

		return redirect('/configDelivery');
	}


	private function _validate(Request $request){
		$rules = [
			'link_face' => 'max:255',
			'link_twiteer' => 'max:255',
			'link_google' => 'max:255',
			'link_instagram' => 'max:255',
			'telefone' => 'required|max:20',
			'rua' => 'required|max:80',
			'numero' => 'required|max:15',
			'bairro' => 'required|max:30',
			'cep' => 'required|max:9',
			'tempo_medio_entrega' => 'required|max:10',
			'tempo_maximo_cancelamento' => 'required',
			'valor_entrega' => 'required',
			'nome' => 'required|max:30',
			'descricao' => 'required|max:200',
			// 'latitude' => 'required|max:10',
			// 'longitude' => 'required|max:10',
			'politica_privacidade' => 'max:400',
			'maximo_adicionais' => 'required',
			'maximo_adicionais_pizza' => 'required',
			'cidade_id' => 'required',
			'tipo_entrega' => 'required',
			'file' => $request->id == 0 ? 'required' : '',
		];

		$messages = [
			'link_face.max' => '255 caracteres maximos permitidos.',
			'link_twiteer.max' => '255 caracteres maximos permitidos.',
			'link_google.max' => '255 caracteres maximos permitidos.',
			'link_instagram.max' => '255 caracteres maximos permitidos.',
			'telefone.required' => 'O campo Telefone é obrigatório.',
			'telefone.max' => '20 caracteres maximos permitidos.',
			'rua.required' => 'O campo rua é obrigatório.',
			'rua.max' => '80 caracteres maximos permitidos.',
			'numero.required' => 'O campo número é obrigatório.',
			'numero.max' => '15 caracteres maximos permitidos.',
			'bairro.required' => 'O campo bairro é obrigatório.',
			'bairro.max' => '30 caracteres maximos permitidos.',
			'cep.required' => 'O campo cep é obrigatório.',
			'cep.max' => '9 caracteres maximos permitidos.',

			'tempo_medio_entrega.required' => 'O campo Tempo Medio de Entrega é obrigatório.',
			'tempo_maximo_cancelamento.required' => 'O campo Tempo Maximo de Cancelamento é obrigatório.',
			'tempo_medio_entrega.max' => '10 caracteres maximos permitidos.',
			'valor_entrega.required' => 'O campo Valor de Entrega é obrigatório.',
			'nome.required' => 'O campo Nome Exibição é obrigatório.',
			'nome.max' => '30 caracteres maximos permitidos.',
			'descricao.required' => 'O campo descrição é obrigatório.',
			'descricao.max' => '200 caracteres maximos permitidos.',
			'latitude.required' => 'O campo Latitude é obrigatório.',
			'latitude.max' => '10 caracteres maximos permitidos.',
			'longitude.required' => 'O campo Longitude é obrigatório.',
			'longitude.max' => '10 caracteres maximos permitidos.',
			'politica_privacidade.max' => '400 caracteres maximos permitidos.',
			'maximo_adicionais.required' => 'Campo obrigatório.',
			'maximo_adicionais_pizza.required' => 'Campo obrigatório.',
			'cidade_id.required' => 'Campo obrigatório.',
			'file.required' => 'Logo é obrigatória.',
			'tipo_entrega.required' => 'Campo obrigatório.',
		];
		$this->validate($request, $rules, $messages);
	}

	public function saveCoords(Request $request){
		$config = DeliveryConfig::
		where('empresa_id', $this->empresa_id)
		->first();

		$config->latitude = $request->lat;
		$config->longitude = $request->lng;

		$config->save();
		session()->flash("mensagem_sucesso", "Coordenadas definidas!");
		return redirect()->back();

	}

	public function galeria(){
		$config = DeliveryConfig::
		where('empresa_id', $this->empresa_id)
		->first();

		return view('configDelivery/galeria')
		->with('config', $config)
		->with('title', 'Galeria da loja');
	}

	public function saveImagem(Request $request){

		if(!is_dir(public_path('imagens_loja_delivery'))){
			mkdir(public_path('imagens_loja_delivery'), 0777, true);
		}
		$file = $request->file('file');
		$produtoDeliveryId = $request->id;

		$extensao = $file->getClientOriginalExtension();
		$nomeImagem = Str::random(20).".".$extensao;

		$upload = $file->move(public_path('imagens_loja_delivery'), $nomeImagem);

		$result = DeliveryConfigGaleria::create([
			'imagem' => $nomeImagem,
			'config_id' => $request->id
		]);

		if($result){
			session()->flash("mensagem_sucesso", "Imagem cadastrada com sucesso!");
		}else{

			session()->flash('mensagem_erro', 'Erro ao cadastrar produto!');
		}
		return redirect()->back();
	}

	public function deleteImagem($id){
		$imagem = DeliveryConfigGaleria
		::where('id', $id)
		->first();

		$public = env('SERVIDOR_WEB') ? 'public/' : '';
		if(file_exists($public . 'imagens_loja_delivery/'.$imagem->imagem))
			unlink($public . 'imagens_loja_delivery/'.$imagem->imagem);

		if($imagem->delete()){
			session()->flash('mensagem_sucesso', 'Imagem removida!');
		}else{
			session()->flash('mensagem_erro', 'Erro!');
		}
		return redirect()->back();

	}

}
