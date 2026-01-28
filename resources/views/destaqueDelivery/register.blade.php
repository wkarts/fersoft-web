@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<form method="post" action="{{{ isset($item) ? '/destaquesDelivery/update': '/destaquesDelivery/save' }}}" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($item->id) ? $item->id : 0 }}}">


					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{isset($item) ? 'Editar' : 'Novo'}} Destaque</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-7 col-lg-4 col-12">
											<label class="col-form-label" id="">Loja (opcional)</label><br>
											<select class="form-control select2" style="width: 100%" id="kt_select2_1" name="loja_id">
												<option value="null">Selecione a loja</option>
												@foreach($lojas as $l)
												<option 
												@if(isset($item))
												@if($l->id == $item->empresa_id)
												selected
												@endif
												@else
												@if(old('loja_id') == $l->id)
												selected
												@endif
												@endif
												value="{{$l->id}}">{{$l->nome}}</option>
												@endforeach
											</select>
											
										</div>

										@isset($item)
										@if($item->produto)
										<input type="hidden" id="produto_id" value="{{$item->produto->id}}">
										<input type="hidden" id="produto_nome" value="{{$item->produto->produto->nome}}">
										@endif
										@endif

										<div class="form-group validated col-sm-7 col-lg-5 col-12 d-produto d-none">
											<label class="col-form-label" id="">Produto (opcional)</label><br>
											<select class="form-control select2" style="width: 100%" id="kt_select2_3" name="produto_id">
												<option value="">Digite para buscar o produto</option>
											</select>
										</div>

									</div>
								</div>

								<div class="row">
									

									<div class="col-sm-3 col-lg-2 mt-4">
										<label>Status:</label>

										<div class="switch switch-outline switch-success">
											<label class="">
												<input @if(isset($item->status) && $item->status) checked @endisset value="true" name="status" class="red-text" type="checkbox">
												<span class="lever"></span>
											</label>
										</div>
									</div>

									<div class="form-group col-sm-3 col-lg-2 col-12">
										<label class="col-form-label">Ordem</label>
										<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Informe um número para ordenar os destaque da pagina inicial"><i class="la la-info"></i></button>
										<div class="">
											<div class="input-group">
												<input type="text" name="ordem" class="form-control" value="@if(isset($item)) {{$item->ordem}} @else 0 @endif" id="ordem" data-mask="000"/>
											</div>
										</div>
									</div>

									<div class="col-lg-10 col-xl-3 mt-5">
										<br>
										<div class="image-input image-input-outline" id="kt_image_1">
											<div class="image-input-wrapper" @if(isset($item)) style="background-image: url(/destaques_delivery/{{$item->img}})" @else style="background-image: url(/imgs/no_image.png)" @endif></div>
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
										<p class="text-info">Proporção recomendada 800x480</p>
										@if($errors->has('file'))
										<div class="text-danger">
											{{ $errors->first('file') }}
										</div>
										@endif
									</div>


								</div>
							</div>
						</div>
					</div>
					<br>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/destaquesDelivery">
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

<script type="text/javascript">
	$('[data-toggle="popover"]').popover()

	var LOJAID = null
	$(function(){
		liberaProdutos();
		setTimeout(() => {
			$("#kt_select2_3").select2({
				minimumInputLength: 2,
				language: "pt-BR",
				placeholder: "Digite para buscar o produto",

				ajax: {
					cache: true,
					url: path + 'produtosDelivery/autocomplete',
					dataType: "json",
					data: function(params) {

						console.clear()
						var query = {
							pesquisa: params.term,
							loja_id: LOJAID
						};
						return query;
					},
					processResults: function(response) {
						console.log("response", response)
						var results = [];

						$.each(response, function(i, v) {
							var o = {};

							o.id = v.id;

							o.text = v.produto.nome
							o.value = v.id;
							results.push(o);
						});
						return {
							results: results
						};
					}
				}
			});
			$('.select2-selection__arrow').addClass('select2-selection__arroww')
			$('.select2-selection__arrow').removeClass('select2-selection__arrow')

			setTimeout(() => {
				if($('#produto_id').val()){
					var newOption = new Option($('#produto_nome').val(), $('#produto_id').val(), true, true);
					$("#kt_select2_3").append(newOption).trigger('change');
				}
			}, 10);

		}, 200);
	})
	$('#kt_select2_1').change(() => {
		liberaProdutos()
	})

	function liberaProdutos(){
		LOJAID = $('#kt_select2_1').val()
		if(LOJAID != "null"){
			$('.d-produto').removeClass('d-none')
		}else{
			$('.d-produto').addClass('d-none')
		}

	}

</script>
@endsection