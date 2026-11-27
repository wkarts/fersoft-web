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
<div class="ui-admin ui-commerce ui-commerce-produtosDestaque">

<div class="content d-flex flex-column flex-column-fluid" id="kt_content">

	<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
		<div class="ui-admin-panel ui-commerce-panel gutter-b">
			<div class="col-lg-12">
				<!--begin::Portlet-->

				<form method="post" action="/produtosDestaque/save" enctype="multipart/form-data">

					<div class="ui-admin-panel ui-commerce-panel gutter-b">
						<div class="ui-admin-panel-head ui-commerce-panel-head">

							<h3 class="ui-commerce-title">Apontamento de produção</h3>
						</div>

					</div>
					@csrf

					<div class="row">
						<div class="col-xl-2"></div>
						<div class="col-xl-8">

							<div class="ui-commerce-section">
								<div class="ui-commerce-section-body">

									<div class="row">
										<div class="form-group validated col-lg-8 col-md-8 col-sm-10">
											<label class="col-form-label">Produto</label>
											<select class="form-control select2" id="kt_select2_2" name="produto_id">
												@foreach($produtos as $p)
												<option value="{{$p->id}}">{{$p->id}} - {{$p->produto->nome}}</option>
												@endforeach
											</select>
											@if($errors->has('produto'))
											<div class="invalid-feedback">
												{{ $errors->first('produto') }}
											</div>
											@endif
										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Categoria</label>
											<div class="">
												<select class="custom-select" name="categoria_id">
													@foreach($categorias as $c)
													<option value="{{$c->id}}">
														{{$c->nome}}
													</option>
													@endforeach
												</select>
											</div>
										</div>

									</div>

								</div>
							</div>
						</div>
					</div>
					<div class="ui-admin-panel-foot ui-commerce-panel-foot">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="">
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

</div>
@endsection


