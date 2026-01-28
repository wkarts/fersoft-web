@extends('default.layout')
@section('content')
<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<form method="post" action="/categorias/save-tributacao">

					<input type="hidden" name="id" value="{{ $categoria->id }}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">
							<h3 class="card-title">Tributação por categoria</h3>
						</div>
					</div>
					@csrf
					<div class="row">
						<div class="col-xl-12">
							<label>Total de produtos desta categoria: <strong>{{ sizeof($categoria->produtos) }}</strong></label>

							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<div class="row">
										<div class="form-group validated col-6 col-lg-2">
											<label class="col-form-label">% ICMS</label>
											<input type="text" class="form-control perc" name="perc_icms">
										</div>

										<div class="form-group validated col-6 col-lg-2">
											<label class="col-form-label">% PIS</label>
											<input type="tel" class="form-control perc" name="perc_pis">
										</div>

										<div class="form-group validated col-6 col-lg-2">
											<label class="col-form-label">% COFINS</label>
											<input type="tel" class="form-control perc" name="perc_cofins">
										</div>

										<div class="form-group validated col-6 col-lg-2">
											<label class="col-form-label">% IPI</label>
											<input type="tel" class="form-control perc" name="perc_ipi">
										</div>

										<div class="form-group validated col-6 col-lg-2">
											<label class="col-form-label">% RED. BC</label>
											<input type="tel" class="form-control perc" name="perc_red_bc">
										</div>

										<div class="form-group validated col-6 col-lg-2">
											<label class="col-form-label">CFOP saida interno</label>
											<input type="tel" class="form-control cfop" name="CFOP_saida_estadual">
										</div>

										<div class="form-group validated col-6 col-lg-2">
											<label class="col-form-label">CFOP saida externo</label>
											<input type="tel" class="form-control cfop" name="CFOP_saida_inter_estadual">
										</div>

										<div class="form-group validated col-12 col-lg-6">
											<label class="col-form-label">CST/CSOSN</label>
											<select class="custom-select form-control" name="CST_CSOSN">
												<option value="">Selecione uma opção</option>
												@foreach(App\Models\Produto::listaCSTCSOSN() as $key => $c)
												<option value="{{$key}}">{{$key}} - {{$c}}</option>
												@endforeach
											</select>
										</div>
										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">CST PIS</label>
											<select class="custom-select form-control" name="CST_PIS">
												<option value="">Selecione uma opção</option>
												@foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
												<option value="{{$key}}">{{$key}} - {{$c}}</option>
												@endforeach
											</select>
										</div>
										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">CST COFINS</label>
											<select class="custom-select form-control" name="CST_COFINS">
												<option value="">Selecione uma opção</option>
												@foreach(App\Models\Produto::listaCST_PIS_COFINS() as $key => $c)
												<option value="{{$key}}">{{$key}} - {{$c}}</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">CST IPI</label>
											<select class="custom-select form-control" name="CST_IPI">
												<option value="">Selecione uma opção</option>
												@foreach(App\Models\Produto::listaCST_IPI() as $key => $c)
												<option value="{{$key}}">{{$key}} - {{$c}}</option>
												@endforeach
											</select>
										</div>

										<div class="form-group validated col-12 col-lg-4">
											<label class="col-form-label">Código de enquandramento de IPI</label>
											<select class="custom-select form-control" name="cenq_ipi">
												@foreach(App\Models\Produto::listaCenqIPI() as $key => $c)
												<option value="{{$key}}">{{$key}} - {{$c}}</option>
												@endforeach
											</select>
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
								<a style="width: 100%" class="btn btn-danger" href="/categorias">
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