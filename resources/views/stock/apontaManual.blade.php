@extends('default.layout')
@section('content')
<style type="text/css">
	#focus-codigo:hover{
		cursor: pointer
	}

	.search-prod{
		position: absolute;
		top: 0;
		margin-top: 40px;
		left: 10;
		width: 100%;
		max-height: 200px;
		overflow: auto;
		z-index: 9999;
		border: 1px solid #eeeeee;
		border-radius: 4px;
		background-color: #fff;
		box-shadow: 0px 1px 6px 1px rgba(0, 0, 0, 0.4);
	}

	.search-prod label:hover{
		cursor: pointer;
	}

	.search-prod label{
		margin-left: 10px;
		width: 100%;
		margin-top: 7px;
		font-size: 14px;
		color: #000 !important;
	}
</style>
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12 mt-2">
				<form method="post" action="/estoque/saveApontamentoManual" enctype="multipart/form-data">

					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Novo Apontamento</h3>
						</div>
					</div>
					@csrf
					<input type="" autofocus="" style="border: none; width: 0px; height: 0px; " id="codBarras" name="">
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">

										{!! __view_locais_select() !!}

										<div class="form-group validated col-sm-6 col-lg-6 col-12">
											<label class="col-form-label" id="">Produto</label>
											<div class="input-group">
												<div class="input-group-prepend">
													<span class="input-group-text" id="focus-codigo">
														<li class="la la-barcode"></li>
													</span>
												</div>
												<input type="hidden" name="produto_id" id="produto_id">
												<input placeholder="Digite para buscar o produto" type="search" id="produto-search" class="form-control">
												<div class="search-prod" style="display: none">
												</div>
											</div>

										</div>
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Quantidade</label>
											<div class="">
												<input type="text" id="quantidad" class="form-control @if($errors->has('quantidade')) is-invalid @endif" name="quantidade" value="{{{ old('quantidade') }}}">
												@if($errors->has('quantidade'))
												<div class="invalid-feedback">
													{{ $errors->first('quantidade') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Tipo</label>
											<div class="">

												<select class="custom-select form-control" id="tipo" name="tipo">
													<option value="reducao">Redução de estoque</option>
													<option value="incremento">Incremento de estoque</option>
												</select>

												@if($errors->has('tipo'))
												<div class="invalid-feedback">
													{{ $errors->first('tipo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 div-motivo-reducao">
											<label class="col-form-label">Motivo</label>
											<div class="">

												<select class="custom-select form-control" id="motivo" name="motivo_reducao">
													@foreach(\App\Models\Estoque::listaMotivosReducao() as $l)
													<option value="{{$l}}">{{$l}}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-2 div-motivo-incremento d-none">
											<label class="col-form-label">Motivo</label>
											<div class="">

												<select class="custom-select form-control" id="motivo" name="motivo_incremento">
													@foreach(\App\Models\Estoque::listaMotivosIncremento() as $l)
													<option value="{{$l}}">{{$l}}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group validated col-lg-6 col-md-8 col-sm-10">
											<label class="col-form-label">Observação</label>

											<input type="text" id="observacao" class="form-control @if($errors->has('observacao')) is-invalid @endif" name="observacao" value="{{{ old('observacao') }}}">
											@if($errors->has('observacao'))
											<div class="invalid-feedback">
												{{ $errors->first('observacao') }}
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

@section('javascript')
<script type="text/javascript">

	(function(){
		selectTipo()
	})

	$('#focus-codigo').click(() => {
		$('#codBarras').focus()
	})

	$('#tipo').change(() => {
		selectTipo()
	})

	function selectTipo(){
		let tipo = $('#tipo').val()

		if(tipo == 'reducao'){
			$('.div-motivo-incremento').addClass('d-none')
			$('.div-motivo-reducao').removeClass('d-none')
		}else{
			$('.div-motivo-incremento').removeClass('d-none')
			$('.div-motivo-reducao').addClass('d-none')
		}
	}

	$('#codBarras').keyup((v) => {
		setTimeout(() => {
			let cod = v.target.value

			if(cod.length > 10){
				$('#codBarras').val('')
				getProdutoCodBarras(cod, (data) => {
					if(data){
						alert('sim')
					}else{

					}
				})
			}
		}, 500)
	})

	function getProdutoCodBarras(cod, data){
		$.ajax
		({
			type: 'GET',
			url: path + 'produtos/getProdutoCodBarras/'+cod,
			dataType: 'json',
			success: function(e){
				console.log(e)
				if(e){
					// $('#kt_select2_1').val(e.id).change()
					PTEMP = PRODUTO = e

					TIPODIMENSAO = e.tipo_dimensao

					let p = PRODUTO.nome
					if(PRODUTO.referencia != ""){
						p += ' | REF: ' + PRODUTO.referencia
					}
					if(parseFloat(PRODUTO.estoqueAtual) > 0){
						p += ' | Estoque: ' + PRODUTO.estoqueAtual
					}

					$('#produto_id').val(e.id)
					$('#produto-search').val(p)
					$('#produto-search').val()
				}else{
					swal("Erro", "Produto não encontrado", "error")
				}
			}, error: function(e){
				console.log(e)
			}
		});
	}

	$('#produto-search').keyup(() => {

		let pesquisa = $('#produto-search').val();

		let filial_id = $('#filial_id').val()
		if(!filial_id){
			swal("Alerta", "Primeiramente selecione o local", "warning")
			return;
		}
		if(pesquisa.length > 1){
			montaAutocomplete(pesquisa, filial_id, (res) => {
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

	function montaAutocomplete(pesquisa, filial_id, call){
		$.get(path + 'produtos/autocomplete', {pesquisa: pesquisa, filial_id: filial_id})
		.done((res) => {

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

			p += ' | R$ ' + parseFloat(rs.valor_venda).toFixed(casas_decimais).replace(".", ",")
			html += '<label onclick="selectProd('+rs.id+')">'+p+'</label>'
		})
		call(html)
	}

	function selectProd(id){

		let lista_id = $('#lista_id').val();
		$.get(path + 'produtos/autocompleteProduto', {id: id, lista_id: lista_id})
		.done((res) => {
			PTEMP = PRODUTO = res

			TIPODIMENSAO = res.tipo_dimensao

			let p = PRODUTO.nome
			if(PRODUTO.referencia != ""){
				p += ' | REF: ' + PRODUTO.referencia
			}
			if(parseFloat(PRODUTO.estoqueAtual) > 0){
				p += ' | Estoque: ' + PRODUTO.estoqueAtual
			}

			$('#produto_id').val(id)
			$('#produto-search').val(p)
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

