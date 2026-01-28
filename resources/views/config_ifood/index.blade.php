@extends('default.layout')
@section('css')
<style type="text/css">
	.title{
		color: #999;
	}
</style>
@endsection
@section('content')

<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="">
			<div class="col-12">
				
				@if($dataMerchant != null)
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h5 class="title">Detalhes da loja</h5>
						</div>
						<div class="card-body">
							<h5>Operação: <strong>{{ $dataMerchant->operation }}</strong></h5>
							<h5>Disponibilidade: 
								@if($dataMerchant->available)
								<i class="la la-check text-success"></i>
								@else
								<i class="la la-ban text-danger"></i>
								@endif
							</h5>
							<h5>Estado: <strong>{{ $dataMerchant->state }}</strong></h5>
						</div>
					</div>
				</div>
				@endif

				<div class="col-12 mt-4">
					<div class="card">
						<div class="card-header">
							<h5 class="title">Interrupções</h5>
						</div>
						<div class="card-body">
							<button class="btn btn-info" data-toggle="modal" data-target="#modal-interrupcao">
								<i class="la la-plus"></i>
								Nova interrupção
							</button>

							<div class="table-responsive">
								<table class="table">
									<thead>
										<tr>
											<th>Início</th>
											<th>Fim</th>
											<th>Descrição</th>
											<th></th>
										</tr>
									</thead>
									<tbody>
										@foreach($dataInterruptions as $item)
										<tr>
											<td>{{ \Carbon\Carbon::parse($item->start)->format('d/m/y H:i') }}</td>
											<td>{{ \Carbon\Carbon::parse($item->end)->format('d/m/y H:i') }}</td>
											<td>{{ $item->description }}</td>
											<td>
												<a title="Remover" class="btn btn-sm btn-danger" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/ifood/deleteInterruption/{{ $item->id }}" }else{return false} })' href="#!">
													<i class="la la-trash"></i>	
												</a>
											</td>
										</tr>
										@endforeach
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<br>

	</div>
</div>

<div class="modal fade" id="modal-interrupcao" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<form method="post" action="/ifood/interrupcao" class="modal-content">
			@csrf
			<div class="modal-header">
				<h5 class="modal-title">Nova interrupção</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-lg-6">
						<label>Inicio</label>
						<input required type="datetime-local" name="inicio" class="form-control">
					</div>

					<div class="col-lg-6">
						<label>Fim</label>
						<input required type="datetime-local" name="fim" class="form-control">
					</div>

					<div class="col-lg-12">
						<label>Descrição</label>
						<input required type="text" name="descricao" class="form-control">
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="submit" class="btn btn-light-success font-weight-bold" >Salvar</button>
			</div>
		</form>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">

</script>
@endsection


