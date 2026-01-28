@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="row">

			@if(sizeof($mesasFechadas) > 0)
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
				<div class="row">
					<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
						<h4>Mesas com pedido de fechamento:</h4>

						@foreach($mesasFechadas as $m)
						<a href="/pedidos/verMesa/{{$m->mesa->id}}" target="_blank" class="btn btn-danger">Ver {{$m->mesa->nome}}</a>
						@endforeach
					</div>
				</div>
			</div>
			@endif

			@if(sizeof($mesasParaAtivar) > 0)
			<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
				<div class="row">
					<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">

						<h4>Mesas a serem ativadas:</h4>
						@foreach($mesasParaAtivar as $m)
						<a onclick='swal("Atenção!", "Deseja ativar esta mesa?", "warning").then((sim) => {if(sim){ location.href="/pedidos/ativarMesa/{{ $m->id }}" }else{return false} })' href="#!" class="btn btn-success">Ativar {{$m->mesa->nome}}</a>
						@endforeach

					</div>
				</div>
			</div>
			<hr>

			@endif
		</div>

		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12 mt-2">
			<div class="row">
				<a class="btn btn-lg btn-success" data-toggle="modal" data-target="#modal1">
					<i class="fa fa-tag"></i>Abrir Comanda
				</a>

				<a  href="/clientes/new" class="btn btn-lg btn-primary ml-1" data-toggle="modal" data-target="#modal2">
					<i class="fa fa-plus"></i>Novo Cliente
				</a>
			</div>
		</div>

		<form method="get" action="/pedidos/filtrar" class="mt-3">
			<div class="row align-items-center">

				<div class="form-group col-lg-2 mt-5">
					<input value="{{isset($comanda) ? $comanda : ''}}" type="" name="comanda" class="form-control" placeholder="Comanda">
				</div>

				<div class="form-group col-lg-3 mt-5">
					<input value="{{isset($nome) ? $nome : ''}}" type="" name="nome" class="form-control" placeholder="Nome cliente">
				</div>

				<div class="form-group col-lg-3 mt-5">
					<input value="{{isset($cpf_cnpj) ? $cpf_cnpj : ''}}" type="tel" name="cpf_cnpj" class="form-control cpf_cnpj" placeholder="CPF/CNPJ">
				</div>

				<div class="col-lg-2 col-xl-2">
					<button class="btn btn-light-primary px-6 mt-0 mb-2 font-weight-bold">Filtrar</button>
				</div>
			</div>

		</form>
		<hr>

		@isset($cliente)
		{{$cliente}}
		@endif
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			@if(count($pedidos) > 0)
			<h5 class="text-success">Comandas em verde já finalizadas</h5>

			<div class="row">
				@foreach($pedidos as $p)
				<div class="col-sm-4 col-lg-4 col-md-6">

					<div class="card card-custom gutter-b @if($p->status) green lighten-4 @endif">
						<div class="card-header text-dark">
							<h3 style="margin-top: 20px;" class="text-white">COMANDA:
								@if($p->comanda == '')
								<a class="btn btn-light-info" onclick="atribuir('{{$p->id}}', '{{$p->mesa->nome}}')" data-toggle="modal" data-target="#modal-comanda">Atribuir comanda</a>
								<h2><br></h2>
								@else
								<span class="">{{$p->comanda}}</span>
								@endif
							</h3>
						</div>
						<div class="card-body" style="height: 230px;">

							<h5>Total: <strong class="text-info">R$ {{number_format($p->somaItems(),2 , ',', '.')}}</strong></h5>
							<h5>Horário Abertura: <strong class="text-info">{{ \Carbon\Carbon::parse($p->data_registro)->format('H:i')}}</strong></h5>
							<h5>Total de itens: <strong class="text-info">{{count($p->itens)}}</strong></h5>
							<h5>Itens Pendentes: <strong class="text-info">{{$p->itensPendentes()}}</strong></h5>
							<h5>Mesa: 
								@if($p->mesa != null)
								<strong class="text-info">{{$p->mesa->nome}}</strong>
								@else
								<strong class="text-info">AVULSA</strong> 
								<a onclick="setarMesa('{{$p->id}}', '{{$p->comanda}}')" class="btn btn-primary" data-toggle="modal" data-target="#modal-set-mesa">
									setar
								</a>
								@endif
							</h5>

							<h5>Cliente: 
								@if($p->cliente != null)
								<strong class="text-success">{{$p->cliente->razao_social}} {{$p->cliente->cpf_cnpj}}</strong>
								@else
								--
								@endif
							</h5>

							@if($p->referencia_cliete != '')
							<h5 class="text-danger">Mesa QrCode</h5>
							@else
							<h5><br></h5>
							@endif

							
						</div>

						<div class="card-footer">
							<a class="btn btn-danger" style="width: 100%;" 
							onclick='swal("Atenção!", "Deseja desativar esta comanda? os dados não poderam ser retomados!", "warning").then((sim) => {if(sim){ location.href="/pedidos/desativar/{{ $p->id }}" }else{return false} })' href="#!"><i class="la la-times"></i> Desativar</a>
							<a href="/pedidos/ver/{{$p->id}}" style="width: 100%; margin-top: 5px;" class="btn btn-info">
								<i class="la la-list"></i>Ver Itens
							</a>
							
						</div>

					</div>


				</div>

				@endforeach


			</div>
			<div class="row">
				<div class="col s6 offset-s3">
					<a href="/pedidos/mesas" class="btn btn-lg btn-light-info">VER MESAS</a>
				</div>

			</div>
			@else


			<h4 class="center-align">Nenhuma comanda aberta!</h4>
			<a class="btn btn-lg btn-success" data-toggle="modal" data-target="#modal1">
				<i class="fa fa-tag"></i>Abrir Comanda
			</a>


			@endif

		</div>
	</div>
