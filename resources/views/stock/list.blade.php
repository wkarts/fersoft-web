@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<input type="hidden" id="pass" value="{{ $config->senha_remover ?? '' }}">
	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<div class="card card-custom gutter-b example example-compact">
				<div class="card-body">

					<div class="col-xl-12">
						<div class="row">
							<div class="col-xl-12">
								<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">

									<div class="card-body">
										<div class="row">
											<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
												<div class="card card-custom gutter-b example example-compact">
													<div class="card-header">

														<div class="card-body">
															<h3 class="card-title">Total em estoque compra: R$ <strong style="margin-left: 3px;" class="text-danger"> {{ number_format($somaEstoque['compra'], 2, ',', '.') }}</strong></h3>

															<h3 class="card-title">Total em estoque venda: R$ <strong style="margin-left: 3px;" class="text-success"> {{ number_format($somaEstoque['venda'], 2, ',', '.') }}</strong></h3>

															<a class="navi-text" href="/estoque/apontamentoManual">
																<span class="label label-xl label-inline label-light-danger">Apontamento manual</span>
															</a>

															<a target="_blank" class="navi-text" href="/estoque/listApontamentos">
																<span class="label label-xl label-inline label-light-primary">Listar alterações</span>
															</a>

															<a onclick='swal("Atenção!", "Deseja realizar esta ação, não será possível retomar os dados?", "warning").then((sim) => {if(sim){ zerarEstoque() }else{return false} })' href="#!" class="navi-text">
																<span class="label label-xl label-inline label-light-warning">Zerar estoque completo</span>
															</a>

															<a href="#!" class="navi-text" data-toggle="modal" data-target="#modal_estoque">
																<span class="label label-xl label-inline label-dark">
																	Gerenciamento de estoque de produtos
																</span>
															</a>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<br>
									<form method="get" action="/estoque/pesquisa">
										<div class="row align-items-center">
											<div class="form-group col-lg-4 col-12">
												<div class="input-group">
													<label class="col-form-label">Produto</label>
													<div class="input-group">
														<input type="text" name="pesquisa" class="form-control" placeholder="Pesquisa produto" id="kt_datatable_search_query" value="{{{ isset($pesquisa) ? $pesquisa : ''}}}">
													</div>
												</div>
											</div>

											<div class="form-group col-lg-2 col-12">

												<label class="col-form-label">Categoria</label>
												<select id="categoria" class="form-control custom-select" name="categoria_id">
													<option value="">Selecione categoria</option>
													@foreach($categorias as $cat)
													<option value="{{$cat->id}}" @if(isset($categoria_id) && $cat->id == $categoria_id) selected @endif>
														{{$cat->nome}}
													</option>
													@endforeach
												</select>
											</div>

											@if(empresaComFilial())
											{!! __view_locais_select_filtro("Local", isset($filial_id) ? $filial_id : '') !!}
											@endif

											<div class="col-lg-2 col-xl-2 mt-3">
												<button class="btn btn-light-primary px-6 font-weight-bold">Filtrar</button>
											</div>
										</div>

									</form>

									<br>
									<h4>Estoque</h4>
									@if(isset($totalProdutosEmEstoque))
									<label>Total de produtos em estoque: <strong class="text-info">{{$totalProdutosEmEstoque}}</strong></label>
									@endif

									<table class="datatable-table" style="max-width: 100%; overflow: scroll">
										<thead class="datatable-head">
											<tr class="datatable-row" style="left: 0px;">

												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Produto</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Local</span></th>
												<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Categoria</span></th>
												<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Quanitdade</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor de Compra</span></th>

												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Valor de Venda</span></th>

												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Subtotal Compra</span></th>
												<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Subtotal Venda</span></th>
											</tr>
										</thead>
										<tbody class="datatable-body">
											<?php 
											$subtotalCompra = 0;
											$subtotalVenda = 0;
											?>
											@foreach($estoque as $e)
											@if($e->produto)
											<tr class="datatable-row" style="left: 0px;">
												<td class="datatable-cell"><span class="codigo" style="width: 200px;">
													{{$e->produto->id}} - {{$e->produto->nome}}
													{{$e->produto->grade ? " (" . $e->produto->str_grade . ")" : ""}}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 200px;">
													{{ $e->filial ? $e->filial->descricao : 'Matriz'}}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 80px;">{{$e->produto->categoria->nome}}</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 100px;">
													@if(!$e->produto->unidadeQuebrada())
													{{number_format($e->quantidade, 0, '.', '')}}
													@else
													{{number_format($e->quantidade, $casasDecimaisQtd, '.', ',')}}
													@endif
												</span></td>

												<td class="datatable-cell"><span class="codigo" style="width: 80px;">
													{{ number_format($e->valor_compra, 2, ',', '.') }} {{$e->produto->unidade_compra}}
												</span></td>

												<td class="datatable-cell"><span class="codigo" style="width: 80px;">
													{{ number_format($e->produto->valor_venda, 2, ',', '.') }} {{$e->produto->unidade_venda}}
												</span></td>

												<td class="datatable-cell"><span class="codigo" style="width: 120px;">
													{{ number_format($e->valorCompra() * $e->quantidade, 2, ',', '.') }}
												</span></td>
												<td class="datatable-cell"><span class="codigo" style="width: 120px;">
													{{ number_format($e->produto->valor_venda * $e->quantidade, 2, ',', '.') }}
												</span></td>

											</tr>
											@endif
											@endforeach
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="d-flex justify-content-between align-items-center flex-wrap">
							<div class="d-flex flex-wrap py-2 mr-3">
								@if(isset($links))
								{{$estoque->links()}}
								@endif
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal_estoque" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Gerenciamento de estoque</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">
				<div class="row">
					<p class="text-danger"><i class="la la-warning text-danger"></i>Esta ação afetará todos os produtos</p>
					<input type="hidden" id="id_cancela" name="">
					<div class="form-group validated col-12">
						<label class="col-form-label" id="">Gerenciar estouque</label>
						<select class="form-control" id="gerenciar_estoque" name="gerenciar_estoque">
							<option value="0">Não</option>
							<option value="1">Sim</option>
						</select>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" id="btn-cancelar-3" onclick="salvarGerenciamento()" class="btn btn-light-success font-weight-bold spinner-white spinner-right">Salvar</button>
			</div>
		</div>
	</div>
