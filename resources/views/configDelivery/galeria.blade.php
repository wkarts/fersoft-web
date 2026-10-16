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
<div class="ui-admin ui-commerce ui-commerce-configDelivery">

<div class="ui-admin-panel ui-commerce-panel gutter-b">
	<div class="ui-admin-panel-body ui-commerce-panel-body">
		<br>
		<div class="" id="kt_user_profile_aside" style="margin-left: 10px; margin-right: 10px;">
			<br>
			<h4>Galeria loja <strong class="text-danger">{{$config->nome}}</strong></h4>

			@if(sizeof($config->galeria) < 5)
			<form method="post" action="/configDelivery/saveImagem" enctype="multipart/form-data">

				@csrf
				<input type="hidden" name="id" value="{{ $config->id }}">
				<div class="row">
					<div class="col-xl-2"></div>
					<div class="col-xl-8">
						<div class="ui-commerce-section">
							<div class="ui-commerce-section-body">

								<div class="form-group row">
									<label class="col-xl-12 col-lg-12 col-form-label text-left">Imagem</label>
									<div class="col-lg-10 col-xl-6">

										<div class="image-input image-input-outline" id="kt_image_1">
											<div class="image-input-wrapper" style="background-image: url(/imgs/no_image.png)"></div>
											<label class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="change" data-toggle="tooltip" title="" data-original-title="Change avatar">
												<i class="fa fa-pencil icon-sm text-muted"></i>
												<input type="file" name="file" accept=".png, .jpg, .jpeg">
												<input type="hidden" name="profile_avatar_remove">
											</label>
											<span class="btn btn-xs btn-icon btn-circle btn-white btn-hover-text-primary btn-shadow" data-action="cancel" data-toggle="tooltip" title="" data-original-title="Cancel avatar">
												<i class="fa fa-close icon-xs text-muted"></i>
											</span>
										</div>


										<span class="form-text text-muted">.png, .jpg, .jpeg</span>
										@if($errors->has('file'))
										<div class="invalid-feedback">
											{{ $errors->first('file') }}
										</div>
										@endif
									</div>
								</div>

								<div class="row">
									<button type="submit" class="btn btn-success">
										<i class="la la-check"></i>
									Salvar Imagem</button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
			@endif
			<div class="ui-admin-panel ui-commerce-panel gutter-b" style="margin-top: 20px;">
				<div class="ui-admin-panel-body ui-commerce-panel-body">
					@if(sizeof($config->galeria) > 0)
					<div class="row">

						@foreach($config->galeria as $v => $g)
						<div class="col-xl-4 col-lg-6 col-md-6 col-sm-6">
							<!--begin::Card-->
							<div class="ui-admin-panel ui-commerce-panel gutter-b">
								@if(file_exists(public_path('imagens_loja_delivery/').$g->path))
								<img style="width: auto; height: 200px; border-radius: 5px;" src="/imagens_loja_delivery/{{$g->imagem}}">
								@else
								<img style="width: auto; height: 200px; border-radius: 5px;" src="/imgs/no_image.png" alt="image">
								@endif
								<a onclick='swal("Atenção!", "Deseja remover esta imagem?", "warning").then((sim) => {if(sim){ location.href="/configDelivery/deleteImagem/{{$g->id}}" }else{return false} })' href="#!" class="btn btn-danger">
									<i class="la la-trash"></i>
								Remover</a>
								<p class="text-info">Imagem {{$v+1}}</p>
							</div>
						</div>

						@endforeach
					</div>
					@else
					<h4 class="text-danger">Nenhum imagem cadastrada</h4>
					@endif
				</div>
			</div>

		</div>
	</div>
</div>

</div>
@endsection	