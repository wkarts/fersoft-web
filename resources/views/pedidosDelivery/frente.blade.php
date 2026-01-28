@extends('default.layout')
@section('content')

<style type="text/css">
	
	.ativo{
		background-color: #55C6BD;
		color: #fff;
	}
	.desativo{
		background-color: #EBEDF3;
		color: #000;
	}

	.card-xl-stretch:hover{
		cursor: pointer;
	}

	.btn-check {
		display: flex;
		align-items: center;
		justify-content: center;
		position: fixed;
		width: 40px;
		height: 40px;
		bottom: 15px;
		right: 10px;
		background-color: #55C6BD;
		color: #FFF;
		border: none;
		border-radius: 5px 5px 0 0;
		font-size: 20px;
		box-shadow: 1px 1px 2px #888;
		z-index: 1000;

	}

	.btn-list {
		display: flex;
		align-items: center;
		justify-content: center;
		position: fixed;
		width: 40px;
		height: 40px;
		bottom: 15px;
		right: 55px;
		background-color: #7337EE;
		color: #FFF;
		border: none;
		border-radius: 5px 5px 0 0;
		font-size: 20px;
		box-shadow: 1px 1px 2px #888;
		z-index: 1000;

	}

	.icone-check{
		color: white;
		font-size: 25px;
	}

	.bd{
		padding: 4px;
		border: 1px solid #e4e6ef;
		border-radius: 10px;
	}

	.bd2{
		padding: 14px;
		border: 1px solid #e4e6ef;
		border-radius: 10px;
		margin-top: 10px;
	}

	.border-card{
		padding: 4px;
		border: 1px solid #e4e6ef;
		border-radius: 10px;
	}

	.bd:hover{
		cursor: pointer;
	}
</style>