</div>


<div class="modal fade" id="modal1" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<form method="post" action="/pedidos/abrir">
		@csrf
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">ABRIR COMANDA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="form-group validated col-sm-3 col-lg-3">
							<label class="col-form-label" id="">Código da comanda</label>
							<div class="">
								<input type="text" id="comanda" name="comanda" class="form-control" value="">
							</div>
						</div>

						<div class="form-group col-lg-3 col-md-4 col-sm-6">
							<label class="col-form-label">Mesa</label>
							<div class="">
								<div class="input-group date">
									<select style="width: 100%;" class="custom-select form-control" id="kt_select2_1" name="mesa_id">
										<option value="null">Selecione a mesa</option>
										@foreach($mesas as $m)
										<option value="{{$m->id}}">{{$m->nome}}</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>

						<div class="form-group col-lg-6 col-md-4 col-sm-6">
							<label class="col-form-label">Cliente</label>
							<div class="">
								<div class="input-group">
									<select style="width: 80%;" class="form-control" id="kt_select2_3" name="cliente_id">
										<option value="null">Selecione o cliente</option>
										@foreach($clientes as $c)
										<option value="{{$c->id}}">{{$c->id}} - {{$c->razao_social}}/{{$c->nome_fantasia}} ({{$c->cpf_cnpj}})</option>
										@endforeach
									</select>
									<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modal2" onclick="saveAndOpen()">
										<i class="la la-plus-circle icon-add"></i>
									</button>

								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="form-group validated col-sm-12 col-lg-12">
							<label class="col-form-label" id="">Observação</label>

							<div class="">
								<input type="text" id="observacao" name="observacao" class="form-control" value="">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-corrigir-2-aux" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Abrir</button>
				</div>
			</div>
		</div>
	</form>
</div>

