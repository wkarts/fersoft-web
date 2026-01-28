@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{ route('apuracaoMensal.store') }}">
					@isset($item)
					@method('put')
					@endif
					<input type="hidden" name="id" value="{{{ isset($categoria) ? $categoria->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Apuração Mensal</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<p class="text-info">Selecione o funcionário para buscar os eventos de pagamento!</p>
									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-5 col-12">
											<label class="col-form-label" id="">Funcionário</label>
											<div class="input-group">

												@isset($item)
												<h4>{{$item->nome}}</h4>
												@else
												<select required class="form-control select2" id="kt_select2_3" name="funcionario_id">
													<option value="">Selecione o funcionário</option>
													@foreach($funcionarios as $f)
													<option value="{{$f->id}}">{{$f->id}} - {{$f->nome}} ({{$f->cpf}})</option>
													@endforeach
												</select>
												@endif

											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Mês</label>
											<div class="">
												
												<select class="form-control" name="mes">
													@foreach(\App\Models\ApuracaoSalario::mesesApuracao() as $key => $m)
													<option value="{{$m}}" @if($key == $mesAtual) selected @endif>{{ ($m) }}</option>
													@endforeach
												</select>
												
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Ano</label>
											<div class="">
												
												<select class="form-control" name="ano">
													@foreach(\App\Models\ApuracaoSalario::anosApuracao() as $key => $a)
													<option value="{{$a}}">{{ strtoupper($a) }}</option>
													@endforeach
												</select>
												
											</div>
										</div>
									</div>

									<div class="col-12 func-select d-none">

										<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded row">

											<table class="datatable-table table-dynamic" style="max-width: 100%; overflow: scroll;">

												<thead class="datatable-head">
													<tr class="datatable-row" style="left: 0px;">
														<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;"></span></th>
														<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Evento</span></th>
														<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Condição</span></th>
														<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor</span></th>
														<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Método</span></th>
													</tr>
												</thead>
												<tbody id="body" class="datatable-body">
													


												</tbody>
											</table>
										</div>
									</div>
									<br>

									<div class="row func-select d-none">
										<div class="form-group validated col-sm-12 col-lg-4">
											<label class="col-form-label" id="">Tipo de Pagamento</label>
											<select required class="custom-select form-control" id="forma" name="tipo_pagamento">
												<option value="">Selecione o tipo de pagamento</option>
												@foreach(App\Models\ApuracaoSalario::tiposPagamento() as $c)
												<option value="{{$c}}">{{$c}}</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-sm-12 col-lg-2">
											<label class="col-form-label" id="">Valor total</label>
											<input type="tel" class="form-control total money" name="valor_total">
										</div>

										<div class="form-group validated col-sm-12 col-lg-6">
											<label class="col-form-label" id="">Observação</label>
											<input type="text" class="form-control" name="observacao">
										</div>
									</div>

								</div>
							</div>
						</div>
					</div>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/apuracaoMensal">
									<i class="la la-close"></i>
									<span class="">Cancelar</span>
								</a>
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<button style="width: 100%" type="submit" class="btn btn-success">
									<i class="la la-check"></i>
									<span class="">Salvar</span>
								</button>
							</div>

						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	$(function(){
		$('#kt_select2_3').val('').change()
	})
	$('#kt_select2_3').change(() => {
		$('.datatable-body').html('')
		$('.func-select').addClass('d-none')
		let funcionario = $('#kt_select2_3').val()
		if(funcionario){
			$.get(path + 'apuracaoMensal/getEventos/'+funcionario)
			.done((html) => {
				console.clear();
				console.log(html)
				if(html == ""){
					swal("Erro", "Funcionário sem eventos de pagamento cadastrados!", "error")
				}else{
					$('.func-select').removeClass('d-none')
					$('.datatable-body').html(html)
					calcTotal()
				}

			}).fail((err) => {
				console.log(err)
			})
		}
	})

	function calcTotal(){
		console.clear()
		let total = 0
		$('.dynamic-form').each(function(){
			console.log($(this))
			var value = $(this).find('input').val();
			var condicao = $(this).find('.condicao_chave').val();
			console.log("condicao", condicao)
			if(value){
				value = value.replace(",", ".")
				value = parseFloat(value)
				if(condicao == "soma"){
					total += value
				}else{
					total -= value
				}

			}
		})
		setTimeout(() => {

			$('.total').val(total.toFixed(2).replace(".", ","))
			$('.value').addClass('money')
		},100)

	}
</script>
@endsection
