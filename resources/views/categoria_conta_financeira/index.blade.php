@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-12">

				<a href="/contaFinanceira" class="btn btn-lg btn-info">
					<i class="fa la-arrow-left"></i>Voltar
				</a>
				<a href="/categoriaContaFinanceira/new" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Nova Categoria
				</a>
				
			</div>
		</div>
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>

			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div class="col-xl-12">
					<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

						<table class="datatable-table" style="max-width: 100%; overflow: scroll">
							<thead class="datatable-head">
								<tr class="datatable-row" style="left: 0px;">
									<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">NOME</span></th>
									<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">TIPO</span></th>

									<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">AÇÕES</span></th>
								</tr>
							</thead>
							<tbody id="body" class="datatable-body">
								@foreach($data as $item)
								<tr class="datatable-row">
									<td class="datatable-cell"><span class="codigo" style="width: 250px;" id="id">{{$item->nome}}</span>
									</td>
									<td class="datatable-cell"><span class="codigo" style="width: 150px;" id="id">{{ strtoupper($item->tipo) }}</span>
									</td>


									<td class="datatable-cell">
										<span class="codigo" style="width: 200px;" id="id">
											<a class="btn btn-warning" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/categoriaContaFinanceira/edit/{{ $item->id }}" }else{return false} })' href="#!">
												<i class="la la-edit"></i>	
											</a>
											<a class="btn btn-danger" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/categoriaContaFinanceira/delete/{{ $item->id }}" }else{return false} })' href="#!">
												<i class="la la-trash"></i>	
											</a>

											<a class="btn btn-info" href="/categoriaContaFinanceira/newSub/{{ $item->id }}" >
												<i class="la la-folder-plus"></i>	
											</a>

										</span>
									</td>

									@foreach($item->subcategorias as $sub)
									<tr class="datatable-row ml-6">
										<td class="datatable-cell"><span class="codigo" style="width: 250px;" id="id">{{$sub->nome}}</span>
										</td>
										<td class="datatable-cell">
											<span class="codigo" style="width: 200px;" id="id">
												<a class="btn btn-warning" onclick='swal("Atenção!", "Deseja editar este registro?", "warning").then((sim) => {if(sim){ location.href="/categoriaContaFinanceira/editSub/{{ $sub->id }}" }else{return false} })' href="#!">
													<i class="la la-edit"></i>	
												</a>
												<a class="btn btn-danger" onclick='swal("Atenção!", "Deseja remover este registro?", "warning").then((sim) => {if(sim){ location.href="/categoriaContaFinanceira/deleteSub/{{ $sub->id }}" }else{return false} })' href="#!">
													<i class="la la-trash"></i>	
												</a>

											</span>
										</td>
									</tr>

									@endforeach
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

@endsection