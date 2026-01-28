@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="{{{ isset($item) ? route('funcionarioEventos.update', [$item->id]) : route('funcionarioEventos.store') }}}">
					@isset($item)
					@method('put')
					@endif
					<input type="hidden" name="id" value="{{{ isset($categoria) ? $categoria->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Atribuir Eventos</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-7 col-lg-8 col-12">
											<label class="col-form-label" id="">Funcionário</label>
											<div class="input-group">

												@isset($item)
												<h4>{{$item->nome}}</h4>
												@else
												<select required class="form-control select2" id="kt_select2_3" name="funcionario_id">
													<option value="">Selecione o funcionário</option>
													@foreach($funcionarios as $f)
													<option value="{{$f->id}}">{{$f->id}} - {{$f->nome}} ({{$f->cpf}})</option>
													@endforeach
												</select>
												@endif

											</div>
										</div>
									</div>

									<div class="col-12">

										<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded row">

											<table class="datatable-table table-dynamic" style="max-width: 100%; overflow: scroll;">

												<thead class="datatable-head">
													<tr class="datatable-row" style="left: 0px;">
														<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 70px;"></span></th>
														<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 200px;">Evento</span></th>
														<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Condição</span></th>
														<th data-field="Country" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Valor</span></th>
														<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Método</span></th>
														<th data-field="ShipDate" class="datatable-cell datatable-cell-sort"><span style="width: 100px;">Ativo</span></th>
													</tr>
												</thead>
												<tbody id="body" class="datatable-body">
													@isset($item)

													@foreach($item->eventos as $ev)

													<tr class="datatable-row dynamic-form">
														<td class="datatable-cell">
															<span class="codigo" style="width: 70px;" id="id">
																<button type="button" class="btn btn-sm btn-danger btn-remove">
																	<i class="la la-trash"></i>
																</button>
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 200px;">
																<select required name="evento[]" class="form-control evento">
																	<option value="">Selecione</option>
																	@foreach($eventos as $e)
																	<option @if($e->id == $ev->evento_id) selected @endif value="{{$e->id}}" data-condicao="{{ $e->condicao }}"
																		data-metodo="{{ $e->metodo }}">{{$e->nome}}
																	</option>
																	@endforeach
																</select>
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 100px;" id="id">
																<select required name="condicao[]" class="form-control condicao_chave" readonly>
																	<option value="">Selecione</option>
																	<option @if($ev->condicao == "soma") selected @endif value="soma">Soma</option>
																	<option @if($ev->condicao == "diminui") selected @endif value="diminui">Diminui</option>
																</select>
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 100px;">
																<input value="{{ number_format($ev->valor, 2, ',', '.') }}" required type="tel" name="valor[]" class="form-control money">
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 100px;" id="id">
																<select required name="metodo[]" class="form-control metodo">
																	<option value="">Selecione</option>
																	<option @if($ev->metodo == "informado") selected @endif value="informado">Informado</option>
																	<option @if($ev->metodo == "fixo") selected @endif value="fixo">Fixo</option>
																</select>
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 100px;">
																<select required name="ativo[]" class="form-control ativo">
																	<option @if($ev->ativo == 1) selected @endif value="1">Sim</option>
																	<option @if($ev->ativo == 0) selected @endif value="0">Não</option>
																</select>
															</span>
														</td>

													</tr>
													@endforeach
													@else
													<tr class="datatable-row dynamic-form">
														<td class="datatable-cell">
															<span class="codigo" style="width: 70px;" id="id">
																<button type="button" class="btn btn-sm btn-danger btn-remove">
																	<i class="la la-trash"></i>
																</button>
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 200px;">
																<select required name="evento[]" class="form-control evento">
																	<option value="">Selecione</option>
																	@foreach($eventos as $e)
																	<option value="{{$e->id}}" data-condicao="{{ $e->condicao }}"
																		data-metodo="{{ $e->metodo }}">{{$e->nome}}
																	</option>
																	@endforeach
																</select>
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 100px;" id="id">
																<select required name="condicao[]" class="form-control condicao_chave" readonly>
																	<option value="">Selecione</option>
																	<option value="soma">Soma</option>
																	<option value="diminui">Diminui</option>
																</select>
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 100px;">
																<input required type="tel" name="valor[]" class="form-control money">
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 100px;" id="id">
																<select required name="metodo[]" class="form-control metodo">
																	<option value="">Selecione</option>
																	<option value="informado">Informado</option>
																	<option value="fixo">Fixo</option>
																</select>
															</span>
														</td>

														<td class="datatable-cell">
															<span class="codigo" style="width: 100px;">
																<select required name="ativo[]" class="form-control ativo">
																	<option value="1">Sim</option>
																	<option value="0">Não</option>
																</select>
															</span>
														</td>

													</tr>
													@endif
												</tbody>
											</table>
											<button type="button" class="btn btn-sm btn-success btn-add mt-2">
												<i class="la la-plus"></i> Adicionar Item
											</button>
										</div>
									</div>
									<br>

								</div>
							</div>
						</div>
					</div>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/funcionarioEventos">
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

	$('form').on('change', '.evento', function() {
		let value = $(this).val()
		if(value){
			const condicao = ($('option:selected', this).attr('data-condicao'));
			const metodo = ($('option:selected', this).attr('data-metodo'));
			$(this).closest('tr').find('.condicao_chave').val(condicao)
			$(this).closest('tr').find('.condicao_chave').addClass('select-disabled')
			$(this).closest('tr').find('.metodo').val(metodo)
			$(this).closest('tr').find('.metodo').addClass('select-disabled')

		}
	})

	$(".btn-add").on("click", function() {
		var $table = $(this)
		.closest(".row")
		.find(".table-dynamic");
		console.clear()

		var hasEmpty = false;
		$table.find("input, select").each(function() {
			console.log("val", $(this).val())
			if (($(this).val() == "" || $(this).val() == null)) {
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
		console.log($table)
		var $tr = $table.find(".dynamic-form").first();
		console.log($tr)

		var $clone = $tr.clone();
		$clone.show();
		$clone.find("input,select").val("");
		$clone.find(".ativo").val("1");
		$clone.find(".money").mask('000000000000000,00', {reverse: true});

		$table.append($clone);
	});

	$(document).delegate(".btn-remove", "click", function(e) {
		e.preventDefault();
		swal({
			title: "Você esta certo?",
			text: "Deseja remover esse item mesmo?",
			icon: "warning",
			buttons: true
		}).then(willDelete => {
			if (willDelete) {
				var trLength = $(this)
                .closest("tr")
                .closest("tbody")
                .find("tr")
                .not(".dynamic-form-document").length;
                if (!trLength || trLength > 1) {
                    $(this)
                    .closest("tr")
                    .remove();
                } else {
                    swal(
                        "Atenção",
                        "Você deve ter ao menos um item na lista",
                        "warning"
                        );
                }
			}
		})
	})
</script>
@endsection