<div class="modal fade" id="modal2" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<form method="post" action="/pedidos/saveCliente">
		@csrf
		<div class="modal-dialog modal-xl" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Novo Cliente</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="form-group validated col-12 col-lg-3">
							<label class="col-form-label" id="">CPF/CNPJ</label>
							<div class="">
								<input type="tel" id="cpf_cnpj" name="cpf_cnpj" class="form-control cpf_cnpj" value="">
							</div>
						</div>

						<div class="form-group validated col-12 col-lg-5">
							<label class="col-form-label" id="">Nome</label>
							<div class="">
								<input required type="text" id="nome" name="nome" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-6 col-lg-2">
							<label class="col-form-label" id="">Limite crédito</label>
							<div class="">
								<input type="text" id="limite_venda" name="limite_venda" class="form-control money" value="">
							</div>
						</div>
						<input type="hidden" id="open" name="open" value="0">
						<div class="form-group validated col-6 col-lg-2">
							<label class="col-form-label" id="">Telefone</label>
							<div class="">
								<input type="tel" id="telefone" name="telefone" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-6 col-lg-2">
							<label class="col-form-label" id="">Celular</label>
							<div class="">
								<input type="tel" id="celular" name="celular" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-12 col-lg-5">
							<label class="col-form-label" id="">Rua</label>
							<div class="">
								<input type="text" id="rua" name="rua" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-6 col-lg-2">
							<label class="col-form-label" id="">Número</label>
							<div class="">
								<input type="tel" id="numero" name="numero" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-12 col-lg-3">
							<label class="col-form-label" id="">Bairro</label>
							<div class="">
								<input type="text" id="bairro" name="bairro" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-lg-4 col-md-5 col-sm-10">
							<label class="col-form-label text-left">Cidade</label>
							<select class="form-control select2" id="kt_select2_4" name="cidade_id" style="width: 100%;">
								<option value="">--</option>
								@foreach($cidades as $c)
								<option value="{{$c->id}}">
									{{$c->nome}} ({{$c->uf}})
								</option>
								@endforeach
							</select>
						</div>

						<div class="form-group validated col-12 col-lg-2">
							<label class="col-form-label" id="">CEP</label>
							<div class="">
								<input type="text" id="cep" name="cep" class="form-control" value="">
							</div>
						</div>

						<div class="form-group validated col-12 col-lg-5">
							<label class="col-form-label" id="">Complemento</label>
							<div class="">
								<input type="text" id="complemento" name="complemento" class="form-control" value="">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-corrigir-2-aux" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Salvar</button>
				</div>
			</div>
		</div>
	</form>
</div>

<div class="modal fade" id="modal-comanda" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<form method="post" action="/pedidos/atribuirComanda">

		@csrf
		<input type="hidden" id="pedido_id" name="pedido_id">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">ATRIBUIR COMANDA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="form-group validated col-sm-3 col-lg-3">
							<label class="col-form-label" id="">Código da comanda</label>
							<div class="">
								<input type="text" id="comanda" name="comanda" class="form-control" value="">
							</div>
						</div>

						<div class="form-group col-lg-4 col-md-4 col-sm-6">
							<label class="col-form-label">Mesa</label>
							<div class="">

								<input type="text" name="mesa" id="mesa_atribuida" class="form-control" disabled>

							</div>
						</div>

					</div>
					<div class="row">
						<div class="form-group validated col-sm-12 col-lg-12">
							<label class="col-form-label" id="">Observação</label>

							<div class="">
								<input type="text" id="observacao" name="observacao" class="form-control" value="">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-corrigir-2-aux" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Ok</button>
				</div>
			</div>
		</div>
	</form>
</div>


<div class="modal fade" id="modal-set-mesa" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<form method="post" action="/pedidos/atribuirMesa">

		<input type="hidden" name="_token" value="{{ csrf_token() }}">
		<input type="hidden" id="pedido_id_mesa" name="pedido_id">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">SETAR MESA COMANDA</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						x
					</button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="form-group validated col-sm-3 col-lg-3">
							<label class="col-form-label" id="">Código da comanda</label>
							<div class="">
								<input type="text" id="comanda_mesa" name="comanda" class="form-control" disabled value="">
							</div>
						</div>

						<div class="form-group col-lg-4 col-md-4 col-sm-6">
							<label class="col-form-label">Mesa</label>
							<div class="">
								<div class="input-group date">
									<select class="custom-select form-control" id="mesa" name="mesa">
										@foreach($mesas as $m)
										<option value="{{$m->id}}">{{$m->nome}}</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-corrigir-2-aux" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Setar</button>
				</div>
			</div>
		</div>
	</form>
</div>


@endsection	

@section('javascript')
<script type="text/javascript">
	$('#cpf_cnpj').blur(() => { 
		let doc = $('#cpf_cnpj').val()
		doc = doc.replace("/", "_")
		console.log(path + 'clientes/consultaCadastrado/'+doc)
		$.get(path + 'clientes/consultaCadastrado/'+doc)
		.done((res) => {
			console.log(res)
			if(res.id){
				swal("Alerta", "Cliente já cadastrado", "warning")
				$('#cpf_cnpj').val('')
				$('#modal2').modal('hide')
			}
		}).fail((err) => {
			console.log(err)
		})
	})

	function saveAndOpen(){
		$('#open').val(1)
	}

	@if(session()->has('cliente_session'))
	$('#modal1').modal('show')
	$('#kt_select2_3').val('{{session()->get("cliente_session")}}')
	@endif
</script>
@endsection