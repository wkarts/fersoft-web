@extends('default.layout')
@section('css')
@unless(config('ui_theme.active', false))
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endunless
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/ui-admin.css') }}?v={{ filemtime(public_path('css/ui-admin.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-commerce.css') }}?v={{ filemtime(public_path('css/ui-commerce.css')) }}">
@endpush
@section('content')
<div class="ui-admin ui-commerce ui-commerce-funcionamentoDelivery">

<div class="ui-admin-panel ui-commerce-panel gutter-b">

	<div class="ui-admin-panel-body ui-commerce-panel-body">
		<div class="">
			<div class="col-sm-12 col-lg-4 col-md-6 col-xl-4">
				@isset($funcionamento)
				<a href="/funcionamentoDelivery" class="btn btn-lg btn-success">
					<i class="fa fa-plus"></i>Novo Dia
				</a>
				@endisset

			</div>
		</div>
		<br>

		@if(isset($funcionamento) || count($dias) > 0)
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">

			<form method="post" action="/funcionamentoDelivery/save">
				@csrf
				<input type="hidden" name="id" value="{{{ isset($funcionamento->id) ? $funcionamento->id : 0 }}}">
				<div class="row align-items-center">
					<div class="form-group validated col-lg-3 col-md-3 col-sm-10">
						<label class="col-form-label">Dia da semana *</label>
						@if(!isset($funcionamento))
						<select class="custom-select form-control @if($errors->has('dia')) is-invalid @endif" id="dia" name="dia" required>
							@foreach($dias as $d)
							<option value="{{$d}}" @if(old('dia') == $d) selected @endif>{{$d}}</option>
							@endforeach
						</select>
						@if($errors->has('dia'))
						<div class="invalid-feedback d-block">{{ $errors->first('dia') }}</div>
						@endif
						@else
						<input type="text" class="form-control" name="dia" value="{{$funcionamento->dia}}" disabled="">
						@endif

					</div>
					<div class="form-group validated col-sm-2 col-lg-2">
						<label class="col-form-label">Horário início</label>
						<div class="">
							<input data-mask="00:00" data-mask-reverse="true" type="text" class="form-control" id="inicio" name="inicio" value="{{{ isset($funcionamento->inicio_expediente) ? $funcionamento->inicio_expediente : '18:00' }}}">
							@if($errors->has('inicio'))
							<div class="invalid-feedback">{{ $errors->first('inicio') }}</div>
							@endif
						</div>
					</div>
					<div class="form-group validated col-sm-2 col-lg-2">
						<label class="col-form-label">Horário fim</label>
						<div class="">
							<input data-mask="00:00" data-mask-reverse="true" type="text" class="form-control" id="fim" name="fim" value="{{{ isset($funcionamento->fim_expediente) ? $funcionamento->fim_expediente : '23:59' }}}">
							@if($errors->has('fim'))
							<div class="invalid-feedback">{{ $errors->first('fim') }}</div>
							@endif
						</div>
					</div>

					<div class="col-lg-2 col-xl-2 mt-2 mt-lg-0">
						<button style="margin-top: 12px;" class="btn btn-light-success px-6 font-weight-bold">Salvar</button>
					</div>
				</div>

			</form>
		</div>
		@else
		<div class="alert alert-light-danger mb-4">
			<strong>Todos os dias da semana já estão configurados.</strong>
			Para alterar horários ou status, use as ações da lista abaixo.
		</div>
		@endif

		<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

			<table class="datatable-table" style="max-width: 100%; overflow: scroll">
				<thead class="datatable-head">
					<tr class="datatable-row" style="left: 0px;">
						<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Dia</span></th>
						<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Inicio</span></th>
						<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Fim</span></th>
						<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Status</span></th>
						<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ações</span></th>
					</tr>
				</thead>
				<tbody id="body" class="datatable-body">
					@foreach($funcionamentos as $f)
					<tr class="datatable-row">
						<td class="datatable-cell">
							<span class="codigo" style="width: 150px;">
								{{ $f->dia }}
							</span>
						</td>
						<td class="datatable-cell">
							<span class="codigo" style="width: 100px;">
								{{ $f->inicio_expediente }}
							</span>
						</td>
						<td class="datatable-cell">
							<span class="codigo" style="width: 100px;">
								{{ $f->fim_expediente }}
							</span>
						</td>
						<td class="datatable-cell">
							<span class="codigo" style="width: 100px;">
								@if($f->ativo)
								<span class="label label-xl label-inline label-light-success">ATIVO</span>
								@else
								<span class="label label-xl label-inline label-light-danger">DESATIVADO</span>
								@endif
							</span>
						</td>

						<td class="datatable-cell">
							<span class="codigo" style="width: 150px;">

								<a href="/funcionamentoDelivery/edit/{{ $f->id }}" class="btn btn-sm btn-warning">
									<i class="la la-edit"></i>					
								</a>

								@if($f->ativo)
								<a title="desativar" href="/funcionamentoDelivery/alterarStatus/{{ $f->id }}" class="btn btn-sm btn-danger">
									<i class="la la-times"></i>				
								</a>
								@else
								<a href="/funcionamentoDelivery/alterarStatus/{{ $f->id }}" class="btn btn-sm btn-success">
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
@endsection	