<div class="card card-custom gutter-b">
	<div class="card-body">
		<input type="hidden" value="{{ csrf_token() }}" id="_token">
		<input type="hidden" value="{{ $config->tipo_divisao_pizza }}" id="divide_pizza">
		<h2>Frente de Pedido de Delivery</h2>
		<input type="hidden" id="clientes" value="{{json_encode($clientes)}}" name="">
		<input type="hidden" id="categorias" value="{{json_encode($categorias)}}" name="">
		<input type="hidden" value="{{env('DIVISAO_VALOR_PIZZA')}}" id="DIVISAO_VALOR_PIZZA">

		<div class="row align-items-center">
			@if(!isset($pedido)) 
			<div class="col-12">
				<p class="text-danger">Informe o cliente primeiramente!!</p>
			</div>
			@endif
			@if(!isset($pedido))
			<div class="form-group validated col-sm-5 col-lg-5 col-10">
				<label class="col-form-label" id="">Cliente</label><br>
				<select class="form-control select2" style="width: 100%" id="kt_select2_3" name="cliente">
					<option value="null">Selecione o cliente</option>
					@foreach($clientes as $c)
					<option value="{{$c->id}}">{{$c->id}} - {{$c->nome}} ({{$c->celular}})</option>
					@endforeach
				</select>
			</div>

			<div class="col-sm-1 col-lg-1 col-2">
				<!-- Modal add cliente -->
				<a style="margin-top: 12px;" href="#" data-toggle="modal" data-target="#modal-cliente" class="btn btn-icon btn-circle btn-success">
					<i class="la la-plus"></i>
				</a>
			</div>
			@else
			<div class="form-group validated col-sm-5 col-lg-5 col-10"><br>
				<h5>Cliente: <strong class="text-info">{{$pedido->cliente->nome}} {{$pedido->cliente->sobre_nome}}</strong></h5>
				<h5>Celular: <strong class="text-info">{{$pedido->cliente->celular}}</strong></h5>
			</div>
			@endif

			<div class="form-group validated col-sm-4 col-lg-4 col-10">
				<label class="col-form-label" id="">Endereço</label><br>
				<select class="form-control custom-select" @if(!isset($pedido)) disabled @endif id="endereco" style="width: 100%" name="endereco">
					<option value="">Balcão</option>
					@if(isset($pedido))
					@foreach($pedido->cliente->enderecos as $e)
					<option value="{{$e->id}}"
						@if(isset($pedido))
						@if($pedido->endereco_id == $e->id)
						selected
						@endif
						@endif
						>{{$e->rua}}, {{$e->numero}} - {{$e->_bairro->nome}}
					</option>
					@endforeach
					@endif
				</select>
			</div>

			<div class="col-sm-1 col-lg-1 col-2">
				<!-- Modal add cliente -->
				<a data-toggle="modal" data-target="#modal-endereco" style="margin-top: 12px;" href="#" class="btn btn-icon btn-circle btn-info @if(!isset($pedido)) disabled @endif">
					<i class="la la-plus"></i>
				</a>
			</div>
			<div class="col-12">
				<h6 class="mt-4 float-right">Valor da entrega: <strong class="text-danger vl_entrega">R$ 0,00</strong></h6>
			</div>
		</div>


		<!-- Item -->

		<div class="card card-custom gutter-b">
			<div class="card-body">

				<div class="row">
					<div class="col-md-12">
						<div class="input-icon">
							<input autocomplete="off" type="text" name="pesquisa" class="form-control" value="{{{isset($pesquisa) ? $pesquisa : ''}}}"
							placeholder="Digite para buscar o produto ..." id="pesquisa">
							<span>
								<i class="fa fa-search"></i>
							</span>
						</div>
					</div>
				</div>

				<div class="row" style="height: 72px; overflow-x: auto; width: auto; white-space: nowrap">
					<div class="form-group validated col-sm-12 col-lg-12 col-12 col-sm-12" style="margin-top: 10px;">
						<a type="button" id="cat_todos" onclick="categoria('todos')" style="height: 40px; min-width: 80px;" class="label label-xl label-inline label-light-muted ativo">Todos</a>
						@foreach($categorias as $c)
						<a type="button" id="cat_{{$c->id}}" onclick="categoria('{{$c->id}}')" style="height: 40px; min-width: 80px;" class="label label-xl label-inline desativo">{{$c->nome}}</a>
						@endforeach

					</div>

				</div>

				<div class="row mt-6 prods">
					@foreach($produtos as $p)
					<div class="col-md-4 bd2" onclick="addItem('{{$p->id}}')">
						<div class="card-xl-stretch me-md-6">
							<a class="d-block bgi-no-repeat bgi-size-cover bgi-position-center card-rounded position-relative min-h-175px mb-5" style="background-image:url('{{$p->img}}')" data-fslightbox="lightbox-video-tutorials">
							</a>
							<div class="m-0">
								<a style="font-size: 20px" class="fs-2 text-dark fw-bold text-hover-primary text-dark lh-base">
									{{ $p->produto->nome }}
								</a>
								<div class="fw-semibold fs-5 text-gray-600 text-dark my-4" style="height: 50px;">
									{{$p->descricao}}
								</div>
								<div class="fs-6 fw-bold">
									<!--begin::Author-->
									<a class="text-gray-700 text-hover-primary">{{ $p->categoria->nome }}</a>
									@if(!$p->categoria->tipo_pizza)
									<span style="font-size: 20px" class="text-danger float-right">R$ {{ number_format($p->valor, 2, ',', '.')}} </span>
									@else

									<span style="font-size: 13.5px" class="text-danger float-right">
										R$
										@foreach($p->pizza as $key => $pz)
										{{ number_format($pz->valor, 2, ',', '.')}} 

										@if($key < sizeof($p->pizza)-1)
										|
										@endif
										@endforeach
									</span>

									@endif
								</div>
							</div>
						</div>
					</div>
					@endforeach
				</div>
			</div>
		</div>

		@isset($pedido)
		<input type="hidden" id="pedido_id" value="{{$pedido->id}}">

		@if(sizeof($pedido->itens) > 0)
		<button id="btn-finalizar-pedido" class="btn-check">
			<i class="icone-check la la-check"></i>
		</button>

		<button data-toggle="modal" data-target="#modal-list" id="btn-itens-pedido" class="btn-list">
			<i class="icone-check la la-list"></i>
		</button>
		@endif
		@endif

	</div>
</div>

