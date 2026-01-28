@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	@if(!$produto->receita)
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<!--begin::Portlet-->

				<form method="post" action="/receita/save">


					<input type="hidden" name="id" value="{{{ isset($categoria) ? $categoria->id : 0 }}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Composição/Receita do produto {{$produto->nome}}</h3>
						</div>

					</div>
					@csrf
					<input type="hidden" name="produto_id" name="" value="{{$produto->id}}">


					<div class="row">

						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Descrição</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao" value="{{old('descricao')}}">
												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-2">
											<label class="col-form-label">Rendimento</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('rendimento')) is-invalid @endif" name="rendimento" value="{{old('rendimento')}}">
												@if($errors->has('rendimento'))
												<div class="invalid-feedback">
													{{ $errors->first('rendimento') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Tempo de Preparo (opcional)</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('tempo_preparo')) is-invalid @endif" name="tempo_preparo" value="{{old('tempo_preparo')}}">
												@if($errors->has('tempo_preparo'))
												<div class="invalid-feedback">
													{{ $errors->first('tempo_preparo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Qtd de Pedaços (opcional)</label>
											<button type="button" class="btn btn-light-info btn-sm btn-icon col-lg-6 col-sm-6" data-toggle="popover" data-trigger="click" data-content="Informe um valor no campo pedaços, se seu produto for uma pizza."><i class="la la-info"></i></button>
											<div class="">
												<input type="text" class="form-control @if($errors->has('pedacos')) is-invalid @endif" name="pedacos" value="{{old('pedacos')}}">
												@if($errors->has('pedacos'))
												<div class="invalid-feedback">
													{{ $errors->first('pedacos') }}
												</div>
												@endif
											</div>
										</div>
										
									</div>

								</div>
								<div class="card-footer">

									<div class="row">
										<div class="col-xl-2">

										</div>
										<div class="col-lg-3 col-sm-6 col-md-4">
											<button style="width: 100%" class="btn btn-danger" type="reset">
												<i class="la la-close"></i>
												<span class="">Limpar</span>
											</button>
										</div>
										<div class="col-lg-3 col-sm-6 col-md-4">
											<button style="width: 100%" type="submit" class="btn btn-success">
												<i class="la la-check"></i>
												<span class="">Salvar</span>
											</button>
										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	@else

	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>

				<div class="accordion" id="accordionExample1">

					<div class="card">
						<div class="card-header">
							<div class="card-title" data-toggle="collapse" data-target="#collapseOne1">
								<h3 class="card-title">Dados da receita/composição<i class="la la-angle-double-down"></i> {{$produto->nome}}
								</h3>
							</div>
						</div>

						<div id="collapseOne1" class="collapse" data-parent="#accordionExample1">
							<form method="post" action="/receita/update">
								<input type="hidden" value="{{$produto->receita->id}}" name="receita_id">
								<div class="container">

									<div class="row">
										<div class="form-group validated col-sm-12 col-lg-12">
											<label class="col-form-label">Descrição</label>
											<div class="">
												<input type="text" value="{{$produto->receita->descricao}}" class="form-control @if($errors->has('descricao')) is-invalid @endif" name="descricao">
												@if($errors->has('descricao'))
												<div class="invalid-feedback">
													{{ $errors->first('descricao') }}
												</div>
												@endif
											</div>
										</div>
									</div>

									<div class="row">
										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Rendimento</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('rendimento')) is-invalid @endif" name="rendimento" value="{{$produto->receita->rendimento}}">
												@if($errors->has('rendimento'))
												<div class="invalid-feedback">
													{{ $errors->first('rendimento') }}
												</div>
												@endif
											</div>
										</div>
										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Tempo de Preparo (opcional)</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('tempo_preparo')) is-invalid @endif" name="tempo_preparo" value="{{$produto->receita->tempo_preparo}}">
												@if($errors->has('tempo_preparo'))
												<div class="invalid-feedback">
													{{ $errors->first('tempo_preparo') }}
												</div>
												@endif
											</div>
										</div>

										<div class="form-group validated col-sm-6 col-lg-3">
											<label class="col-form-label">Qtd de Pedaços (opcional)</label>
											<div class="">
												<input type="text" class="form-control @if($errors->has('pedacos')) is-invalid @endif" name="pedacos" value="{{$produto->receita->pedacos}}">
												@if($errors->has('pedacos'))
												<div class="invalid-feedback">
													{{ $errors->first('pedacos') }}
												</div>
												@endif
											</div>
										</div>
										
										@csrf

										<div class="col-lg-3 col-sm-6 col-md-4 mt-5">
											<br>
											<button type="submit" class="btn btn-success">
												<i class="la la-refresh"></i>
												<span class="">ATUALIZAR</span>
											</button>
										</div>
									</div>

									<br>
								</div>

							</form>
						</div>
					</div>

				</div>
				<div class="row">
					<div class="col-sm-12 col-lg-6 col-md-12">

						<form method="post" action="/receita/saveItem">
							@csrf
							<input type="hidden" name="produto_id" name="" value="{{$produto->id}}">
							<input type="hidden" value="{{$produto->receita->id}}" name="receita_id">
							<div class="card-body">
								<div class="row">
									<div class="form-group col-sm-12 col-lg-12">
										<label class="col-form-label">Produto</label>

										<select class="form-control select2 w-100 @if($errors->has('produto')) is-invalid @endif" id="kt_select2_1" name="produto">
											<option value="">Selecione o produto</option>
											@foreach($produtos as $p)
											<option value="{{$p->id}}">{{$p->nome}} R${{ moeda($p->valor_venda)}}</option>
											@endforeach
										</select>
										@if($errors->has('produto'))
										<div class="text-danger">
											{{ $errors->first('produto') }}
										</div>
										@endif
									</div>

									<div class="form-group validated col-sm-6 col-lg-6">
										<label class="col-form-label">Quatidade</label>
										<div class="">
											<input type="text" id="quantidade" class="form-control @if($errors->has('quantidade')) is-invalid @endif" name="quantidade">
											@if($errors->has('quantidade'))
											<div class="invalid-feedback">
												{{ $errors->first('quantidade') }}
											</div>
											@endif
										</div>
									</div>

									<div class="form-group validated col-sm-6 col-lg-6">
										<label class="col-form-label">Unidade</label>

										<select class="custom-select form-control" name="medida">
											@foreach(App\Models\Produto::unidadesMedida() as $u)
											<option @if($u == 'UN') selected @endif value="{{$u}}">{{$u}}</option>
											@endforeach
										</select>
									</div>
								</div>
							</div>
							<div class="card-footer">
								<button type="reset" class="btn btn-secondary">Limpar</button>
								<button type="submit" class="btn btn-primary mr-2">Adicionar</button>
							</div>
						</form>
					</div>



					<div class="col-sm-12 col-lg-6 col-md-12">
						<div id="kt_datatable1_wrapper" class="dataTables_wrapper dt-bootstrap4 no-footer">

							<div class="row">
								<div class="col-sm-12">
									<div class="dataTables_scroll">
										<div class="dataTables_scrollHead" style="overflow: hidden; position: relative; border: 0px none; width: 100%;">
											<div class="dataTables_scrollHeadInner" style="box-sizing: content-box;padding-right: 0px;">
												<table class="table table-separate table-head-custom table-checkable dataTable no-footer">
													<thead>
														<tr role="row">
															<th class="sorting_asc" tabindex="0" aria-controls="kt_datatable1" rowspan="1" colspan="1"  aria-sort="ascending">PRODUTO</th>
															<th class="sorting" tabindex="0" aria-controls="kt_datatable1" rowspan="1" colspan="1" >QUANTIDADE</th>
															<th class="sorting" tabindex="0" aria-controls="kt_datatable1" rowspan="1" colspan="1">AÇÕES</th>

														</tr>
													</thead>
												</table>
											</div>
										</div>
										<div class="dataTables_scrollBody" style="position: relative; overflow: auto; width: 100%; max-height: 50vh;">
											<table class="table table-separate table-head-custom table-checkable dataTable no-footer" >

												<tbody>
													@foreach($produto->receita->itens as $i)
													<tr role="row" class="odd">
														<td class="sorting_1">{{$i->produto->nome}}</td>
														<td>{{$i->quantidade}}/{{$i->medida}}</td>
														<td nowrap="nowrap">
															<div class="dropdown dropdown-inline">

															</div>
															<a onclick='swal("Atenção!", "Deseja remover este item?", "warning").then((sim) => {if(sim){ location.href="/receita/deleteItem/{{ $i->id }}" }else{return false} })' href="#!" class="btn btn-sm btn-danger btn-icon" title="Remover item">
																<i class="la la-trash"></i> 
															</a>
														</td>
													</tr>
													@endforeach

												</tbody>
											</table>
										</div>
									</div>
								</div>
							</div>

						</div>
					</div>
					<div class="col-12">
						<button id="btn-finalizar" class="btn btn-success float-right">Finalizar</button>
					</div>
				</div>
				<br>
			</div>
		</div>
	</div>

	@endif
</div>
@endsection

@section('javascript')
<script type="text/javascript">
	$('[data-toggle="popover"]').popover()

	$('#btn-finalizar').click(() => {
		swal({
			title: "Alerta",
			text: "Deseja apontar o estoque?",
			icon: "warning",
			buttons: ["Não", 'Sim'],
			dangerMode: true,
		}).then((v) => {
			if (v) {
				location.href = path + 'estoque/apontamentoProducao'

			} else {
				location.href = path + 'produtos'

			}
		});
	})
</script>
@endsection
