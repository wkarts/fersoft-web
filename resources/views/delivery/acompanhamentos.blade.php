@extends('delivery.default')
@section('content')

<div class="row" id="anime" style="display: none;">
	<div class="col-12 col-md-4 offset-md-4 text-center py-5">
		<lottie-player src="/anime/success.json" background="transparent" speed="0.8" style="width: 100%; height: auto;" autoplay></lottie-player>
	</div>
</div>

<div id="content" style="display: block; padding-bottom: 140px;">
	<section class="py-4">
		<div class="container">
			<input type="hidden" id="maximo_adicionais" value="{{$config->maximo_adicionais}}">
			<input type="hidden" id="produto_id" value="{{$produto->id}}">
			<input type="hidden" id="_token" value="{{ csrf_token() }}">
			<input type="hidden" id="whats_delivery" value="{{ env('WHATSAPP_DELIVERY') }}">
			<input type="hidden" id="total_init" value="{{ $produto->valor }}">

			<div class="card-moderno mb-4">
				<h2 style="font-size: 24px; font-weight: 800; margin-bottom: 5px;">{{$produto->produto->nome}}</h2>
				<span style="color: #10b981; font-weight: 800; font-size: 20px; display: block; margin-bottom: 15px;">
					R$ <span id="valor_produto">{{$produto->valor}}</span>
				</span>

				@if($produto->descricao)
					<p style="font-size: 14px; color: var(--cor-texto-claro); margin-bottom: 8px;">
						<i class="fa fa-info-circle mr-1"></i> {{$produto->descricao}}
					</p>
				@endif

				@if($produto->ingredientes)
					<p style="font-size: 13px; color: #94a3b8; background-color: #f8fafc; padding: 10px; border-radius: 8px; margin: 0;">
						<strong>Ingredientes:</strong> {{$produto->ingredientes}}
					</p>
				@endif
			</div>

			<h3 style="font-size: 16px; font-weight: 700; color: var(--cor-texto-claro); margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;">
				Escolha os Adicionais
			</h3>

			@if(count($adicionais) > 0)
				<div class="row">
					@foreach($adicionais as $a)
						<div class="col-12 col-md-6 mb-3" onclick="selet_add({{$a->complemento}}, '{{$a->complemento->nome}}')">
							<div class="card-moderno" id="adicional_{{$a->complemento->id}}" style="padding: 15px; margin-bottom: 0; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s ease;">
								<span style="font-size: 16px; font-weight: bold; color: var(--cor-texto-escuro);">
									{{$a->complemento->nome}}
								</span>
								<span style="font-size: 15px; font-weight: 700; color: var(--cor-texto-claro);">
									+ R$ {{$a->complemento->valor}}
								</span>
							</div>
						</div>
					@endforeach
				</div>
			@else
				<div class="card-moderno text-center py-4">
					<p style="margin: 0; color: var(--cor-texto-claro); font-weight: 500;">Este item não possui adicionais disponíveis.</p>
				</div>
			@endif

			<div class="card-moderno mt-4" style="padding: 15px;">
				<h4 style="font-size: 15px; font-weight: 700; margin-bottom: 10px;">Alguma observação?</h4>
				<input type="text" class="form-control" id="observacao" placeholder="Ex: Sem cebola, maionese à parte, etc.">
			</div>

		</div>
	</section>
</div>

<div style="position: fixed; bottom: 0; left: 0; width: 100%; background: white; padding: 15px; box-shadow: 0 -5px 20px rgba(0,0,0,0.08); z-index: 1000; border-top: 1px solid #f1f5f9;">
	<div class="container">
		<div class="row align-items-center">
			
			<div class="col-4 col-md-3">
				<div class="d-flex align-items-center justify-content-center" style="background: #f1f5f9; border-radius: 12px; height: 50px; padding: 5px;">
					<input type="number" class="text-center" value="1" id="quantidade" style="border: none !important; background: transparent !important; font-weight: 800; font-size: 18px; width: 100%; padding: 0 !important; margin: 0 !important;">
				</div>
			</div>

			<div class="col-8 col-md-9">
				<button onclick="adicionar()" type="button" class="btn-principal d-flex justify-content-between align-items-center" style="height: 50px; padding: 0 20px !important;">
					<span><i class="fa fa-cart-plus mr-2"></i> Adicionar</span>
					<span>R$ <strong id="valor_total">{{$produto->valor}}</strong></span>
				</button>
			</div>

		</div>

		@if(env('WHATSAPP_DELIVERY') != '')
			<div class="row mt-2">
				<div class="col-12">
					<button onclick="pedirWhats('{{$produto->produto->nome}}')" type="button" class="btn btn-success w-100" style="padding: 10px !important; font-size: 13px !important;">
						<i class="fa fa-whatsapp mr-1"></i> Pedir Direto via WhatsApp
					</button>
				</div>
			</div>
		@endif
	</div>
</div>

<style>
	/* Classe injetada pelo seu JavaScript original ao selecionar um item */
	.bg-adicional-ativo {
		border-color: var(--cor-primaria) !important;
		background-color: #fff5f5 !important;
		box-shadow: 0 4px 12px rgba(234, 29, 44, 0.08) !important;
	}
</style>

@endsection