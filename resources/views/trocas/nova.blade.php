@extends('default.layout')
@section('content')
<style type="text/css">
	#focus-codigo:hover{
		cursor: pointer
	}

	.search-venda{
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

	.search-venda label:hover{
		cursor: pointer;
	}

	.search-venda label{
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
				<form method="post" action="/trocas/save" id="form-troca">
					<input type="hidden" name="id" value="{{{ isset($servico) ? $servico->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Troca/Devolução Venda</h3>
						</div>
					</div>
					@csrf

					<input type="hidden" name="venda_id" id="venda_id">
					<input type="hidden" name="tipo" id="tipo">
					<input type="hidden" name="itens" id="itens">

					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-10">
											<label class="col-form-label">Pesquisa por código da venda ou nome do cliente</label>
											<div class="input-group">
												<input autocomplete="off" type="text" class="form-control" id="pesquisa" name="pesquisa" value="">

												<div class="search-venda" style="display: none">
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row data-venda d-none">
						<div class="col-xl-12">

							<h4>Cliente: <strong class="cliente-nome"></strong></h4>
							<h4>Data: <strong class="cliente-data"></strong></h4>
							<h4>Valor total: <strong class="cliente-valor"></strong></h4>
						</div>
						<div class="col-xl-12">
							<p class="text-danger">*Remova os itens que não serão devolvidos!</p>

							<div id="kt_datatable" class="datatable datatable-bordered datatable-head-custom datatable-default datatable-primary datatable-loaded">
								<table class="datatable-table" style="max-width: 100%; overflow: scroll">
									<thead class="datatable-head">
										<tr class="datatable-row" style="left: 0px;">
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 250px;">Produto</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Quantidade</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Valor</span></th>
											<th data-field="OrderID" class="datatable-cell datatable-cell-sort"><span style="width: 150px;">Ação</span></th>
										</tr>
									</thead>
									<tbody class="datatable-body">
										
									</tbody>
								</table>
							</div>
							<h4>Valor alterado: <strong class="vl-alterado"></strong></h4>

						</div>
					</div>

					<div class="card-footer d-none">

						<div class="row">
							<div class="col-xl-2">
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/trocas">
									<i class="la la-close"></i>
									<span class="">Cancelar</span>
								</a>
							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<button type="submit" style="width: 100%" class="btn btn-success">
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

<div class="modal fade" id="modal-edit" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdrop" aria-hidden="true">
	<div class="modal-dialog modal-md" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="exampleModalLabel">Alterar quantidade</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					x
				</button>
			</div>
			<div class="modal-body">

				<div class="row">
					<div class="form-group validated col-sm-12 col-lg-12 col-12">
						<h4 class="prod-name"></h4>
					</div>
				</div>

				<div class="row">
					<div class="form-group validated col-sm-3 col-lg-6 col-12">
						<label class="col-form-label">Quantidade</label>
						<div class="">
							<input id="qtd" type="text" class="form-control" name="qtd" value="" data-mask="00000,000" data-mask-reverse="true">
						</div>
					</div>

				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-light-danger font-weight-bold" data-dismiss="modal">Fechar</button>
				<button type="button" onclick="alterar()" class="btn btn-success font-weight-bold spinner-white spinner-right">Alterar</button>
			</div>
		</div>
	</div>
</div>

@endsection

@section('javascript')
<script type="text/javascript">
	var ID = 0
	var TIPO = ''
	var ITENS = []
	$('#pesquisa').keyup(() => {
		console.clear()
		let pesquisa = $('#pesquisa').val();

		if(pesquisa.length > 1){
			montaAutocomplete(pesquisa, (res) => {
				if(res){
					if(res.length > 0){
						montaHtmlAutoComplete(res, (html) => {
							$('.search-venda').html(html)
							$('.search-venda').css('display', 'block')
						})

					}else{
						$('.search-venda').css('display', 'none')
					}
				}else{
					$('.search-venda').css('display', 'none')
				}
			})
		}else{
			$('.search-venda').css('display', 'none')
		}
	})

	function montaAutocomplete(pesquisa, call){
		$.get(path + 'trocas/autocomplete', {pesquisa: pesquisa})
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
			let p = rs.id
			p += " - " + rs.razao_social

			p += " | R$ " + parseFloat(rs.valor_total).toFixed(2).replace(".", ",")
			p += " - Data " + rs.data
			p += " - " + rs.tipo.toUpperCase()
			html += '<label onclick="selectVenda(\''+p+'\')">'+p+'</label>'
		})
		call(html)
	}

	function selectVenda(p){
		let sp = p.split("-")
		ID = parseInt(sp[0])
		TIPO = sp[3].trim().toLowerCase()

		$('#venda_id').val(ID)
		$('#tipo').val(TIPO)

		$('.search-venda').css('display', 'none')
		$('#pesquisa').val(p)
		$.get(path + 'trocas/getVenda', {id: ID, tipo: TIPO})
		.done((res) => {
			console.log(res)
			$('.cliente-nome').html(res.cliente ? res.cliente.razao_social : 'Consumidor final')
			$('.cliente-data').html(res.data)
			$('.cliente-valor').html("R$ " + parseFloat(res.valor_total).toFixed(2).replace(".", ","))

			ITENS = res.itens
			let html = montaHtmlItens(ITENS, (html) => {
				console.log(html)
				$('tbody').html(html)
			})
			$('.card-footer').removeClass('d-none')
			$('.data-venda').removeClass('d-none')


		})
		.fail((err) => {
			console.log(err)
		})

	}

	function montaHtmlItens(itens, call){
		let t = '';
		let soma = 0
		itens.map((i) => {
			t += '<tr class="datatable-row">'
			t += '<td class="datatable-cell">'
			t += '<span class="codigo" style="width: 250px;">'
			t += i.produto.nome + '</span>'
			t += '</td>'

			t += '<td class="datatable-cell">'
			t += '<span class="codigo" style="width: 150px;">'
			t += i.quantidade + '</span>'
			t += '</td>'

			t += '<td class="datatable-cell">'
			t += '<span class="codigo" style="width: 150px;">'
			t += parseFloat(i.valor).toFixed(2).replace(".", ",") + '</span>'
			t += '</td>'

			t += "<td class='datatable-cell'><span style='width: 150px;' class='codigo'><button class='btn btn-danger' type='button' onclick='deleteItem("+i.id+")'>"
			t += "<i class='la la-trash'></i></button>";

			t += "<button type='button' class='btn btn-warning ml-1' onclick='editItem("+i.id+")'>"
			t += "<i class='la la-edit'></i></button></span></td>";
			t += '</tr>'

			soma += parseFloat(i.quantidade) * parseFloat(i.valor)
		});
		$('#itens').val(JSON.stringify(ITENS))
		$('.vl-alterado').html("R$ " + soma.toFixed(2).replace(".", ","))
		call(t)
	}

	function deleteItem(id){
		swal({
			title: "Alerta",
			text: "Deseja remover este item?",
			icon: "warning",
			buttons: ["Não", 'Sim'],
			dangerMode: true,
		}).then((v) => {
			if (v) {

				let temp = ITENS.filter((x) => {
					return x.id != id
				})
				ITENS = temp
				let html = montaHtmlItens(ITENS, (html) => {
					console.log(html)
					$('tbody').html(html)
				})

			} else {

			}
		});
		
	}

	var IDITEM = 0;
	function editItem(id){
		let item = ITENS.filter((x) => {
			return x.id == id
		})
		if(item){
			item = item[0]
			IDITEM = item.id
			$('#qtd').val(item.quantidade)
			$('.prod-name').html(item.produto.nome)
			$('#modal-edit').modal('show')
		}
	}

	function alterar(){
		let qtd = $('#qtd').val()

		qtd = qtd.replace(".", ",")

		for(let i=0; i<ITENS.length; i++){
			ITENS[i].quantidade = qtd
		}
		setTimeout(() => {
			let html = montaHtmlItens(ITENS, (html) => {
				console.log(html)
				$('tbody').html(html)
				$('#modal-edit').modal('hide')

			})
		}, 300)
	}

	function salvarTroca(){
		let js = {
			tipo: TIPO,
			id: ID,
			itens: ITENS
		}

		console.log(js)
	}
</script>
@endsection