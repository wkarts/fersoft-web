@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<h5>Cliente: <strong>{{ $item->nome_cliente }}</strong></h5>
		<h5>Telefone: <strong>{{ $item->telefone_cliente }}</strong></h5>
		<h5>Estado: 
			@if($item->estado == 'fechado')
			<strong class="text-warning">FECHADO</strong>
			@elseif($item->estado == 'aberto')
			<strong class="text-success">ABERTO</strong>
			@elseif($item->estado == 'concluido')
			<strong class="text-info">CONCLUÍDO</strong>
			@else
			<strong class="text-danger">RECUSADO</strong>
			@endif

			<button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#modal-edit">
				<i class="la la-edit"></i>
			</button>
		</h5>

		<a onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/pedidosMesa/delete/{{ $item->id }}" }else{return false} })' href="#!" class="btn btn-danger">Remover atendimento</a>

		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<div class="row">
				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
					<div class="row">
						<div class="col-xl-12">

							<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

								<h4 class="mt-4">Itens do pedido</h4>
								<table class="datatable-table" style="max-width: 100%; overflow: scroll">
									<thead class="datatable-head">
										<tr class="datatable-row" style="left: 0px;">
											
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Produto</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Quantidade</span></th>
											<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Sabores</span></th>
											<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor Unitário</span></th>

											<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>

											<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Adicionais</span></th><th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Observação</span></th>
											<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">SubTotal</span></th>

											<th data-field="CompanyName" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Ações</span></th>
										</tr>
									</thead>

									<tbody id="body" class="datatable-body">
										@foreach($item->itens as $i)
										<tr class="datatable-row">
											
											<td class="datatable-cell">
												<span class="codigo" style="width: 200px;" id="id">
													#{{$i->produto->referencia == "" ? $i->produto->id : $i->produto->referencia}} {{$i->produto->produto->nome}}
												</span>
											</td>
											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													{{ (int)$i->quantidade }}
												</span>
											</td>
											<td class="datatable-cell">
												<span class="codigo" style="width: 200px;" id="id">
													@if(sizeof($i->sabores) > 0)
													@foreach($i->sabores as $s)
													{{$s->produto->produto->nome}}<br>

													@endforeach
													<label>Tamanho: {{$i->tamanho->nome()}} - {{$i->tamanho->pedacos}} pedaços</label>
													@else
													--
													@endif
												</span>
											</td>

											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													@if(count($i->sabores) == 0)
													{{ number_format($i->produto->valor, 2, ',', '.') }}
													@else
													--
													@endif
												</span>
											</td>
											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													@if($i->status)
													<span class="label label-xl label-inline label-light-success">OK</span>
													@else
													<span class="label label-xl label-inline label-light-danger">Pendente</span>
													@endif
												</span>
											</td>
											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													@if(count($i->itensAdicionais) > 0)

													@foreach($i->itensAdicionais as $key => $ad)
													{{$ad->adicional->nome()}} 
													@if($key < count($i->itensAdicionais)-1)
													|
													@endif
													@endforeach

													@else
													Nenhum 
													@endif
												</span>
											</td>
											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													{{ $i->obseracao != '' ? $i->obseracao : '--' }}
												</span>
											</td>
											
											<td class="datatable-cell">
												<span class="codigo" style="width: 100px;" id="id">
													{{number_format($i->valor, 2, ',', '.')}}
												</span>
											</td>
											
											<td class="datatable-cell">
												<span class="codigo" style="width: 200px;" id="id">

													<a class="btn btn-sm btn-danger" onclick='swal("Atenção!", "Deseja excluir este item do pedido?", "warning").then((sim) => {if(sim){ location.href="/pedidosMesa/deleteItem/{{ $i->id }}" }else{return false} })' href="#!">
														<i class="la la-trash"></i>				
													</a>

													@if(!$i->status)
													<a class="btn btn-sm btn-success" onclick='swal("Atenção!", "Deseja marcar este item como concluido?", "warning").then((sim) => {if(sim){ location.href="/pedidosMesa/alterarStatus/{{ $i->id }}" }else{return false} })' href="#!">
														<i class="la la-check"></i>				
													</a>
													@endif
												</span>
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

	</div>
</div>

<div class="modal fade" id="modal-edit" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Alterar estado</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<form method="post" action="/pedidosMesa/alterarEstado/{{$item->id}}">
				@csrf
				@method('put')
				<div class="modal-body">

					<div class="row">
						<div class="col-lg-4 form-group">
							<select name="estado" class="custom-select form-control">
								<option @if($item->estado == 'fechado') selected @endif value="fechado">Fechado</option>
								<option @if($item->estado == 'aberto') selected @endif value="aberto">Aberto</option>
								<option @if($item->estado == 'concluido') selected @endif value="concluido">Concluído</option>
								<option @if($item->estado == 'recusado') selected @endif value="recusado">Recusado</option>
							</select>
						</div>

					</div>

				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
					<button type="submit" id="btn-enviar-push" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Alterar</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection