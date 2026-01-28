@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">

	<div class="card-body">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<h4>Atualizar Percentual</h4>

			<h5>Produto: <strong class="text-danger">{{$tributacao->produto->nome}}</strong></h5>

			<form method="post" action="/percentualuf/updatePercentualSingle">
				<input type="hidden" name="id" value="{{$tributacao->id}}">
				@csrf
				<div class="row">
					<div class="form-group validated col-sm-3 col-lg-2">
						<label class="col-form-label">%ICMS</label>
						<div class="">
							<input type="text" id="percentual_icms" class="form-control @if($errors->has('percentual_icms')) is-invalid @endif" name="percentual_icms" value="{{{ isset($tributacao->percentual_icms) ? $tributacao->percentual_icms : old('percentual_icms') }}}" data-mask="00,00" data-mask-reverse="true">
							@if($errors->has('percentual_icms'))
							<div class="invalid-feedback">
								{{ $errors->first('percentual_icms') }}
							</div>
							@endif
						</div>
					</div>

					<div class="form-group validated col-sm-3 col-lg-2">
						<label class="col-form-label">%Redução BC</label>
						<div class="">
							<input id="percentual_red_bc" type="text" class="form-control @if($errors->has('percentual_red_bc')) is-invalid @endif" name="percentual_red_bc" value="{{{ isset($tributacao) ? $tributacao->percentual_red_bc : old('percentual_red_bc') }}}" data-mask="00,00" data-mask-reverse="true">
							@if($errors->has('percentual_red_bc'))
							<div class="invalid-feedback">
								{{ $errors->first('percentual_red_bc') }}
							</div>
							@endif
						</div>
					</div>

					<div class="form-group validated col-sm-3 col-lg-2">
						<label class="col-form-label">%FCP</label>
						<div class="">
							<input id="percentual_fcp" type="text" class="form-control @if($errors->has('percentual_fcp')) is-invalid @endif" name="percentual_fcp" value="{{{ isset($tributacao) ? $tributacao->percentual_fcp : old('percentual_fcp') }}}" data-mask="00,00" data-mask-reverse="true">
							@if($errors->has('percentual_fcp'))
							<div class="invalid-feedback">
								{{ $errors->first('percentual_fcp') }}
							</div>
							@endif
						</div>
					</div>

					<div class="form-group validated col-sm-3 col-lg-2">
						<label class="col-form-label">%ICMS interno</label>
						<div class="">
							<input id="percentual_icms_interno" type="text" class="form-control @if($errors->has('percentual_icms_interno')) is-invalid @endif" name="percentual_icms_interno" value="{{{ isset($tributacao) ? $tributacao->percentual_icms_interno : old('percentual_icms_interno') }}}" data-mask="00,00" data-mask-reverse="true">
							@if($errors->has('percentual_icms_interno'))
							<div class="invalid-feedback">
								{{ $errors->first('percentual_icms_interno') }}
							</div>
							@endif
						</div>
					</div>
				</div>

				<div class="row col-12">
					<a class="btn btn-light-danger" href="/percentualuf/verProdutos/{{$tributacao->uf}}">Cancelar</a>
					<input style="margin-left: 5px;" type="submit" value="Salvar" class="btn btn-light-success">
				</div>
			</form>
		</div>
	</div>
</div>

@endsection