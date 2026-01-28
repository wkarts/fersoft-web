@extends('default.layout')
@section('content')

<div class=" d-flex flex-column flex-column-fluid" id="kt_content">
	<div class="card card-custom gutter-b example example-compact">
		<div class="container @if(env('ANIMACAO')) animate__animated @endif animate__backInLeft">
			<div class="col-lg-12">
				<br>
				<!--begin::Portlet-->

				<form method="post" action="/estoque/set-estoque-local">
					<input type="hidden" name="produto_id" value="{{ $item->id }}">
					<div class="card card-custom gutter-b example example-compact">
						<div class="card-header">

							<h3 class="card-title">Adicionar Estoque <strong class="text-success ml-1">{{ $item->nome }}</strong></h3>
						</div>
					</div>
					@csrf

					<div class="row">
						<div class="col-xl-2"></div>
						<div class="col-xl-8">

							<div class="kt-section kt-section--first">
								<div class="kt-section__body">

									<table class="table">
										<thead>
											<tr>
												<th>Local</th>
												<th>Quantidade</th>
											</tr>
										</thead>
										<tbody>
											@foreach($locais as $key => $local)
											@if(sizeof($grade) > 1)
											@foreach($grade as $k => $produto)

											<tr>
												<td>
													{{$local}} - 
													<strong>
														grade: {{$produto->str_grade}}
													</strong>
												</td>
												<td>
													<input type="hidden" value="{{$key}}" name="filial_id[]">
													<input type="hidden" value="{{$produto->id}}" name="produto_grade_id[]">
													<input id="" type="tel" class="form-control qtd-p" required name="quantidade[]" 
													value="{{ $item->estoqueAtual() }}">
												</td>
											</tr>
											@endforeach
											@else
											<tr>
												<td>
													{{$local}}
												</td>
												<td>
													<input type="hidden" value="{{$key}}" name="filial_id[]">
													<input id="" type="tel" class="form-control qtd-p" required name="quantidade[]" 
													value="{{ $item->estoqueAtual() }}">
												</td>
											</tr>
											@endif
											@endforeach
										</tbody>
									</table>

								</div>
							</div>
						</div>
					</div>
					<div class="card-footer">

						<div class="row">
							<div class="col-xl-2">

							</div>
							<div class="col-lg-3 col-sm-6 col-md-4">
								<a style="width: 100%" class="btn btn-danger" href="/produtos">
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