<div class="modal fade" id="modal-list" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Itens do Pedido</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			
			<div class="modal-body">
				<div class="row mt-6 prods-pedido">

				</div>

				<h4 class="mt-6">Valor total <strong class="total-parcial text-success"></strong></h4>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-endereco" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Cadastrar Endereço</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<form method="post" action="/pedidosDelivery/novoEnderecoClienteCaixa">
				@csrf
				<div class="modal-body">
					<div class="row">

						<input type="hidden" name="pedido_id" value="{{ isset($pedido) ? $pedido->id : 0 }}">

						<div class="form-group validated col-sm-10 col-lg-10">
							<label class="col-form-label" id="">Rua</label>
							<div class="">
								<input required type="text" id="rua" name="rua" class="form-control" value="">
							</div>
						</div>
						<div class="form-group validated col-sm-2 col-lg-2">
							<label class="col-form-label" id="">Número</label>
							<div class="">
								<input required type="text" id="numero" name="numero" class="form-control" value="">
							</div>
						</div>
						<div class="form-group validated col-sm-4 col-lg-4">
							<label class="col-form-label" id="">Celular</label>
							<div class="">
								<input required type="text" id="celular" name="celular" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-sm-4 col-lg-4">
							<label class="col-form-label" id="">Bairro</label>
							<select required name="bairro_id" class="custom-select form-control">
								<option value="">Selecione o bairro</option>
								@foreach($bairros as $b)
								<option value="{{$b->id}}">{{$b->nome}} - R$ {{$b->valor_entrega}}</option>
								@endforeach
							</select>

						</div>

						<div class="form-group validated col-sm-4 col-lg-4">
							<label class="col-form-label" id="">Referência</label>
							<div class="">
								<input type="password" id="referencia" name="referencia" class="form-control" value="">
							</div>
						</div>

					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-cancelar-3" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Salvar</button>
				</div>
			</form>
		</div>
	</div>
</div>


<div class="modal fade" id="modal-cliente" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Cadastrar Cliente</h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<form method="post" action="/pedidosDelivery/novoClienteDeliveryCaixa">
				@csrf
				<div class="modal-body">
					<div class="row">

						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Nome</label>
							<div class="">
								<input required type="text" id="nome" name="nome" class="form-control" value="">
							</div>
						</div>
						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Sobre nome</label>
							<div class="">
								<input required type="text" id="sobre_nome" name="sobre_nome" class="form-control" value="">
							</div>
						</div>
						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Celular</label>
							<div class="">
								<input required type="text" id="celular" name="celular" class="form-control" value="">
							</div>
						</div>
						<div class="form-group validated col-sm-6 col-lg-6">
							<label class="col-form-label" id="">Senha</label>
							<div class="">
								<input type="password" id="senha" name="senha" class="form-control" value="">
							</div>
						</div>

					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-cancelar-3" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Salvar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-adicionais" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title title-modal">Adicionais do Produto</h5>
				<h6>Valor unitário <strong class="vl-unit text-info"></strong></h6>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			
			<div class="modal-body">
				<div class="row">

					<div class="col-md-12">
						<div class="input-icon">
							<input autocomplete="off" type="text" name="pesquisa-adicional" class="form-control" 
							placeholder="Digite para buscar o adicional ..." id="pesquisa-adicional">
							<span>
								<i class="fa fa-search"></i>
							</span>
						</div>
					</div>
				</div>

				<br>

				<div class="col-12">
					<div class="row adicionais">

					</div>
				</div>

				<br>
				<div class="row">

					<div class="col-lg-10 col-12">
						<label>Observação</label>
						<input type="text" id="observacao" placeholder="Observação" class="form-control">
					</div>

					<div class="col-lg-2 col-12">
						<label>Quantidade</label>
						<input type="tel" id="qtd" value="1" placeholder="Qtd" class="form-control money">
					</div>
				</div>
				<h6 class="mt-4">Valor do item <strong class="vl-item text-info"></strong></h6>

			</div>
			<div class="modal-footer">

				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">
					Fechar
					<i class="la la-close"></i>
				</button>
				<button type="submit" id="btn-add-item" class="btn btn-light-success font-weight-bold spinner-white spinner-right">
					Adicionar Produto
					<i class="la la-check"></i>
				</button>
			</div>

		</div>
	</div>
</div>

<div class="modal fade" id="modal-adicionais-pizza" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title title-modal">Adicionais para pizza</h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			
			<div class="modal-body">
				<div class="row">

					<div class="col-md-12">
						<div class="input-icon">
							<input autocomplete="off" type="text" name="pesquisa-adicional" class="form-control" 
							placeholder="Digite para buscar o adicional ..." id="pesquisa-adicional">
							<span>
								<i class="fa fa-search"></i>
							</span>
						</div>
					</div>
				</div>

				<br>

				<div class="col-12">
					<div class="row adicionais">

					</div>
				</div>

				<br>
				<div class="row">

					<div class="col-lg-10 col-12">
						<label>Observação</label>
						<input type="text" id="observacao2" placeholder="Observação" class="form-control">
					</div>

					<div class="col-lg-2 col-12">
						<label>Quantidade</label>
						<input type="tel" id="qtd2" value="1" placeholder="Qtd" class="form-control money">
					</div>
				</div>
				<h6 class="mt-4">Valor do item <strong class="vl-item text-info"></strong></h6>

			</div>
			<div class="modal-footer">

				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">
					Fechar
					<i class="la la-close"></i>
				</button>
				<button type="submit" id="btn-save-pizza" class="btn btn-light-success font-weight-bold spinner-white spinner-right">
					Adicionar Pizza
					<i class="la la-check"></i>
				</button>
			</div>

		</div>
	</div>
