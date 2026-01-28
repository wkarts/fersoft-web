@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<form method="post" @isset($etiqueta) action="/produtos/updateEtiqueta" @else action="/produtos/saveEtiqueta" @endif>
					<input type="hidden" name="id" value="{{{ isset($etiqueta) ? $etiqueta->id : 0 }}}">
					<br>
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{{ isset($etiqueta) ? "Editar": "Cadastrar" }}} Etiqueta</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-5 col-lg-5">
											<label class="col-form-label">Nome</label>
											<div class="">
												<input id="nome" type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="nome" value="{{{ isset($etiqueta) ? $etiqueta->nome : old('nome') }}}">
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Altura mm*</label>
											<div class="">
												<input id="altura" type="text" class="form-control @if($errors->has('altura')) is-invalid @endif" name="altura" value="{{{ isset($etiqueta) ? $etiqueta->altura : old('altura') }}}">
												@if($errors->has('altura'))
												<div class="invalid-feedback">
													{{ $errors->first('altura') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Largura mm*</label>
											<div class="">
												<input id="largura" type="text" class="form-control @if($errors->has('largura')) is-invalid @endif" name="largura" value="{{{ isset($etiqueta) ? $etiqueta->largura : old('largura') }}}">
												@if($errors->has('largura'))
												<div class="invalid-feedback">
													{{ $errors->first('largura') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Etiquetas por linha*</label>
											<div class="">
												<input id="etiquestas_por_linha" type="text" class="form-control @if($errors->has('etiquestas_por_linha')) is-invalid @endif" name="etiquestas_por_linha" value="{{{ isset($etiqueta) ? $etiqueta->etiquestas_por_linha : old('etiquestas_por_linha') }}}">
												@if($errors->has('etiquestas_por_linha'))
												<div class="invalid-feedback">
													{{ $errors->first('etiquestas_por_linha') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label">Dist. entre etiquetas mm*</label>
											<div class="">
												<input id="distancia_etiquetas_lateral" type="text" class="form-control @if($errors->has('distancia_etiquetas_lateral')) is-invalid @endif" name="distancia_etiquetas_lateral" value="{{{ isset($etiqueta) ? $etiqueta->distancia_etiquetas_lateral : old('distancia_etiquetas_lateral') }}}">
												@if($errors->has('distancia_etiquetas_lateral'))
												<div class="invalid-feedback">
													{{ $errors->first('distancia_etiquetas_lateral') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Dist. etiquetas topo mm*</label>
											<div class="">
												<input id="distancia_etiquetas_topo" type="text" class="form-control @if($errors->has('distancia_etiquetas_topo')) is-invalid @endif" name="distancia_etiquetas_topo" value="{{{ isset($etiqueta) ? $etiqueta->distancia_etiquetas_topo : old('distancia_etiquetas_topo') }}}">
												@if($errors->has('distancia_etiquetas_topo'))
												<div class="invalid-feedback">
													{{ $errors->first('distancia_etiquetas_topo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Quantidade etiquetas*</label>
											<div class="">
												<input id="quantidade_etiquetas" type="text" class="form-control @if($errors->has('quantidade_etiquetas')) is-invalid @endif" name="quantidade_etiquetas" value="{{{ isset($etiqueta) ? $etiqueta->quantidade_etiquetas : old('quantidade_etiquetas') }}}">
												@if($errors->has('quantidade_etiquetas'))
												<div class="invalid-feedback">
													{{ $errors->first('quantidade_etiquetas') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-2">
											<label class="col-form-label">Tamanho da fonte*</label>
											<div class="">
												<input id="tamanho_fonte" type="text" class="form-control @if($errors->has('tamanho_fonte')) is-invalid @endif" name="tamanho_fonte" value="{{{ isset($etiqueta) ? $etiqueta->tamanho_fonte : old('tamanho_fonte') }}}">
												@if($errors->has('tamanho_fonte'))
												<div class="invalid-feedback">
													{{ $errors->first('tamanho_fonte') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label">Tamanho cód. barras mm*</label>
											<div class="">
												<input id="tamanho_codigo_barras" type="text" class="form-control @if($errors->has('tamanho_codigo_barras')) is-invalid @endif" name="tamanho_codigo_barras" value="{{{ isset($etiqueta) ? $etiqueta->tamanho_codigo_barras : old('tamanho_codigo_barras') }}}">
												@if($errors->has('tamanho_codigo_barras'))
												<div class="invalid-feedback">
													{{ $errors->first('tamanho_codigo_barras') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-3">
											<label class="col-form-label">Tipo</label>
											<div class="">
												<select class="form-control form-select" name="tipo">
													<option @isset($etiqueta) @if($etiqueta->tipo == 'simples') selected @endif @endif value="simples">Simples</option>
													<option @isset($etiqueta) @if($etiqueta->tipo == 'gondola') selected @endif @endif value="gondola">Gondola</option>
												</select>
												@if($errors->has('tipo'))
												<div class="invalid-feedback">
													{{ $errors->first('tipo') }}
												</div>
												@endif
											</div>
										</div>

									</div>
									<div class="row">
										<div class="form-group validated col-sm-3 col-lg-3">
											<div class="">
												<label class="checkbox">
													<input @isset($etiqueta) @if($etiqueta->nome_empresa) checked @endif @else @if(old('nome_empresa')) checked @endif @endif type="checkbox" name="nome_empresa"/>
													<span></span>
													<b style="margin-left: 3px;">Nome da empresa</b>
												</label>
											</div>
										</div>
										<div class="form-group validated col-sm-3 col-lg-3">
											<div class="">
												<label class="checkbox">
													<input @isset($etiqueta) @if($etiqueta->nome_produto) checked @endif @else @if(old('nome_produto')) checked @endif @endif type="checkbox" name="nome_produto"/>
													<span></span>
													<b style="margin-left: 3px;">Nome do produto</b>
												</label>
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-3">
											<div class="">
												<label class="checkbox">
													<input @isset($etiqueta) @if($etiqueta->valor_produto) checked @endif @else @if(old('valor_produto')) checked @endif @endif type="checkbox" name="valor_produto"/>
													<span></span>
													<b style="margin-left: 3px;">Valor do produto</b>
												</label>
											</div>
										</div>

										<div class="form-group validated col-sm-3 col-lg-3">
											<div class="">
												<label class="checkbox">
													<input @isset($etiqueta) @if($etiqueta->codigo_produto) checked @endif @else @if(old('codigo_produto')) checked @endif @endif type="checkbox" name="codigo_produto"/>
													<span></span>
													<b style="margin-left: 3px;">Código do produto</b>
												</label>
											</div>
										</div>
										<div class="form-group validated col-sm-3 col-lg-3">
											<div class="">
												<label class="checkbox">
													<input @isset($etiqueta) @if($etiqueta->codigo_barras_numerico) checked @endif @else @if(old('codigo_barras_numerico')) checked @endif @endif type="checkbox" name="codigo_barras_numerico"/>
													<span></span>
													<b style="margin-left: 3px;">Código de barras numérico</b>
												</label>
											</div>
										</div>

										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Obsevação</label>
											<div class="">
												<input id="observacao" type="text" class="form-control @if($errors->has('observacao')) is-invalid @endif" name="observacao" value="{{{ isset($etiqueta) ? $etiqueta->observacao : old('observacao') }}}">
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

					</div>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/etiquetas">
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