</div>

@section('javascript')
<script type="text/javascript">

	function zerarEstoque(){
		let senha = $('#pass').val()
		if(senha != ""){

			swal({
				title: 'Zerar estoque',
				text: 'Informe a senha!',
				content: {
					element: "input",
					attributes: {
						placeholder: "Digite a senha",
						type: "password",
					},
				},
				button: {
					text: "Zerar!",
					closeModal: false,
					type: 'error'
				},
				confirmButtonColor: "#DD6B55",
			}).then(v => {
				if(v.length > 0){
					$.get(path+'configNF/verificaSenha', {senha: v})
					.then(
						res => {
							location.href="/estoque/zerarEstoque?senha="+v;
						},
						err => {
							swal("Erro", "Senha incorreta", "error")
							.then(() => {
								location.reload()
							});
						}
						)
				}else{
					location.reload()
				}
			})
		}else{
			swal("Erro", "Não é possível realizar esta ação sem uma senha cadastrada", 
				"error")
		}
	}

	function salvarGerenciamento(){
		$('#modal_estoque').modal('hide')
		let senha = $('#pass').val()
		let gerenciar_estoque = $('#gerenciar_estoque').val()
		if(senha != ""){
			swal({
				title: 'Alterar gerenciamento de produtos',
				text: 'Informe a senha!',
				content: {
					element: "input",
					attributes: {
						placeholder: "Digite a senha",
						type: "password",
					},
				},
				button: {
					text: "OK!",
					closeModal: false,
					type: 'error'
				},
				confirmButtonColor: "#DD6B55",
			}).then(v => {
				if(v.length > 0){
					$.get(path+'configNF/verificaSenha', {senha: v})
					.then(
						res => {
							location.href="/estoque/alterarGerenciamento?senha="+v+"&gerenciar_estoque="+gerenciar_estoque;
						},
						err => {
							swal("Erro", "Senha incorreta", "error")
							.then(() => {
								location.reload()
							});
						}
						)
				}else{
					location.reload()
				}
			})
		}else{
			swal("Erro", "Não é possível realizar esta ação sem uma senha cadastrada", 
				"error")
		}
	}
</script>
@endsection

@endsection
