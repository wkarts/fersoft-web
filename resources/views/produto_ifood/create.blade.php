@extends('default.layout')
@section('content')

<style type="text/css">
	.img-template img{
		width: 300px;
		border: 1px solid #999;
		border-radius: 10px;
	}

	.img-template-active img{
		width: 300px;
		border: 3px solid green;
		border-radius: 10px;
	}

	.template:hover{
		cursor: pointer;
	}

	#btn_token:hover{
		cursor: pointer;
	}

	.search-prod label{
		margin-left: 10px;
		width: 100%;
		margin-top: 7px;
		font-size: 14px;
	}
</style>
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{ isset($item) ? route('product-ifood.update', [$item->id]) : route('product-ifood.store') }}" enctype="multipart/form-data">
					@isset($item)
					@method('PUT')
					@endif
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($item) ? "Editar": "Cadastrar" }}} Produto iFood</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							@if(!isset($produto))
							<p class="text-danger">*Atenção campo referência é obrigatório para cadastrar novo produto</p>
							@endif
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<div class="row">
										@if(isset($item))
										<div class="form-group validated col-sm-4 col-lg-4 col-12">
											<label class="col-form-label">Referência</label>
											<div class="">
												<input disabled value="{{$item->produto->nome}}" autocomplete="off" id="referencia" type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" name="" value="">
											</div>
										</div>

										<input type="hidden" name="referencia" value="{{$item->nome}}">
										@else
										<div class="form-group validated col-sm-4 col-lg-4 col-12">
											<label class="col-form-label">Referência</label>
											<div class="">
												<input autocomplete="off" id="referencia" type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" name="referencia" value="">

												<div class="search-prod" style="display: none">
												</div>
												@if($errors->has('referencia'))
												<div class="invalid-feedback">
													{{ $errors->first('referencia') }}
												</div>
												@endif
											</div>
										</div>
										@endif

										@if(isset($item))
										<input type="hidden" value="{{$item->id}}" id="produto_id" name="produto_id">
										@else
										<input type="hidden" value="0" id="produto_id" name="produto_id">
										@endif

										@if(!isset($item))
										<div class="col-sm-3 col-lg-3 mt-4">
											<label>Adicionar no cadastro principal de produtos:</label>

											<div class="switch switch-outline switch-info">
												<label class="">
													<input value="true" name="novo_produto" class="red-text" type="checkbox">
													<span class="lever"></span>
												</label>
											</div>
										</div>
										@endif

										<div class="form-group validated col-sm-4 col-lg-3 col-12">
											<label class="col-form-label">Nome para iFood</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{isset($item) ? $item->nome : old('nome') }}">

												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Valor</label>
											<div class="">
												<input id="valor" type="text" class="form-control @if($errors->has('valor')) is-invalid @endif" name="valor" value="{{isset($item) ? $item->valor : old('valor') }}">
												@if($errors->has('valor'))
												<div class="invalid-feedback">
													{{ $errors->first('valor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Estoque</label>
											<div class="">
												<input id="estoque" type="tel" class="form-control @if($errors->has('estoque')) is-invalid @endif" name="estoque" value="{{isset($item) ? $item->estoque : old('estoque') }}" data-mask="00000.00" data-mask-reverse="true">
												@if($errors->has('estoque'))
												<div class="invalid-feedback">
													{{ $errors->first('estoque') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-3 col-12">
											<label class="col-form-label">Código de barras</label>
											<div class="">
												<input id="codigo_barras" type="text" class="form-control @if($errors->has('codigo_barras')) is-invalid @endif" name="codigo_barras" value="{{isset($item) ? $item->ean : old('codigo_barras') }}">
												@if($errors->has('codigo_barras'))
												<div class="invalid-feedback">
													{{ $errors->first('codigo_barras') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-4 col-12">
											<label class="col-form-label">Categoria</label>
											<div class="">
												<select name="categoria" class="form-control @if($errors->has('categoria')) is-invalid @endif">
													<option value="">--</option>
													@foreach($categories as $c)
													<option @isset($item) @if($c->id == $item->categoria->id_ifood) selected @endif @endif value="{{$c->id}}">{{$c->name}}</option>
													@endforeach
												</select>
												@if($errors->has('categoria'))
												<div class="invalid-feedback">
													{{ $errors->first('categoria') }}
												</div>
												@endif
											</div>
										</div>


										<div class="form-group validated col-sm-12 col-lg-9">
											<label class="col-form-label">Descrição</label>
											<div class="">

												<textarea class="form-control" name="descricao" id="descricao" style="width:100%;height:200px;">{{isset($item) ? $item->descricao : old('descricao')}}</textarea>

												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
												</div>
												@endif
											</div>
										</div>

										<div class="col-lg-3">
											<label class="col-form-label col-12">Imagem principal</label>

											<div class="image-input image-input-outline" id="kt_image_1">
												<div class="image-input-wrapper" @if(isset($item) && $item->imagem != "") style="background-image: url({{$item->imagem}})" @else style="background-image: url(/imgs/no_image.png)" @endif></div>
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
											<div class="text-danger">
												{{ $errors->first('file') }}
											</div>
											@endif
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
								<a style="width: 100%" class="btn btn-danger" href="/ifood/products">
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

@section('javascript')
<script type="text/javascript">
	$('#referencia').keyup(() => {
		console.clear()
		let pesquisa = $('#referencia').val();

		if(pesquisa.length > 1){
			montaAutocomplete(pesquisa, (res) => {
				if(res){
					if(res.length > 0){
						montaHtmlAutoComplete(res, (html) => {
							$('.search-prod').html(html)
							$('.search-prod').css('display', 'block')
						})

					}else{
						$('.search-prod').css('display', 'none')
					}
				}else{
					$('.search-prod').css('display', 'none')
				}
			})
		}else{
			$('.search-prod').css('display', 'none')
		}
	})

	function montaAutocomplete(pesquisa, call){
		$.get(path + 'produtos/autocomplete', {pesquisa: pesquisa})
		.done((res) => {
			console.log(res)
			call(res)
		})
		.fail((err) => {
			console.log(err)
			call([])
		})
	}

	function montaHtmlAutoComplete(arr, call){
		let html = ''
		arr.map((rs) => {
			let p = rs.nome
			if(rs.grade){
				p += ' ' + rs.str_grade
			}
			if(rs.referencia != ""){
				p += ' | REF: ' + rs.referencia
			}
			if(parseFloat(rs.estoqueAtual) > 0){
				p += ' | Estoque: ' + rs.estoqueAtual
			}
			html += '<label onclick="selectProd('+rs.id+')">'+p+'</label>'
		})
		call(html)
	}

	function selectProd(id){

		let lista_id = $('#lista_id').val();
		$.get(path + 'produtos/autocompleteProduto', {id: id, lista_id: lista_id})
		.done((res) => {
			let PRODUTO = res
			if(PRODUTO.ifood_id != ""){
				$('#referencia').val("")
				swal("Alerta", "Este produto já esta referênciado!", "warning")
				$('#produto_id').val('0')
			}else{
				$('#produto_id').val(PRODUTO.id)
				let nome = PRODUTO.nome
				if(PRODUTO.referencia != ""){
					nome += ' | REF: ' + PRODUTO.referencia
				}

				$('#nome').val(nome)
				$('#referencia').val(nome)
				$('#valor').val(parseFloat(PRODUTO.valor_venda).toFixed(2).replace(".", ","))
				$('#codigo_barras').val(PRODUTO.codBarras != 'SEM GTIN' ? PRODUTO.codBarras : '')


				if(parseFloat(PRODUTO.estoqueAtual) > 0){
					$('#estoque').val(PRODUTO.estoqueAtual)
				}
			}
		})
		.fail((err) => {
			console.log(err)
			swal("Erro", "Erro ao encontrar produto", "error")
		})
		$('.search-prod').css('display', 'none')
	}
</script>
@endsection
@endsection