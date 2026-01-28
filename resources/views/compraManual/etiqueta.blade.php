@extends('default.layout')
@section('content')

<div class="card card-custom gutter-b">
	
	<div class="card-body">

		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<div class="card card-custom gutter-b example example-compact">
				<div class="card-header">

					<div class="col-xl-12">
						<div class="row">
							<div class="col-xl-12">
								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
									<br>
									<h4>Gerar etiqueta da compra: <strong class="text-info">#{{$compra->id}}</strong>
									</h4>
									<input type="hidden" id="padroes" value="{{json_encode($padrosEtiqueta)}}">
									<form method="post" action="/compras/etiquetaStore">

										<div class="row">
											<div class="col-lg-12">
												<h6 class="mt-3 mb-3">Selecione os itens para imprimir</h6>
												@foreach($compra->itens as $i)
												<div class="col-lg-12">

													<label class="checkbox">
														<input id="prod" type="checkbox" checked name="prod_select_{{$i->id}}"/>
														<span></span>
														<b style="margin-left: 3px;">{{$i->produto->nome}}</b>
													</label>
												</div>
												@endforeach
											</div>
										</div>
										<div class="row">
											<div class="form-group validated col-5">
												<label class="col-form-label">Modelos pré-definidos</label>
												<div class="input-group">

													<select class="form-control" id="modelo">
														<option value="">Selecione</option>
														@foreach($padrosEtiqueta as $p)
														<option value="{{$p->id}}">
															{{$p->nome}}
														</option>
														@endforeach
													</select>
													<a href="/produtos/verEtiquetasPadroes" class="btn btn-success btn-sm">
														<i class="la la-plus-circle icon-add"></i>
													</a>
												</div>
											</div>
										</div>

										@csrf

										<input type="hidden" value="{{$compra->id}}" name="compra_id">
										<div class="row">
											<div class="form-group validated col-sm-3 col-lg-3">
												<label class="col-form-label">Altura mm*</label>
												<div class="">
													<input type="text" class="form-control @if($errors->has('altura')) is-invalid @endif" name="altura" value="{{ old('altura') }}" id="alturam">
													@if($errors->has('altura'))
													<div class="invalid-feedback">
														{{ $errors->first('altura') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<label class="col-form-label">Largura mm*</label>
												<div class="">
													<input type="text" class="form-control @if($errors->has('largura')) is-invalid @endif" name="largura" value="{{ old('largura') }}" id="larguram">
													@if($errors->has('largura'))
													<div class="invalid-feedback">
														{{ $errors->first('largura') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<label class="col-form-label">Num. etiquetas por linha*</label>
												<div class="">
													<input type="text" class="form-control @if($errors->has('qtd_linhas')) is-invalid @endif" id="qtd_linhas" name="qtd_linhas" id="qtd_linhas" value="{{ old('qtd_linhas') }}">
													@if($errors->has('qtd_linhas'))
													<div class="invalid-feedback">
														{{ $errors->first('qtd_linhas') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<label class="col-form-label">Dist. entre etiquetas lateral mm*</label>
												<div class="">
													<input id="dist_lateral" type="text" class="form-control @if($errors->has('dist_lateral')) is-invalid @endif" name="dist_lateral" value="{{ old('dist_lateral') }}">
													@if($errors->has('dist_lateral'))
													<div class="invalid-feedback">
														{{ $errors->first('dist_lateral') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<label class="col-form-label">Dist. entre etiquetas topo mm*</label>
												<div class="">
													<input id="dist_topo" type="text" class="form-control @if($errors->has('dist_topo')) is-invalid @endif" name="dist_topo" value="{{ old('dist_topo') }}">
													@if($errors->has('dist_topo'))
													<div class="invalid-feedback">
														{{ $errors->first('dist_topo') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<label class="col-form-label">Tamanho da fonte*</label>
												<div class="">
													<input id="tamanho_fonte" type="text" class="form-control @if($errors->has('tamanho_fonte')) is-invalid @endif" name="tamanho_fonte" value="{{ old('tamanho_fonte') }}">
													@if($errors->has('tamanho_fonte'))
													<div class="invalid-feedback">
														{{ $errors->first('tamanho_fonte') }}
													</div>
													@endif
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<label class="col-form-label">Tamanho código de barras mm*</label>
												<div class="">
													<input id="tamanho_codigo" type="text" class="form-control @if($errors->has('tamanho_codigo')) is-invalid @endif" name="tamanho_codigo" value="{{ old('tamanho_codigo') }}">
													@if($errors->has('tamanho_codigo'))
													<div class="invalid-feedback">
														{{ $errors->first('tamanho_codigo') }}
													</div>
													@endif
												</div>
											</div>
										</div>
										<div>
											<p class="text-info">Os campos marcados serão impressos na etiqueta</p>
										</div>
										<div class="row">
											<div class="form-group validated col-sm-3 col-lg-3">
												<div class="">
													<label class="checkbox">
														<input id="nome_empresa" type="checkbox" checked="checked" name="nome_empresa"/>
														<span></span>
														<b style="margin-left: 3px;">Nome da empresa</b>
													</label>
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<div class="">
													<label class="checkbox">
														<input id="nome_produto" type="checkbox" checked="checked" name="nome_produto"/>
														<span></span>
														<b style="margin-left: 3px;">Nome do produto</b>
													</label>
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<div class="">
													<label class="checkbox">
														<input id="valor_produto" type="checkbox" checked="checked" name="valor_produto"/>
														<span></span>
														<b style="margin-left: 3px;">Valor do produto</b>
													</label>
												</div>
											</div>

											<div class="form-group validated col-sm-3 col-lg-3">
												<div class="">
													<label class="checkbox">
														<input id="cod_produto" type="checkbox" checked="checked" name="cod_produto"/>
														<span></span>
														<b style="margin-left: 3px;">Código do produto</b>
													</label>
												</div>
											</div>
											<div class="form-group validated col-sm-3 col-lg-3">
												<div class="">
													<label class="checkbox">
														<input id="codigo_barras_numerico" type="checkbox" checked="checked" name="codigo_barras_numerico"/>
														<span></span>
														<b style="margin-left: 3px;">Código de barras numérico</b>
													</label>
												</div>
											</div>
										</div>

										<div class="col-12">
											<p style="font-size: 17px; font-weight: bold" class="text-danger" id="obs"></p>
										</div>

										<div class="row">
											<div class="col-12">
												<button class="btn btn-info" type="submit">
													Gerar
												</button>
											</div>
										</div>
									</form>
									<br>
								</div>
							</div>
						</div>
						
					</div>
				</div>

			</div>
		</div>
	</div>

</div>

@section('javascript')
<script type="text/javascript">
	var MODELOS = []
	$(function () {
		MODELOS = JSON.parse($('#padroes').val())

	})
	$('#modelo').change(() => {
		$('#obs').html('')
		let id = $('#modelo').val()
		let p = MODELOS.filter((x) => { return x.id == id })
		p = p[0]
		console.log(p)
		$('#larguram').val(p.largura)
		$('#alturam').val(p.altura)
		$('#qtd_linhas').val(p.etiquestas_por_linha)
		$('#dist_lateral').val(p.distancia_etiquetas_lateral)
		$('#dist_topo').val(p.distancia_etiquetas_topo)
		$('#qtd_etiquetas').val(p.quantidade_etiquetas)
		$('#tamanho_fonte').val(p.tamanho_fonte)
		$('#tamanho_codigo').val(p.tamanho_codigo_barras)

		if(p.nome_produto){
			$('#nome_produto').attr('checked', true);
		}else{
			$('#nome_produto').removeAttr('checked');
		}

		if(p.nome_empresa){
			$('#nome_empresa').attr('checked', true);
		}else{
			$('#nome_empresa').removeAttr('checked');
		}

		if(p.codigo_produto){
			$('#cod_produto').attr('checked', true);
		}else{
			$('#cod_produto').removeAttr('checked');
		}

		if(p.valor_produto){
			$('#valor_produto').attr('checked', true);
		}else{
			$('#valor_produto').removeAttr('checked');
		}

		if(p.codigo_barras_numerico){
			$('#codigo_barras_numerico').attr('checked', true);
		}else{
			$('#codigo_barras_numerico').removeAttr('checked');
		}

		if(p.observacao != ''){
			$('#obs').html('Observação: ' + p.observacao)
		}

	})
</script>
@endsection
@endsection
