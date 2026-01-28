@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<h3 style="font-weight: bold;">Alterar Estado da OS 
				<strong style="font-weight: bold;" class="text-success">{{$ordem->numero_sequencial}}</strong>
			</h3>
			<h4>Estado Atual: 
				@if($ordem->estado == 'pd') 
				<span class="label label-xl label-inline label-light-warning">PENDENTE</span>
				@elseif($ordem->estado == 'ap')
				<span class="label label-xl label-inline label-light-success">APROVADO</span>
				@elseif($ordem->estado == 'rp')
				<span class="label label-xl label-inline label-light-danger">REPROVADO</span>
				@else
				<span class="label label-xl label-inline label-light-info">FINALIZADO</span>
				@endif

			</h4>

			@if($ordem->estado != 'fz' && $ordem->estado != 'rp')

			<form method="post" action="/ordemServico/alterarEstado" id="form-os">
				<input type="hidden" name="_token" value="{{ csrf_token() }}">
				<input type="hidden" name="id" value="{{$ordem->id}}">
				<input type="hidden" id="gerar_conta_receber" name="gerar_conta_receber" value="0">

				<input type="hidden" id="valor_conta" name="valor_conta" value="">
				<input type="hidden" id="vencimento_conta" name="vencimento_conta" value="">
				<input type="hidden" id="categoria_conta_id" name="categoria_conta_id" value="">
				<input type="hidden" id="forma_pagamento_conta" name="forma_pagamento_conta" value="">

				<div class="row">
					<div class="form-group validated col-sm-4 col-lg-4">

						@if($ordem->estado == 'pd')
						<select class="custom-select form-control" id="novo_estado" name="novo_estado">
							<option value="ap">APROVADO</option>
							<option value="rp">REPROVADO</option>
						</select>

						@elseif($ordem->estado == 'ap')
						<select class="custom-select form-control" id="novo_estado" name="novo_estado">
							<option value="fz">FINALIZADO</option>
						</select>
						@endif
					</div>

					<div class="form-group validated col-sm-4 col-lg-4">
						<button type="button" class="btn btn-success btn-action">Alterar</button>
					</div>
				</div>

			</form>
			@elseif($ordem->estado == 'fz')
			<h5 class="text-success">Ordem de Serviço finalizada!</h5>

			<a href="/ordemServico" class="btn btn-info">Voltar</a>

			@else
			<h5 class="text-danger">Ordem de Serviço reprovada!</h5>

			<a href="/ordemServico" class="btn btn-danger">Voltar</a>

			@endif
		</div>

	</div>
</div>

<div class="modal fade" id="modal-conta" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<input type="hidden" id="id_cancela" name="">
					<div class="form-group validated col-sm-6 col-lg-4">
						<label class="col-form-label" id="">Valor</label>
						<input type="text" id="modal_valor_conta" class="form-control money" value="{{ moeda($ordem->total_os()) }}">
					</div>

					<div class="form-group validated col-sm-6 col-lg-4">
						<label class="col-form-label" id="">Vencimento</label>
						<input type="date" id="modal_vencimento_conta" class="form-control">
					</div>

					<div class="form-group validated col-sm-6 col-lg-4">
						<label class="col-form-label" id="">Categoria</label>
						<select class="custom-select form-control" id="modal_categoria_conta_id">
							<option value="">Selecione a categoria</option>
							@foreach($categoriasConta as $cat)
							<option value="{{$cat->id}}">{{$cat->nome}}</option>
							@endforeach
						</select>
					</div>

					<div class="form-group validated col-sm-12 col-lg-5">
						<label class="col-form-label" id="">Tipo de Pagamento</label>
						<select class="custom-select form-control" id="modal_forma_pagamento_conta">
							<option value="">Selecione o tipo de pagamento</option>
							@foreach(App\Models\ContaPagar::tiposPagamento() as $c)
							<option value="{{$c}}">{{$c}}</option>
							@endforeach
						</select>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="btn-cancelar-3" onclick="gerarConta()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Gerar Conta</button>
			</div>
		</div>
	</div>
</div>


@endsection
@section('javascript')
<script type="text/javascript">
	$('.btn-action').click(() => {
		let estado = $('#novo_estado').val()
		if(estado == 'ap'){
			swal({
				title: "Gerar conta a receber",
				text: "Deseja incluir este registro ao conta a receber?",
				icon: "warning",
				buttons: ["Não", 'Sim'],
				dangerMode: true,
			}).then((v) => {
				if (v) {
					$('#gerar_conta_receber').val(1)

					$('#modal-conta').modal('show')
				}else{
					$('#gerar_conta_receber').val(0)
					$('#form-os').submit()
				}
				
			})
		}else{
			$('#form-os').submit()
		}
	})

	function gerarConta(){
		let modal_valor_conta = $('#modal_valor_conta').val()
		let modal_vencimento_conta = $('#modal_vencimento_conta').val()
		let modal_categoria_conta_id = $('#modal_categoria_conta_id').val()
		let modal_forma_pagamento_conta = $('#modal_forma_pagamento_conta').val()

		if(modal_valor_conta && modal_vencimento_conta && modal_categoria_conta_id && modal_forma_pagamento_conta){
			$('#valor_conta').val(modal_valor_conta)
			$('#vencimento_conta').val(modal_vencimento_conta)
			$('#categoria_conta_id').val(modal_categoria_conta_id)
			$('#forma_pagamento_conta').val(modal_forma_pagamento_conta)

			$('#form-os').submit()
		}else{
			swal("Alerta", "Informe todos os campos", "warning")
		}
	}
</script>
@endsection