</div>

<div class="modal fade" id="modal-pizzas" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title title-modal">Produtos do tipo Pizza</h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			
			<div class="modal-body">

				<div class="row">

					<div class="form-group col-lg-3 col-6">
						<label>Tamanho da pizza</label>
						<select class="form-control" id="tamanho_pizza">
							<option value="">Selecione o tamanho</option>
							@foreach($tamanhos as $t)
							<option value="{{$t->id}}" data-qtdsabores="{{$t->maximo_sabores}}">{{$t->nome}}</option>
							@endforeach
						</select>
					</div>
					
					<div class="form-group col-lg-10 col-6">
						<span class="float-right">Selecione até <strong class="qtd_sabores text-info">1</strong> sabor(es)</span>
					</div>
				</div>
				<div class="row">

					<div class="col-md-12">
						<div class="input-icon">
							<input autocomplete="off" type="text" name="pesquisa-pizza" class="form-control" 
							placeholder="Digite para buscar a pizza ..." id="pesquisa-pizza">
							<span>
								<i class="fa fa-search"></i>
							</span>
						</div>
					</div>
				</div>

				<br>

				<div class="col-12">
					<div class="row pizzas">

					</div>
				</div>

				<br>
				
				<h6 class="mt-4">Valor do item <strong class="vl-item text-info">R$ 0,00</strong></h6>

			</div>
			<div class="modal-footer">

				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">
					Fechar
					<i class="la la-close"></i>
				</button>
				<button type="submit" id="btn-add-pizza" class="btn btn-light-success font-weight-bold spinner-white spinner-right">
					Adicionais para pizza
					<i class="la la-check"></i>
				</button>
			</div>

		</div>
	</div>
</div>

<div class="modal fade" id="modal-finalizar-pedido" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Finalizar Pedido</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<form method="post" action="/pedidosDelivery/finalizarFrente">
				@csrf
				<input type="hidden" name="pedido_id" value="{{ isset($pedido) ? $pedido->id : 0 }}">
				<input type="hidden" name="valor_entrega" id="valor_entrega" value="">
				<div class="modal-body">
					<div class="row">
						<div class="col-lg-6">
							<h4>Cliente: <strong class="text-success" id="cliente-nome"></strong></h4>
							<h4>Celular: <strong class="text-success" id="cliente-celular"></strong></h4>
						</div>
						<div class="col-lg-6">
							<h4>Endereço: <strong class="text-success" id="cliente-endereco"></strong></h4>
							<h4>Total do pedido: <strong class="text-success" id="total-pedido"></strong></h4>
							<h4>Valor da entrega: <strong class="text-success vl_entrega"></strong></h4>

						</div>
					</div>

					<div class="row">
						<div class="col-lg-4">
							<label>Forma de pagamento</label>
							<select required class="form-control" id="forma_pagamento" name="forma_pagamento">
								<option value="">--</option>
								@foreach(\App\Models\VendaCaixa::tiposPagamento() as $key => $tp)
								<option value="{{$key}}">{{ $key }} - {{ $tp }}</option>
								@endforeach
							</select>
						</div>
						<div class="col-lg-2 div-troco d-none">
							<div class="col-lg-12">
								<label>Troco para</label>
								<input type="tel" name="troco_para" id="troco_para" placeholder="Troco para" class="form-control money">
							</div>
						</div>
						<div class="col-lg-4">
							<label>Estado do pedido</label>
							<select class="form-control" name="estado_pedido" id="estado_pedido">
								<option value="novo">Novo</option>
								<option selected value="aprovado">Aprovado</option>
								<option value="aprovado">Aprovado</option>
								<option value="reprovado">Reprovado</option>
								<option value="finalizado">Finalizado</option>
							</select>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-lg-12">
							<label>Observação</label>
							<input type="text" name="observacao_pedido" id="observacao_pedido" placeholder="Observação" class="form-control">
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-add-pizza" class="btn btn-light-success font-weight-bold spinner-white spinner-right">
						Finalizar Pedido
						<i class="la la-check"></i>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection

@section('javascript')
<script type="text/javascript" src="/js/frentePedidoDeliveryPedido.js"></script>
@endsection
