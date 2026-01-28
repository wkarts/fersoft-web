@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<input type="hidden" id="_token" value="{{ csrf_token() }}">

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Contadores/Parceiros</h4>

			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight">Total de registros: <strong class="text-success">{{sizeof($data)}}</strong></label>
			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="row">

					<a href="{{ route('rep-parceiro.create') }}" class="btn btn-success">
						<i class="la la-plus"></i>
						Novo Contador
					</a>

				</div>
			</div>

			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;">#</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Nome</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data cadastro</span></th>
								
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 350px;">Endereço</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Cidade</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Comissão %</span></th>
								
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 350px;">Ações</span></th>
							</tr>
						</thead>

						<tbody class="datatable-body">
							@foreach($data as $e)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 70px;">
										{{$e->id}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 150px;">
										{{$e->razao_social}}
									</span>
								</td>
								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										{{ \Carbon\Carbon::parse($e->created_at)->format('d/m/Y H:i') }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 350px;">
										{{$e->logradouro}}, {{$e->numero}} - {{$e->bairro}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										@if($e->cidade)
										{{$e->cidade->info}}
										@else
										--
										@endif
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 100px;">
										{{ number_format($e->comissao, 2, ',', '.') }}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 350px;">
										<a href="{{ route('rep-parceiro.edit', [$e->id]) }}" class="btn btn-sm btn-warning">
											Editar
										</a>

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

@endsection	
