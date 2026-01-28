@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				@isset($item)<form method="post" action="/alertas/update/{{$item->id}}"> @method('put') @else<form method="post" action="/alertas/store">@endif

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{isset($item) ? 'Editar' : 'Novo'}} Alerta</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-4">
											<label class="col-form-label">Título</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('titulo')) is-invalid @endif" name="titulo" value="{{{ isset($item) ? $item->titulo : old('titulo') }}}">
												@if($errors->has('titulo'))
												<div class="invalid-feedback">
													{{ $errors->first('titulo') }}
												</div>
												@endif
											</div>
										</div>
										<div class="col col-sm-3 col-lg-2">
											<br>
											<label>Status</label>

											<div class="switch switch-outline switch-success">
												<label class="">
													<input value="true" name="status" @isset($item) @if($item->status) checked @endif @else @if(old('status')) checked @endif @endif class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>


										<div class="form-group validated col-lg-2 col-md-2 col-sm-6">
											<label class="col-form-label">Prioridade</label>

											<select class="custom-select form-control" id="prioridade" name="prioridade">
												@foreach(App\Models\Aviso::prioridades() as $p)
												<option @if(isset($item)) @if($item->prioridade == $p) selected @endif @endif value="{{$p}}" 
													@if(old('prioridade') == $p)
													selected
													@endif>
													{{strtoupper($p)}}
												</option>
												@endforeach
											</select>

										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-12">
											<label class="col-form-label">Texto</label>
											<div class="">
												<textarea name="texto" id="texto" style="width: 100%;height:300px;">{{{ isset($item) ? $item->texto : old('texto') }}}</textarea>
												@if($errors->has('texto'))
												<div class="invalid-feedback">
													{{ $errors->first('texto') }}
												</div>
												@endif
											</div>
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
								<a style="width: 100%" class="btn btn-danger" href="/categorias">
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

<script type="text/javascript" src="/js/nicEdit-latest.js"></script>

<script type="text/javascript">
	new nicEditor({fullPanel : true}).panelInstance('texto',{hasPanel : true});
</script>
@endsection