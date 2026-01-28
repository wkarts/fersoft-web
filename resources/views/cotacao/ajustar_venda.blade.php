@extends('default.layout', ['title' => 'Cotação para venda'])
@section('content')

<div class="card card-custom gutter-b" id="content">

	<div class="card-body @if(env('ANIMACAO')) animate__animated @endif animate__backInRight">
		<div class="content d-flex flex-column flex-column-fluid" id="kt_content" >
			<div class="col-12">
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Cotação #{{ $item->link }}</h3>
						<h3>Fornecedor: <strong>{{ $item->fornecedor->razao_social }}</strong></h3>	
					</div>

					<form class="card-body" method="post" action="/cotacao/salvar-venda/{{$item->id}}">
						@csrf
						@method('put')
						<p class="text-danger">O valor de venda e percentual de lucro será atualizado</p>
						@if($item->venda_id)
						<h5>Já existe uma venda para esta cotação <a href="/vendas/detalhar/{{ $item->venda_id }}" class="btn btn-sm btn-dark">ver venda</a></h5>
						@endif
						<div class="table-responsive">
							<table class="table">
								<thead>
									<tr>
										<th>Produto</th>
										<th>Valor unit. de venda atual</th>
										<th>Quantidade</th>
										<th>Valor unit. de compra</th>
										<th>Subtotal</th>
										<th>% lucro</th>
										<th>Valor unit. de venda</th>
									</tr>
								</thead>
								<tbody>
									@foreach($item->itens as $p)
									<tr>
										<input type="hidden" name="item_id[]" value="{{ $p->id }}">
										<td>{{ $p->produto->nome }}</td>
										<td>
											{{ moeda($p->produto->valor_venda) }}
										</td>
										<td>{{ $p->quantidade }}</td>
										<td><span>{{ moeda($p->valor_unitario) }}</span></td>
										<td>{{ moeda($p->valor) }}</td>
										<td>
											<input @if($item->venda_id) disabled @endif required type="tel" name="percentual_lucro[]" value="{{ $p->produto->percentual_lucro }}" class="form-control percentual_lucro perc">
										</td>
										<td>
											<input @if($item->venda_id) disabled @endif required type="tel" name="valor_venda[]" value="{{ moeda($p->valor_unitario + ($p->valor_unitario*($p->produto->percentual_lucro/100))) }}" class="form-control valor_venda">
										</td>
										
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
						<button @if($item->venda_id) disabled @endif class="btn btn-success float-right">Gerar venda</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@section('javascript')
<script type="text/javascript">
	$(document).on("blur", ".percentual_lucro", function () {
		console.log($(this).val())
        $vl = $(this).closest('td').next().find('input');
        $vc = $(this).closest('td').prev().prev().find('span');
		console.log(convertMoedaToFloat($vl.val()))
		console.log($vc.html())
		let c = (($(this).val()/100) * convertMoedaToFloat($vc.html())) + convertMoedaToFloat($vc.html())
		$vl.val(convertFloatToMoeda(c))

	})
</script>
@endsection
