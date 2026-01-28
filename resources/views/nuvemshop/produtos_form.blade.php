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
				<form method="post" action="/nuvemshop/saveProduto" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($produto) ? $produto->id : 0 }}}">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">{{{ isset($produto) ? "Editar": "Cadastrar" }}} Produto</h3>
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
										@if(isset($prodBd) && $prodBd != null)
										<div class="form-group validated col-sm-4 col-lg-4 col-12">
											<label class="col-form-label">Referência</label>
											<div class="">
												<input disabled value="{{$prodBd->nome}}" autocomplete="off" id="referencia" type="text" class="form-control @if($errors->has('referencia')) is-invalid @endif" name="" value="">
											</div>
										</div>

										<input type="hidden" name="referencia" value="{{$prodBd->nome}}">
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

										@if(isset($prodBd) && $prodBd != null)
										<input type="hidden" value="{{$prodBd->id}}" id="produto_id" name="produto_id">
										@else
										<input type="hidden" value="0" id="produto_id" name="produto_id">
										@endif
										<div class="form-group validated col-sm-4 col-lg-3 col-12">
											<label class="col-form-label">Nome para ecommerce</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{isset($produto) ? $produto->name->pt : old('nome') }}">

												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Preço</label>
											<div class="">
												<input id="valor" type="text" class="form-control @if($errors->has('valor')) is-invalid @endif" name="valor" value="{{isset($produto) ? $produto->variants[0]->price : old('valor') }}">
												@if($errors->has('valor'))
												<div class="invalid-feedback">
													{{ $errors->first('valor') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Preço promocional</label>
											<div class="">
												<input id="valor_promocional" type="text" class="form-control @if($errors->has('valor_promocional')) is-invalid @endif money" name="valor_promocional" value="{{isset($produto) ? $produto->variants[0]->promotional_price : old('valor_promocional') }}">
												@if($errors->has('valor_promocional'))
												<div class="invalid-feedback">
													{{ $errors->first('valor_promocional') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Estoque</label>
											<div class="">
												<input id="estoque" type="text" class="form-control @if($errors->has('estoque')) is-invalid @endif" name="estoque" value="{{isset($produto) ? $produto->variants[0]->stock : old('estoque') }}">
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
												<input id="codigo_barras" type="text" class="form-control @if($errors->has('codigo_barras')) is-invalid @endif" name="codigo_barras" value="{{isset($produto) ? $produto->variants[0]->barcode : old('codigo_barras') }}">
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
												<select name="categoria_id" class="form-control">
													<option value="">--</option>
													@foreach($categorias as $c)
													<option @isset($produto) @if($c->id == (isset($produto->categories[0]) ? $produto->categories[0]->id : '')) selected @endif @endif value="{{$c->id}}">{{$c->name->pt}}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Largura (cm)</label>
											<div class="">
												<input id="largura" type="text" class="form-control @if($errors->has('largura')) is-invalid @endif" name="largura" value="{{isset($produto) ? $produto->variants[0]->width : old('largura') }}">
												@if($errors->has('largura'))
												<div class="invalid-feedback">
													{{ $errors->first('largura') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Altura (cm)</label>
											<div class="">
												<input id="altura" type="text" class="form-control @if($errors->has('altura')) is-invalid @endif" name="altura" value="{{isset($produto) ? $produto->variants[0]->height : old('altura') }}">
												@if($errors->has('altura'))
												<div class="invalid-feedback">
													{{ $errors->first('altura') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Comprimento (cm)</label>
											<div class="">
												<input id="comprimento" type="text" class="form-control @if($errors->has('comprimento')) is-invalid @endif" name="comprimento" value="{{isset($produto) ? $produto->variants[0]->depth : old('comprimento') }}">
												@if($errors->has('comprimento'))
												<div class="invalid-feedback">
													{{ $errors->first('comprimento') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-4 col-lg-2 col-12">
											<label class="col-form-label">Peso (g)</label>
											<div class="">
												<input id="peso" type="text" data-mask="00000,000" data-mask-reverse="true" class="form-control @if($errors->has('peso')) is-invalid @endif" name="peso" value="{{isset($produto) ? $produto->variants[0]->weight : old('peso') }}">
												@if($errors->has('peso'))
												<div class="invalid-feedback">
													{{ $errors->first('peso') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Descrição</label>
											<div class="">

												<textarea class="form-control" name="descricao" id="descricao" style="width:100%;height:200px;">{{isset($produto) ? $produto->description->pt : old('descricao')}}</textarea>

												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
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
								<a style="width: 100%" class="btn btn-danger" href="/nuvemshop/produtos">
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
			if(PRODUTO.nuvemshop_id != ""){
				$('#referencia').val("")
				swal("Alerta", "Este produto já esta referênciado", "warning")
				$('#produto_id').val('0')
			}else{
				$('#produto_id').val(PRODUTO.id)
				let nome = PRODUTO.nome
				if(PRODUTO.referencia != ""){
					nome += ' | REF: ' + PRODUTO.referencia
				}

				$('#nome').val(nome)
				$('#referencia').val(nome)
				$('#valor').val(parseFloat(PRODUTO.valor_venda).toFixed(2))
				$('#codigo_barras').val(PRODUTO.codBarras)


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