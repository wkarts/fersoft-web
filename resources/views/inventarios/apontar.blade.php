@extends('default.layout')
@section('content')

<style type="text/css">
	#focus-codigo:hover{
		cursor: pointer
	}
</style>

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/inventario/apontar">
					<input type="hidden" id="inventario_id" name="inventario_id" value="{{$inventario->id}}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Apontar Item
								<a style="margin-left: 5px;" href="/inventario/itens/{{$inventario->id}}" class="btn btn-light-info">
									Listar Itens do inventário
								</a>
							</h3>
						</div>
					</div>
					@csrf
					<input type="" autofocus="" style="border: none; width: 0px; height: 0px; " id="codBarras" name="">
					<div class="row">
						<div class="col-xl-12">
							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-lg-6 col-md-8 col-sm-10">
											<label class="col-form-label text-left col-lg-4 col-sm-12">Produto</label>
											<div class="input-group">
												<div class="input-group-prepend">
													<span class="input-group-text" id="focus-codigo">
														<li class="la la-barcode"></li>
													</span>
												</div>
												<select class="form-control select2" id="kt_select2_1" name="produto_id">
													<option value="">--</option>
													@foreach($produtos as $p)
													<option
													@if(old('produto_id') == $p->id)
													selected
													@endif
													value="{{$p->id}}">
													{{$p->nome}} 
													@if($p->referencia != "")
													| REF: {{$p->referencia}}
													@endif
												</option>
												@endforeach
											</select>
										</div>
										@if($errors->has('produto_id'))
										<div class="invalid-feedback">
											{{ $errors->first('produto_id') }}
										</div>
										@endif
									</div>
							
									<div class="form-group validated col-sm-6 col-lg-2">
										<label class="col-form-label">Quantiade</label>
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
										<label class="col-form-label">Estado do item</label>
										<div class="">

											<select class="custom-select @if($errors->has('estado')) is-invalid @endif" name="estado">
												<option value="">--</option>
												@foreach(App\Models\ItemInventario::estados() as $t)
												<option  @if(old('estado') == $t) selected @endif value="{{$t}}">{{$t}}</option>
												@endforeach
											</select>

											@if($errors->has('estado'))
											<div class="invalid-feedback">
												{{ $errors->first('estado') }}
											</div>
											@endif
										</div>
									</div>

								</div>

								<div class="row">
									<div class="form-group validated col-lg-8 col-md-8 col-sm-10">
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
	$('#focus-codigo').click(() => {
		$('#codBarras').focus()
	})

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

				if(e){
					$('#kt_select2_1').val(e.id).change()
				}else{
					swal("Erro", "Produto não encontrado", "error")
				}
			}, error: function(e){
				console.log(e)
			}
		});
	}

	$('#kt_select2_1').change(() => {
		let produtoId = $('#kt_select2_1').val()
		let inventarioId = $('#inventario_id').val()

		$.get(path+'inventario/produtoJaAdicionadoInventario', {produto: produtoId, inventario: inventarioId})
		.done((res) => {
			console.log(res)
		})
		.fail((err) => {
			swal("Alerta", "Este produto já esta registrado", "warning")
			$('#kt_select2_1').val('').change()
		})
	})
</script>	
@endsection

@endsection

