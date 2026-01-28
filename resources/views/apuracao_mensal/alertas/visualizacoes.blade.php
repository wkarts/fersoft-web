@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<input type="hidden" id="_token" value="{{ csrf_token() }}">

			<a href="/alertas" class="btn btn-info btn-sm float-right">Voltar</a>

			<h4 class="@if(env('ANIMACAO')) animate__animated @endif animate__backInRight mt-2">Visualizações alerta {{$data->titulo}}</h4>


			<div class="col-xl-12 @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">

				<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

					<table class="datatable-table" style="max-width: 100%; overflow: scroll">
						<thead class="datatable-head">
							<tr class="datatable-row" style="left: 0px;">
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 300px;">Empresa</span></th>
								<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Data</span></th>

							</tr>
						</thead>

						@php $soma = 0; @endphp
						<tbody class="datatable-body">
							@foreach($data->views as $item)

							<tr class="datatable-row">
								<td class="datatable-cell">
									<span class="codigo" style="width: 300px;">
										{{$item->empresa->nome}}
									</span>
								</td>

								<td class="datatable-cell">
									<span class="codigo" style="width: 120px;">
										{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
									</span>
								</td>

								
							</tr>
							@php $soma += $item->nota; @endphp

							@endforeach
						</tbody>

					</table>

				</div>
			</div>
		</div>
	</div>
</div>

@endsection	
