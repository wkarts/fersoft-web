@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<form method="post" action="{{{ isset($produto) ? '/deliveryProduto/update': '/deliveryProduto/save' }}}" enctype="multipart/form-data">
					<input type="hidden" name="id" value="{{{ isset($produto->id) ? $produto->id : 0 }}}">


					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">{{isset($produto) ? 'Editar' : 'Novo'}} Produto</h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">
									<p class="text-danger">O produto de delivery depende do produto principal, isso é necessário para baixa de estoque</p>
									<div class="row">
										<div id="ref-prod" class="form-group validated col-sm-7 col-lg-7 col-10">
											<label class="col-form-label" id="">Produto</label><br>
											<select class="form-control select2" style="width: 100%" id="kt_select2_1" name="produto_id">
												<option value="">Selecione o produto</option>
												@foreach($produtos as $p)
												<option 
												@if(isset($produto))
												@if($p->id == $produto->produto->id)
												selected
												@endif
												@else
												@if(old('produto_id') == $p->id)
												selected
												@endif
												@endif
												value="{{$p->id}}">{{$p->id}} - {{$p->nome}}</option>
												@endforeach
											</select>
											@if($errors->has('produto'))
											<div class="invalid-feedback">
												{{ $errors->first('produto') }}
											</div>
											@endif

											
										</div>

										<div style="display: none" id="novo-prod" class="form-group validated col-sm-7 col-lg-7 col-10">
											<label class="col-form-label">Nome do Produto</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('nome')) is-invalid @endif" name="produto" id="nome" >
												@if($errors->has('nome'))
												<div class="invalid-feedback">
													{{ $errors->first('nome') }}
												</div>
												@endif
											</div>

											<p class="text-info">* As informações de tributação do produto serão definidas usando o padrão estabelecido</p>
										</div>

										<div class="col-lg-1 col-md-1 col-sm-1 col-2">
											<br>
											<a id="novo-produto" style="margin-top: 18px;" class="btn btn-success">
												<i class="la la-plus"></i>
											</a>
										</div>

										<div class="form-group validated col-lg-4 col-md-4 col-sm-10">
											<label class="col-form-label ">Categoria</label>

											<select id="categoria-select" class="custom-select form-control" name="categoria_id">
												@foreach($categorias as $c)
												<option
												data-type="{{$c->tipo_pizza}}"
												@if($c->id == old('categoria_id'))
												selected=""
												@endif
												@isset($produto)
												@if($c->id == $produto->categoria_id)
												selected=""
												@endif
												@endisset
												value="{{$c->id}}">{{$c->nome}}</option>
												@endforeach
											</select>

											@if($errors->has('categoria'))
											<div class="invalid-feedback">
												{{ $errors->first('categoria') }}
											</div>
											@endif
										</div>
									</div>
								</div>

								<div class="row">
									<div class="form-group validated col-sm-3 col-lg-2 produto-comum">
										<label class="col-form-label">Valor de Venda</label>
										<div class="">
											<input type="text" class="form-control @if($errors->has('valor')) is-invalid @endif money" name="valor" value="{{{ isset($produto) ? moeda($produto->valor) : old('valor') }}}">
											@if($errors->has('valor'))
											<div class="invalid-feedback">
												{{ $errors->first('valor') }}
											</div>
											@endif
										</div>
									</div>

									<div class="form-group validated col-sm-3 col-lg-2 produto-comum">
										<label class="col-form-label">Valor Anterior</label>
										<div class="">
											<input type="text" id="valor_anterior" class="form-control @if($errors->has('valor_anterior')) is-invalid @endif" name="valor_anterior" value="{{{ isset($produto) ? moeda($produto->valor_anterior) : old('valor_anterior') }}}">
											@if($errors->has('valor_anterior'))
											<div class="invalid-feedback">
												{{ $errors->first('valor_anterior') }}
											</div>
											@endif
										</div>
									</div>

									<div class="form-group validated col-sm-3 col-lg-2">
										<label class="col-form-label">Referência</label>
										<div class="">
											<input type="text" id="referencia" class="form-control @if($errors->has('referencia')) is-invalid @endif" name="referencia" value="{{{ isset($produto) ? $produto->referencia : old('referencia') }}}">
											@if($errors->has('referencia'))
											<div class="invalid-feedback">
												{{ $errors->first('referencia') }}
											</div>
											@endif
										</div>
									</div>

									<?php $controleEdit = []; ?>

									@foreach($tamanhos as $key => $t)

									<div class="form-group validated col-sm-2 col-lg-2 produto-pizza">
										<label class="col-form-label">Valor {{$t->nome}}</label>

										@if(isset($produto) && count($produto->pizza) > 0)
										@foreach($produto->pizza as $pp)

										@if($pp->tamanho_id == $t->id)
										<input type="text" class="form-control valor_pizza" value="{{{ isset($pp->valor) ? $pp->valor : old('valor_{{$t->nome}}') }}}" name="valor_{{$t->nome}}">
										@else

										@if(!$pp->tamanhoNaoCadastrado($t->id, $pp->produto) && !in_array($t->id, $controleEdit))
										<input type="text" class="form-control valor_pizza" 
										value="" name="valor_{{$t->nome}}">

										<?php array_push($controleEdit, $t->id); ?>

										@endif
										@endif
										@endforeach

										@else
										<input type="text" class="form-control valor_pizza" value="{{{ isset($pp->valor) ? $pp->valor : old('valor_'.$t->nome) }}}" name="valor_{{$t->nome}}">
										@endif


										@if($errors->has('valor_'.$t->nome))
										<div class="invalid-feedback">
											{{ $errors->first('valor_'.$t->nome) }}
										</div>
										@endif
									</div>

									@endforeach

									<div class="form-group validated col-sm-3 col-lg-2">
										<label class="col-form-label">Limite diário de venda</label>
										<div class="">
											<input type="text" class="form-control @if($errors->has('limite_diario')) is-invalid @endif" name="limite_diario" value="{{{ isset($produto->limite_diario) ? $produto->limite_diario : old('limite_diario') }}}">
											@if($errors->has('limite_diario'))
											<div class="invalid-feedback">
												{{ $errors->first('limite_diario') }}
											</div>
											@endif
											<span class="text-info">-1 = sem limite</span>
										</div>
									</div>
									<div class="col-sm-3 col-lg-2 mt-4">
										<label>Destaque:</label>

										<div class="switch switch-outline switch-primary">
											<label class="">
												<input @if(isset($produto->destaque) && $produto->destaque) checked @endisset value="true" name="destaque" class="red-text" type="checkbox">
												<span class="lever"></span>
											</label>
										</div>
									</div>

									<div class="col-sm-3 col-lg-2 mt-4">
										<label>Ativo:</label>

										<div class="switch switch-outline switch-success">
											<label class="">
												<input @if(isset($produto->status) && $produto->status) checked @endisset value="true" name="status" class="red-text" type="checkbox">
												<span class="lever"></span>
											</label>
										</div>
									</div>

									<div class="col-sm-3 col-lg-2 mt-4">
										<label>Liberar adicionais:</label>

										<div class="switch switch-outline switch-info">
											<label class="">
												<input @if(isset($produto->tem_adicionais) && $produto->tem_adicionais) checked @endisset value="true" name="tem_adicionais" class="red-text" type="checkbox">
												<span class="lever"></span>
											</label>
										</div>
									</div>

									<div class="form-group validated col-lg-6">
										<label class="col-form-label">Descrição curta</label>
										<div class="">
											<input type="text" id="descricao_curta" class="form-control @if($errors->has('descricao_curta')) is-invalid @endif" name="descricao_curta" value="{{{ isset($produto) ? $produto->descricao_curta : old('descricao_curta') }}}">
											@if($errors->has('descricao_curta'))
											<div class="invalid-feedback">
												{{ $errors->first('descricao_curta') }}
											</div>
											@endif
										</div>
									</div>
								</div>

								<div class="row">
									<div class="form-group validated col-sm-12 col-lg-9">
										<label class="col-form-label">Descrição</label>
										<div class="">

											<textarea class="form-control" name="descricao" placeholder="Descrição" rows="3">{{{ isset($produto->descricao) ? $produto->descricao : old('descricao') }}}</textarea>
											@if($errors->has('descricao'))
											<div class="invalid-feedback">
												{{ $errors->first('descricao') }}
											</div>
											@endif
										</div>
									</div>

									<div class="col-lg-10 col-xl-3">
										<label class="col-form-label col-12">Imagem principal</label>

										<div class="image-input image-input-outline" id="kt_image_1">
											<div class="image-input-wrapper" @if(isset($produto) && isset($produto->galeria[0]->path)) style="background-image: url({{$produto->img}})" @else style="background-image: url(/imgs/no_image.png)" @endif></div>
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

								<!-- <div class="row">
									<div class="form-group validated col-sm-12 col-lg-12">
										<label class="col-form-label">Ingredientes</label>
										<div class="">

											<textarea class="form-control" name="ingredientes" placeholder="Descrição" rows="3">{{{ isset($produto->ingredientes) ? $produto->ingredientes : old('ingredientes') }}}</textarea>
											@if($errors->has('ingredientes'))
											<div class="invalid-feedback">
												{{ $errors->first('ingredientes') }}
											</div>
											@endif
										</div>
									</div>
								</div>
							-->
						</div>
					</div>
				</div>
				<br>
				<div class="card-footer">

					<div class="row">
						<div class="col-xl-2">

						</div>
						<div class="col-lg-3 col-sm-6 col-md-4">
							<a style="width: 100%" class="btn btn-danger" href="/deliveryProduto">
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