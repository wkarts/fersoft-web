@extends('default.layout')
@section('content')

<div class="row" id="anime" style="display: none">
	<div class="col s8 offset-s2">
		<lottie-player src="/anime/{{\App\Models\Venda::randSuccess()}}" background="transparent" speed="0.8" style="width: 100%; height: 300px;" autoplay >
		</lottie-player>
	</div>
</div>


<div class="card card-custom gutter-b">

	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >
			<div class="card card-custom gutter-b example example-compact">
				<div class="col-lg-12">
					<!--begin::Portlet-->

					<input type="hidden" value="{{$devolucao->id}}" id="dev_id" name="dev_id">
					<input type="hidden" name="id" value="{{{ isset($cliente) ? $cliente->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Editando Tributação Devolução: <strong>{{$devolucao->id}}</strong></h3>
						</div>
					</div>
					<input type="hidden" value="{{csrf_token()}}" id="_token">
					

					<form class="row" method="post" action="/devolucao/{{$devolucao->id}}/updateManual">
						@method('put')
						@csrf
						<div class="col-xl-12">
							
							<div class="col-xl-12">
								<div class="row">

									<div class="col-xl-12 col-sm-12 col-lg-12">
										<h4 class="center-align">Nota Fiscal: <strong class="text-primary">{{$dadosNf['nNf']}}</strong></h4>
										<h4 class="center-align">Chave: <strong class="text-primary">{{$dadosNf['chave']}}</strong></h4>
									</div>

									<div class="col-xl-6 col-sm-6 col-lg-6">
										<h5>Fornecedor: <strong>{{$dadosEmitente['razaoSocial']}}</strong></h5>
										<h5>Nome Fantasia: <strong>{{$dadosEmitente['nomeFantasia']}}</strong></h5>
										<h5>CNPJ: <strong>{{$dadosEmitente['cnpj']}}</strong></h5>
										<h5>IE: <strong>{{$dadosEmitente['ie']}}</strong></h5>
										<h5>Cidade: <strong>{{$cidade->nome}} ({{$cidade->uf}})</strong></h5>
									</div>

									<div class="col-xl-6 col-sm-6 col-lg-6">
										<h5>Logradouro: <strong>{{$dadosEmitente['logradouro']}}</strong></h5>
										<h5>Numero: <strong>{{$dadosEmitente['numero']}}</strong></h5>
										<h5>Bairro: <strong>{{$dadosEmitente['bairro']}}</strong></h5>
										<h5>CEP: <strong>{{$dadosEmitente['cep']}}</strong></h5>
										<h5>Fone: <strong>{{$dadosEmitente['fone']}}</strong></h5>
									</div>
								</div>
							</div>

						</div>
						<div class="col-xl-12">
							<div class="row">
								<div class="col-xl-12">

									<h4>Itens da NFe</h4>
									<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
										<table class="datatable-table" style="max-width: 100%;overflow: scroll" id="tbl">
											<thead class="datatable-head">
												<tr class="datatable-row" style="left: 0px;">
													<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">Código</span></th>
													<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Produto</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Valor</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Qtd</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">Subtotal</span></th>

													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">R$ VBC</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">%ICMS</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 120px;">R$ ICMS</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%PIS</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ PIS</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%COFINS</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ COFINS</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%IPI</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">R$ IPI</span></th>

													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">%pST</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">modBCST</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">vBCST</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">pICMSST</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 80px;">vICMSST</span></th>

													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">CST/CSOSN</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">CST/PIS</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">CST/COFINS</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">CST/IPI</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">CEST</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">qBCMonoRet</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">adRemICMSRet</span></th>
													<th data-field="Type" data-autohide-disabled="false" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">vICMSMonoRet</span></th>
												</tr>
											</thead>

											<tbody class="datatable-body">

												@foreach($devolucao->itens as $item)

												<tr class="datatable-row">
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															{{$item->id}}
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 200px;" id="id">
															{{$item->nome}}
														</span>
													</td>
													<input type="hidden" name="item_id[]" value="{{$item->id}}">
													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															<input type="tel" class="form-control money" 
															value="{{ number_format($item->valor_unit, 2, ',', '')}}" name="valor_unit[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															<input type="tel" class="form-control money qtd" 
															value="{{ number_format($item->quantidade, 2, ',', '.')}}" name="quantidade[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															<input type="tel" class="form-control money" 
															value="{{ number_format($item->quantidade*$item->valor_unit, 2, ',', '')}}" name="valor[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															<input type="tel" class="form-control money" 
															value="{{ number_format($item->vbc_manual, 2, ',', '')}}" name="vbc_manual_item[]">
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															<input type="tel" class="form-control money perc_icms" 
															value="{{ number_format($item->perc_icms, 2, ',', '')}}" name="perc_icms[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 120px;" id="id">
															<input type="tel" class="form-control money" 
															value="{{ number_format($item->vicms_manual, 2, ',', '')}}" name="vicms_manual[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money perc_pis" 
															value="{{ number_format($item->perc_pis, 2, ',', '')}}" name="perc_pis[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money" 
															value="{{ number_format($item->vpis_manual, 2, ',', '')}}" name="vpis_manual[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money perc_cofins" 
															value="{{ number_format($item->perc_cofins, 2, ',', '')}}" name="perc_cofins[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money" 
															value="{{ number_format($item->vcofins_manual, 2, ',', '')}}" name="vcofins_manual[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money perc_ipi" 
															value="{{ number_format($item->perc_ipi, 2, ',', '')}}" name="perc_ipi[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money" 
															value="{{ number_format($item->vipi_manual, 2, ',', '')}}" name="vipi_manual[]">
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money" 
															value="{{ number_format($item->pST, 2, ',', '.')}}" name="pST[]">
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control" data-mask="00" value="{{ (int)$item->modBCST }}" name="modBCST[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money" value="{{ number_format($item->vBCST, 2, ',', '.')}}" name="vBCST[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money" value="{{ number_format($item->pICMSST, 2, ',', '.')}}" name="pICMSST[]">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 80px;" id="id">
															<input type="tel" class="form-control money" value="{{ number_format($item->vICMSST, 2, ',', '.')}}" name="vICMSST[]">
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 250px;" id="id">
															<select class="custom-select form-control" name="cst_csosn[]">
																@foreach(App\Models\Produto::listaCSTCSOSN() as $key => $c)
																<option value="{{$key}}" @if($key == $item->cst_csosn) selected @endif>
																	{{$key}} - {{$c}}
																</option>
																@endforeach
															</select>
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 200px;" id="id">
															<select class="custom-select form-control" name="cst_pis[]">
																@foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
																<option value="{{$key}}" @if($key == $item->cst_pis) selected @endif>
																	{{$key}} - {{$c}}
																</option>
																@endforeach
															</select>
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 200px;" id="id">
															<select class="custom-select form-control" name="cst_cofins[]">
																@foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
																<option value="{{$key}}" @if($key == $item->cst_cofins) selected @endif>
																	{{$key}} - {{$c}}
																</option>
																@endforeach
															</select>
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 200px;" id="id">
															<select class="custom-select form-control" name="cst_ipi[]">
																@foreach(App\Models\Produto::listaCST_IPI() as $key => $c)
																<option value="{{$key}}" @if($key == $item->cst_csosn) selected @endif>
																	{{$key}} - {{$c}}
																</option>
																@endforeach
															</select>
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 100px;" id="id">
															<input type="tel" data-mask="00000000" class="form-control" value="{{$item->cest}}" name="cest[]">
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 100px;" id="id">
															<input type="tel" class="form-control money" value="{{ moeda($item->qBCMonoRet) }}" name="qBCMonoRet[]">
														</span>
													</td>

													<td class="datatable-cell">
														<span class="codigo" style="width: 100px;" id="id">
															<input type="tel" class="form-control" value="{{$item->adRemICMSRet}}" name="adRemICMSRet[]" data-mask="00,0000" data-mask-reverse="true">
														</span>
													</td>
													<td class="datatable-cell">
														<span class="codigo" style="width: 100px;" id="id">
															<input type="tel" class="form-control money" value="{{ moeda($item->vICMSMonoRet) }}" name="vICMSMonoRet[]">
														</span>
													</td>
												</tr>
												@endforeach

											</tbody>


										</table>
										<br>

									</div>

								</div>
							</div>
						</div>



						<div class="col-xl-12">
							<div class="col-xl-12">
								<div class="row">

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Valor Base de Cálculo</label>
										<input class="form-control money" type="text" value="{{number_format($devolucao->vbc_manual, 2, ',', '.')}}" id="vbc_manual" name="vbc_manual">
									</div>

									<div class="form-group validated col-lg-4 col-md-6 col-sm-4">
										<label class="col-form-label">Natureza de Operação</label>

										<select class="custom-select form-control" id="natureza" name="natureza">
											@foreach($naturezas as $n)
											<option @if($devolucao->natureza_id == $n->id) selected @endif value="{{$n->id}}">{{$n->natureza}}</option>
											@endforeach
										</select>

									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Tipo</label>

										<select class="custom-select form-control" id="tipo" name="tipo">
											<option @if($devolucao->tipo == 1) selected @endif value="1">Saida</option>
											<option @if($devolucao->tipo == 0) selected @endif value="0">Entrada</option>
										</select>

									</div>

									<div class="form-group validated col-lg-4 col-md-4 col-sm-4">
										<label class="col-form-label">Transportadora</label>

										<select class="custom-select form-control" id="transportadora_id" name="transportadora_id">
											<option value="0">--</option>
											@foreach($transportadoras as $t)
											<option
											@if($devolucao->transportadora_id != null)
											@if($devolucao->transportadora_id == $t->id)
											selected
											@endif
											@endif
											value="{{$t->id}}"
											>{{$t->razao_social}}</option>
											@endforeach
										</select>

									</div>


								</div>
							</div>

							<hr>
							<div class="col-sm-12">
								<div class="row">
									<div class="form-group validated col-12">
										<h3>Frete</h3>
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Valor do frete</label>
										<input class="form-control money" type="text" value="{{number_format($devolucao->vFrete, 2, ',', '.')}}" id="valor_frete" name="valor_frete">
									</div>


									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label" id="">Tipo</label>
										<select class="custom-select form-control" id="tipo_frete" name="tipo_frete">
											<option @if($devolucao->frete_tipo == 0) selected @endif value="0">0 - Emitente</option>
											<option @if($devolucao->frete_tipo == 1) selected @endif  value="1">1 - Destinatário</option>
											<option @if($devolucao->frete_tipo == 2) selected @endif  value="2">2 - Terceiros</option>
											<option @if($devolucao->frete_tipo == 9) selected @endif  value="9">9 - Sem Frete</option>
										</select>
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Placa</label>
										<input class="form-control" data-mask="AAA-AAAA" type="text" value="{{$devolucao->veiculo_placa}}" id="placa" name="placa">
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">UF</label>
										<select class="custom-select form-control" id="uf_placa" name="uf_placa">
											@foreach(App\Models\Cidade::estados() as $u)
											<option @if($u == $devolucao->veiculo_uf) selected=" @endif" value="{{$u}}">{{$u}}</option>
											@endforeach
										</select>
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Quantidade</label>
										<input class="form-control" type="text" value="{{$devolucao->frete_quantidade}}" id="qtd" name="frete_quantidade">
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Espécie</label>
										<input class="form-control" type="text" value="{{$devolucao->frete_especie}}" id="especie" name="especie">
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Peso bruto</label>
										<input class="form-control" type="text" value="{{$devolucao->frete_peso_bruto}}" id="peso_bruto" name="peso_bruto">
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Peso liquído</label>
										<input class="form-control" type="text" value="{{$devolucao->frete_peso_liquido}}" id="peso_liquido" name="peso_liquido">
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Outras despesas</label>
										<input class="form-control money" type="text" value="{{$devolucao->despesa_acessorias}}" id="valor_outros" name="valor_outros">
									</div>

									<div class="form-group validated col-lg-2 col-md-4 col-sm-2">
										<label class="col-form-label">Desconto</label>
										<input class="form-control money" type="text" value="{{$devolucao->vDesc}}" id="vDesc" name="vDesc">
									</div>
								</div>
							</div>
							<hr>
						</div>

						<div class="col-xl-12">
							<div class="row">
								<div class="col-xl-12">

									<div class="col-xl-6">
										<h4>Valor Integral da Nota: <strong id="valorDaNF" class="text-danger">R$ {{number_format((float)$dadosNf['vProd'], 2, ',', '.')}}</strong></h4>

									</div>
									<div class="col-xl-3">
									</div>
									<div class="col-xl-3">
										<button id="update-devolucao" style="width: 100%" type="submit" class="btn btn-success">
											<i class="la la-check"></i>
											<span class="">Atualizar</span>
										</button>
									</div>
								</div>
							</div>
						</div>
					</form>
					<br>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection	

@section('javascript')
<script type="text/javascript">
	$('body').on('blur', '.qtd', function() {
		var qtd = $(this).val();
		var $total_amount = $(this).closest('td').next().find('input');
		var $vl_unit = $(this).closest('td').prev().find('input');

		let v = parseFloat($vl_unit.val().replace(",", "."))
		qtd = parseFloat(qtd.replace(",", "."))

		$total_amount.val((v*qtd).toFixed(2).replace(".", ","));
	})

	$('body').on('blur', '.perc_icms', function() {
		var perc = $(this).val();
		var $total_icms = $(this).closest('td').next().find('input');
		var $sub_total = $(this).closest('td').prev().prev().find('input');

		let v = parseFloat($sub_total.val().replace(",", "."))
		perc = parseFloat(perc.replace(",", "."))

		$total_icms.val(((v*perc)/100).toFixed(2).replace(".", ","));
	})

	$('body').on('blur', '.perc_pis', function() {
		var perc = $(this).val();
		var $total_pis = $(this).closest('td').next().find('input');
		var $sub_total = $(this).closest('td').prev().prev().prev().prev().find('input');

		let v = parseFloat($sub_total.val().replace(",", "."))
		perc = parseFloat(perc.replace(",", "."))

		$total_pis.val(((v*perc)/100).toFixed(2).replace(".", ","));
	})

	$('body').on('blur', '.perc_cofins', function() {
		var perc = $(this).val();
		var $total_cofins = $(this).closest('td').next().find('input');
		var $sub_total = $(this).closest('td').prev().prev().prev().prev().prev().prev().find('input');

		let v = parseFloat($sub_total.val().replace(",", "."))
		perc = parseFloat(perc.replace(",", "."))

		$total_cofins.val(((v*perc)/100).toFixed(2).replace(".", ","));
	})

	$('body').on('blur', '.perc_ipi', function() {
		var perc = $(this).val();
		var $total_ipi = $(this).closest('td').next().find('input');
		var $sub_total = $(this).closest('td').prev().prev().prev().prev().prev().prev().prev().prev().find('input');

		let v = parseFloat($sub_total.val().replace(",", "."))
		perc = parseFloat(perc.replace(",", "."))

		$total_ipi.val(((v*perc)/100).toFixed(2).replace(".", ","));
	})
</script>
@endsection