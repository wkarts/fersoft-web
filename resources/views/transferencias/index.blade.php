@extends('default.layout')
@section('content')
<div class="card card-custom gutter-b">
	<input type="hidden" id="pass" value="{{ $config->senha_remover ?? '' }}">
	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
		<div class="col-sm-12 col-lg-12 col-md-12 col-xl-12">
			<div class="card card-custom gutter-b example example-compact">
				<div class="card-body">
                  
                  <button type="button" class="btn btn-light-info font-weight-bold" data-toggle="modal" data-target="#modal-manual-transferencia">
                      <i class="la la-exchange-alt icon-md"></i> Como funciona a Transferência?
                  </button>

					<div class="col-xl-12">
						<form class="row" method="post" action="/transferencia/store">
							@csrf

							{!! __view_locais_select_transfencia("Origem", 'saida') !!}

							{!! __view_locais_select_transfencia("Destino", 'entrada') !!}

							
							<div class="form-group validated col-12 col-lg-8">
								<label class="col-form-label" id="lbl_i_rg">Observação</label>
								<div class="">
									<input type="text" class="form-control" name="observacao">
								</div>
							</div>

							<div class="col-xl-12">
								<div class="table-responsive">
									<table class="table table-dynamic">
										<thead>
											<tr>
												<th>Produto</th>
												<th>Quantidade</th>
											</tr>
										</thead>
										<tbody>
											<tr class="dynamic-form">
												<td>
													<select required class="form-control custom-select-prod" style="width: 100%" id="kt_select2_1" name="produto[]">
														<option value="">Digite para buscar o produto</option>
													</select>
												</td>
												<td>
													<input required placeholder="Quantidade" type="text" class="form-control quantidade" name="quantidade[]">
												</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="row col-12">
									<button type="button" class="btn btn-info btn-clone-tbl ml-3">
										<i class="la la-plus"></i> Adicionar produto
									</button>
								</div>
							</div>

							<div class="col-md-12 col-12">
								<button class="btn btn-success float-right">Salvar transferência</button>
							</div>
						</form>
					</div>

				</div>

			</div>
			<a class="btn btn-dark" href="/transferencia/list">
				<i class="la la-refresh"></i>
				Ver histórico de transferências
			</a>

		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">

	$(function(){
		setTimeout(() => {
			$(".custom-select-prod").select2({
				minimumInputLength: 2,
				language: "pt-BR",
				placeholder: "Digite para buscar o produto",
				width: "100%",
				ajax: {
					cache: true,
					url: path + 'produtos/autocomplete',
					dataType: "json",
					data: function(params) {
						console.clear()
						var query = {
							pesquisa: params.term,
							filial_id: $('#saida').val(),
						};
						return query;
					},
					processResults: function(response) {
						console.log("response", response)
						var results = [];

						$.each(response, function(i, v) {
							var o = {};
							o.id = v.id;

							o.text = v.nome + (v.grade ? " "+v.str_grade : "") + " | R$ " + parseFloat(v.valor_venda).toFixed(2).replace(".", ",")
							+ (v.referencia != "" ? " - ref: " + v.referencia : "")
							+ " - estoque: " + v.estoqueAtual;
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

		}, 200);
	});

	$('.btn-clone-tbl').on("click", function() {
		console.clear()
		var $elem = $(this)
		.closest(".row")
		.prev()
		.find(".table-dynamic");

		var hasEmpty = false;

		$elem.find("input, select").each(function() {
			if (($(this).val() == "" || $(this).val() == null) && $(this).attr("type") != "hidden" && $(this).attr("type") != "file" && !$(this).hasClass("ignore")) {
				hasEmpty = true;
			}
		});

		if (hasEmpty) {
			swal(
				"Atenção",
				"Preencha todos os campos antes de adicionar novos.",
				"warning"
				);
			return;
		}

		try{
			$("tbody .custom-select-prod").select2("destroy");
		}catch{

		}
		var $tr = $elem.find(".dynamic-form").first();
		var $clone = $tr.clone();

		$clone.show();
		$clone.find("input,select").val("");

		$elem.append($clone);

		setTimeout(() => {
			$("tbody .custom-select-prod").select2({
				minimumInputLength: 2,
				language: "pt-BR",
				placeholder: "Digite para buscar o produto",
				width: "100%",
				ajax: {
					cache: true,
					url: path + 'produtos/autocomplete',
					dataType: "json",
					data: function(params) {
						console.clear()

						var query = {
							pesquisa: params.term,
						};
						return query;
					},
					processResults: function(response) {
						console.log("response", response)
						var results = [];

						$.each(response, function(i, v) {
							var o = {};
							o.id = v.id;

							o.text = v.nome + (v.grade ? " "+v.str_grade : "") + " | R$ " + parseFloat(v.valor_venda).toFixed(2).replace(".", ",")
							+ (v.referencia != "" ? " - ref: " + v.referencia : "")
							+ " - estoque: " + v.estoqueAtual;
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

		}, 200);
	})
</script>
<div class="modal fade" id="modal-manual-transferencia" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-info font-weight-bold">
                    <i class="la la-exchange-alt text-info icon-lg"></i> Manual Operacional: Transferência Multilocal (Matriz e Filiais)
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                
                <div class="alert alert-custom alert-light-info fade show mb-5" role="alert">
                    <div class="alert-icon"><i class="la la-cogs icon-xl"></i></div>
                    <div class="alert-text">
                        <strong>Automação de Ponta a Ponta:</strong> Esta rotina foi desenhada para movimentar mercadorias entre as unidades da empresa (Matriz ↔ Filiais) com zero retrabalho. Ao emitir a Nota Fiscal de Saída na origem, o sistema encarrega-se de <strong>espelhar a nota automaticamente</strong>, gerando o registo de Entrada no destino e alimentando os livros fiscais (SPED) com os códigos corretos.
                    </div>
                </div>

                <h4 class="font-weight-bold text-dark mb-4">🌟 Principais Benefícios</h4>
                <div class="row mb-6">
                    <div class="col-md-4">
                        <div class="bg-light p-4 rounded text-center h-100 border border-light-dark">
                            <i class="la la-copy text-primary icon-3x mb-2"></i>
                            <h6 class="font-weight-bold">Espelhamento Fiscal</h6>
                            <p class="text-muted mb-0 font-size-sm">Esqueça a digitação dupla. Aprovou a transferência? A Nota de Compra/Entrada já está no sistema da filial destino.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-4 rounded text-center h-100 border border-light-dark">
                            <i class="la la-box text-success icon-3x mb-2"></i>
                            <h6 class="font-weight-bold">Integridade de Estoque</h6>
                            <p class="text-muted mb-0 font-size-sm">O sistema regista um histórico cirúrgico: mostra a que horas o produto saiu de um local, o custo, e a que horas entrou noutro.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light p-4 rounded text-center h-100 border border-light-dark">
                            <i class="la la-balance-scale text-warning icon-3x mb-2"></i>
                            <h6 class="font-weight-bold">Inteligência Tributária</h6>
                            <p class="text-muted mb-0 font-size-sm">Conversão automática de CFOPs (ex: 5152 converte para 1152 no destino) e aplicação de CSTs de isenção.</p>
                        </div>
                    </div>
                </div>

                <hr>

                <h4 class="font-weight-bold text-dark mb-4 mt-5">Passo a Passo: Como realizar uma Transferência</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-custom gutter-b border shadow-none">
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-4 d-flex align-items-start">
                                        <span class="badge badge-info mr-3 mt-1">1</span>
                                        <div>
                                            <strong class="text-dark">Definir Locais e Produtos</strong><br>
                                            <span class="text-muted">Inicie uma nova transferência. Selecione com precisão o local de <strong>Saída</strong> (quem envia) e o local de <strong>Entrada</strong> (quem recebe). Adicione os produtos e as respetivas quantidades.</span>
                                        </div>
                                    </li>
                                    <li class="mb-4 d-flex align-items-start">
                                        <span class="badge badge-info mr-3 mt-1">2</span>
                                        <div>
                                            <strong class="text-dark">Gravar Movimentação</strong><br>
                                            <span class="text-muted">Ao clicar em salvar, o sistema fará instantaneamente a baixa no stock da unidade de origem e a entrada na unidade de destino.</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-custom gutter-b border shadow-none">
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-4 d-flex align-items-start">
                                        <span class="badge badge-info mr-3 mt-1">3</span>
                                        <div>
                                            <strong class="text-dark">Transporte e Pesos (Novo)</strong><br>
                                            <span class="text-muted">No ecrã de revisão fiscal, preencha os dados da transportadora e não se esqueça de informar o <strong>Peso Bruto</strong> e o <strong>Peso Líquido</strong>. Estes dados irão gerar os volumes no XML.</span>
                                        </div>
                                    </li>
                                    <li class="mb-0 d-flex align-items-start">
                                        <span class="badge badge-success mr-3 mt-1">4</span>
                                        <div>
                                            <strong class="text-dark">Transmissão para a SEFAZ</strong><br>
                                            <span class="text-muted">Clique em Transmitir. Assim que a SEFAZ devolver o protocolo de autorização, a mágica acontece: o ficheiro XML da NF-e é guardado e uma Nota de Entrada é criada silenciosamente para o seu SPED.</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-custom alert-light-danger fade show mb-0 mt-2" role="alert">
                    <div class="alert-icon"><i class="la la-exclamation-triangle"></i></div>
                    <div class="alert-text">
                        <strong>Atenção ao Operador:</strong> Como o sistema gera a Nota de Entrada e a movimentação de stock de forma 100% automatizada, <u>NÃO deve importar o XML desta nota manualmente no menu de Compras</u> na filial de destino. Se o fizer, os produtos serão somados duas vezes ao seu stock!
                    </div>
                </div>

            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-info font-weight-bold" data-dismiss="modal">Entendi, Fechar Manual</button>
            </div>
        </div>
    </div>
</div>
@endsection
