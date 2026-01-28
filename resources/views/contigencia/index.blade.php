@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<div class="card-body">

		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">


			<label class="@if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">Registros: <strong class="text-success">{{sizeof($data)}}</strong></label>
			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
				<div class="form-group col-lg-3 col-md-4 col-sm-6">
					<a href="/contigencia/create" class="btn btn-success">
						<i class="la la-plus"></i>
						Ativar contigência
					</a>
					
				</div>
			</div>

			<div class="row @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
				<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
					<table class="table">
						<thead>
							<tr>
								<th>Data</th>
								<th>Motivo</th>
								<th>Tipo</th>
								<th>Documento</th>
								<th>Status</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							@foreach($data as $item)
							<tr>
								<td>{{ __date($item->created_at) }}</td>
								<td>{{ $item->motivo }}</td>
								<td>{{ $item->tipo }}</td>
								<td>{{ $item->documento }}</td>
								<td>
									@if($item->status)
									<i class="la la-check text-success"></i>
									@else
									<i class="la la-close text-danger"></i>
									@endif
								</td>
								<td>
									@if($item->status)
									<a href="{{ route('contigencia.desactive', [$item->id]) }}" class="btn btn-danger btn-sm">Desativar</a>
									@endif
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